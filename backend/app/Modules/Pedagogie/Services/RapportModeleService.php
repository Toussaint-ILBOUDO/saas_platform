<?php

namespace App\Modules\Pedagogie\Services;

use App\Models\RapportElement;
use App\Models\RapportSection;
use App\Modules\Pedagogie\Exceptions\ModeleRapportNonMigreException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Modèle de rapport mensuel — configuration par l'administration.
 *
 * L'enseignant remplit la partie qualitative de son rapport mensuel à partir
 * de ce modèle : les sections (blocs) et leurs éléments (champs) sont créés,
 * ordonnés, rendus obligatoires ou désactivés ici. Les informations générales
 * et le bilan des activités, eux, sont TOUJOURS automatiques — ils ne font
 * pas partie du modèle.
 */
class RapportModeleService
{
    /**
     * Arbre des sections et de leurs éléments.
     *
     * @param  bool  $actifsSeuls  `true` pour le formulaire enseignant (seuls
     *                             les blocs/éléments actifs sont affichés),
     *                             `false` pour l'administration.
     * @return Collection<int, RapportSection>
     */
    public function sections(bool $actifsSeuls = false): Collection
    {
        try {
            $sections = RapportSection::query()
                ->with(['elements' => fn ($q) => $q->orderBy('ordre')])
                ->orderBy('ordre')
                ->get();
        } catch (QueryException $e) {
            // Tables absentes : la migration `tenants:migrate` n'a pas encore été
            // appliquée à ce cabinet. Message clair plutôt qu'un 500 muet.
            if ($this->estTableManquante($e)) {
                throw new ModeleRapportNonMigreException;
            }

            throw $e;
        }

        if ($actifsSeuls) {
            $sections = $sections->where('actif', true)->values();

            foreach ($sections as $section) {
                $section->setRelation(
                    'elements',
                    $section->elements->where('actif', true)->values()
                );
            }
        }

        return $sections;
    }

    /**
     * Arbre prêt pour l'API : sections avec leurs éléments sous forme de
     * tableaux associatifs.
     *
     * @param  bool  $actifsSeuls  même filtre que `sections()`.
     * @return array<int, array<string, mixed>>
     */
    public function auFormat(bool $actifsSeuls = false): array
    {
        return $this->sections($actifsSeuls)
            ->map(function (RapportSection $section) {
                return [
                    'id' => $section->id,
                    'libelle' => $section->libelle,
                    'description' => $section->description,
                    'ordre' => $section->ordre,
                    'actif' => $section->actif,
                    'elements' => $section->elements
                        ->map(fn (RapportElement $element) => [
                            'id' => $element->id,
                            'section_id' => $element->section_id,
                            'libelle' => $element->libelle,
                            'type' => $element->type,
                            'obligatoire' => $element->obligatoire,
                            'aide' => $element->aide,
                            'ordre' => $element->ordre,
                            'actif' => $element->actif,
                        ])
                        ->values(),
                ];
            })
            ->values()
            ->all();
    }

    public function creerSection(string $libelle, ?string $description = null): RapportSection
    {
        $ordre = (int) RapportSection::query()->max('ordre') + 1;

        return RapportSection::create([
            'libelle' => $libelle,
            'description' => $description ?: null,
            'ordre' => $ordre,
            'actif' => true,
        ]);
    }

    public function modifierSection(RapportSection $section, array $donnees): RapportSection
    {
        $section->update([
            'libelle' => $donnees['libelle'] ?? $section->libelle,
            'description' => array_key_exists('description', $donnees)
                ? ($donnees['description'] ?: null)
                : $section->description,
            'actif' => $donnees['actif'] ?? $section->actif,
        ]);

        return $section->fresh();
    }

    public function supprimerSection(RapportSection $section): void
    {
        // Les éléments partent en cascade (contrainte `cascadeOnDelete`).
        $section->delete();
    }

    public function creerElement(int $sectionId, array $donnees): RapportElement
    {
        $section = RapportSection::findOrFail($sectionId);
        $ordre = (int) $section->elements()->count() + 1;

        return RapportElement::create([
            'section_id' => $sectionId,
            'libelle' => $donnees['libelle'],
            'type' => $donnees['type'] ?? 'textarea',
            'obligatoire' => (bool) ($donnees['obligatoire'] ?? false),
            'aide' => $donnees['aide'] ?? null,
            'ordre' => $ordre,
            'actif' => true,
        ]);
    }

    public function modifierElement(RapportElement $element, array $donnees): RapportElement
    {
        $element->update([
            'libelle' => $donnees['libelle'] ?? $element->libelle,
            'type' => $donnees['type'] ?? $element->type,
            'obligatoire' => $donnees['obligatoire'] ?? $element->obligatoire,
            'aide' => array_key_exists('aide', $donnees)
                ? ($donnees['aide'] ?: null)
                : $element->aide,
            'actif' => $donnees['actif'] ?? $element->actif,
        ]);

        return $element->fresh();
    }

    public function supprimerElement(RapportElement $element): void
    {
        $element->delete();
    }

    /**
     * Réordonne les sections selon l'ordre fourni par le client.
     *
     * @param  array<int, int>  $ids
     */
    public function reordonnerSections(array $ids): void
    {
        $this->appliquerOrdre(RapportSection::class, $ids);
    }

    /**
     * Réordonne les éléments (dans leur section) selon l'ordre fourni.
     *
     * @param  array<int, int>  $ids
     */
    public function reordonnerElements(array $ids): void
    {
        $this->appliquerOrdre(RapportElement::class, $ids);
    }

    /**
     * SQLSTATE PostgreSQL des tables inexistantes (42P01) : la base du cabinet
     * n'est pas encore migrée pour le modèle de rapport.
     */
    protected function estTableManquante(QueryException $e): bool
    {
        return (string) $e->getCode() === '42P01';
    }

    /**
     * Applique l'ordre de la liste. Les ids absents de la liste passent en
     * fin de file (ordre = max + index) : jamais de trou dans la séquence.
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $modele
     * @param  array<int, int>  $ids
     */
    protected function appliquerOrdre(string $modele, array $ids): void
    {
        DB::transaction(function () use ($modele, $ids) {
            $ordre = 0;

            foreach ($ids as $id) {
                $ordre++;
                $modele::query()->whereKey($id)->update(['ordre' => $ordre]);
            }

            // Les entrées absentes de la liste sont repoussées en fin de file,
            // dans leur ordre relatif : aucune série n'est réordonnée par surprise.
            $suffixe = $ordre;

            $modele::query()
                ->whereNotIn('id', $ids)
                ->orderBy('ordre')
                ->get()
                ->each(function ($entite) use (&$suffixe) {
                    $suffixe++;
                    $entite->update(['ordre' => $suffixe]);
                });
        });
    }
}