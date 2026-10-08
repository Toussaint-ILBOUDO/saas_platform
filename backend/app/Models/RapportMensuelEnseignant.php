<?php

namespace App\Models;

use App\Modules\Pedagogie\Exceptions\ModeleRapportNonMigreException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

class RapportMensuelEnseignant extends Model
{
    protected $fillable = [
        'contrat_cours_id',
        'enseignant_id',
        'periode_id',
        'volume_horaire_cumule',
        'bilan_activites',
        // Modèle de rapport administrable : réponses indexées par élément.
        'reponses',
        // Colonnes dépréciées, conservées pour le monolithe web (repliées dans
        // `reponses` à la migration). Le flux API/PDF lit `reponses`.
        'point_notes_matieres',
        'point_notes_autres_matieres',
        'difficultes_rencontrees',
        'solutions_trouvees',
        'attentes_parents_eleve',
        'attentes_administration',
        'appreciation_evolution',
        'observations',
        'statut',
        'motif_rejet',
        'date_validation',
        'valide_par',
    ];

    protected $casts = [
        'volume_horaire_cumule' => 'decimal:2',
        'reponses' => 'array',
        'date_validation' => 'datetime',
    ];

    /**
     * Inclure les sections/éléments du modèle de rapport dans la ressource
     * sérialisée (détail) sans les transporter dans les listes.
     */
    public bool $avecSections = false;

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function contratCours()
    {
        return $this->belongsTo(ContratCours::class);
    }

    public function enseignant()
    {
        return $this->belongsTo(
            EnseignantProfil::class,
            'enseignant_id',
            'id'
        );
    }

    public function periode()
    {
        return $this->belongsTo(PeriodeComptable::class,'periode_id');
    }

    /**
     * D-049 — détail des heures par matière. C'est cette relation qui alimente
     * la facture et le bulletin.
     */
    public function lignes()
    {
        return $this->hasMany(
            RapportMensuelEnseignantLigne::class,
            'rapport_mensuel_enseignant_id'
        );
    }

    public function valideur()
    {
        return $this->belongsTo(User::class, 'valide_par');
    }

    /**
     * Lignes porteuses d'heures (> 0) : seules celles-ci sont facturables.
     */
    public function lignesFacturables()
    {
        return $this->lignes->filter(
            fn (RapportMensuelEnseignantLigne $ligne) => (float) $ligne->nombre_heures > 0
        );
    }

    /*
    |--------------------------------------------------------------------------
    | MODÈLE DE RAPPORT (sections / éléments / réponses)
    |--------------------------------------------------------------------------
    */

    /**
     * Les sections et éléments du modèle de rapport, chacun portant la réponse
     * de CET enseignant pour cet élément. Consommé par la ressource API (détail)
     * et par le PDF — une seule source de vérité pour la présentation.
     *
     * Le modèle de rapport étant global au cabinet et stable pendant une
     * requête, la structure (sans les réponses) est mémoïsée statiquement ;
     * seules les réponses sont réinjectées à chaque appel.
     *
     * @return array<int, array{id: int, libelle: string, description: string|null, elements: array<int, array{id: int, libelle: string, type: string, obligatoire: bool, aide: string|null, reponse: string|null}>}>
     */
    public function sectionsRenseignees(): array
    {
        $reponses = (array) ($this->reponses ?? []);

        if (static::$sectionsMemo === null) {
            try {
                static::$sectionsMemo = RapportSection::query()
                    ->where('actif', true)
                    ->orderBy('ordre')
                    ->get()
                    ->map(function (RapportSection $section) {
                        return [
                            'id' => $section->id,
                            'libelle' => $section->libelle,
                            'description' => $section->description,
                            'elements' => $section->elements
                                ->where('actif', true)
                                ->values()
                                ->map(fn (RapportElement $element) => [
                                    'id' => $element->id,
                                    'libelle' => $element->libelle,
                                    'type' => $element->type,
                                    'obligatoire' => $element->obligatoire,
                                    'aide' => $element->aide,
                                    'reponse' => null,
                                ])
                                ->all(),
                        ];
                    })
                    ->values()
                    ->all();
            } catch (QueryException $e) {
                // Tables absentes : la migration `tenants:migrate` n'a pas encore
                // été appliquée à ce cabinet. Message clair plutôt qu'un 500 muet.
                if ((string) $e->getCode() === '42P01') {
                    throw new ModeleRapportNonMigreException;
                }

                throw $e;
            }
        }

        return array_map(function (array $section) use ($reponses) {
            $section['elements'] = array_map(function (array $element) use ($reponses) {
                $element['reponse'] = $reponses[(string) $element['id']] ?? null;

                return $element;
            }, $section['elements']);

            return $section;
        }, static::$sectionsMemo);
    }

    private static ?array $sectionsMemo = null;

    /*
    |--------------------------------------------------------------------------
    | ÉTATS (D-051)
    |--------------------------------------------------------------------------
    |
    | soumis   → déposé par l'enseignant, en attente de validation
    | valide   → validé par l'admin : alimente la facture et le bulletin
    | rejete   → renvoyé à l'enseignant avec motif, modifiable et re-soumissible
    |
    */

    public function estSoumis(): bool
    {
        return $this->statut === 'soumis';
    }

    public function estValide(): bool
    {
        return $this->statut === 'valide';
    }

    public function estRejete(): bool
    {
        return $this->statut === 'rejete';
    }

    /**
     * Un rapport validé ou rejeté n'est plus modifiable par l'enseignant ; seul
     * un rapport « soumis » peut être corrigé puis re-soumis (D-051).
     */
    public function estModifiableParEnseignant(): bool
    {
        return $this->statut === 'soumis';
    }
}