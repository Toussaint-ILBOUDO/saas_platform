<?php

namespace App\Modules\Plateforme\Services;

use App\Models\ContratCabinet;
use App\Models\ServicePlateforme;
use App\Models\LigneTypePlateforme;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * P8 — Cycle de vie d'un contrat commercial de cabinet (D-048/D-049).
 *
 * Tarifs en entiers FCFA : null = service **non couvert**, 0 = gratuit
 * négocié explicitement (Q-A17). Taux : 0 % ≈ non applicable.
 * Un contrat validé est immuable (Q-A16) : un avenant = nouvelle version.
 */
final class ContratCabinetService
{
    /** Code de ligne tarifada → intitulé (écran + PDF). */
    public const LIBELLES_TARIFS = [
        'frais_creation_contrat' => 'Frais de création — contrat de service',
        'frais_contrat_actif' => 'Frais de contrat actif',
        'abonnement_mensuel_enseignant' => 'Abonnement mensuel — enseignant',
        'abonnement_mensuel_parent' => 'Abonnement mensuel — parent',
        'abonnement_mensuel_eleve' => 'Abonnement mensuel — élève',
        'frais_rapport_mensuel' => 'Frais — rapport mensuel validé',
        'abonnement_annuel_admin' => 'Abonnement annuel — admin du cabinet',
        'maintenance_annuelle_site' => 'Maintenance annuelle — site web',
        'taux_commission_librairie' => 'Commission — Librairie (% de vente)',
        'taux_commission_autres' => 'Commission — autres services (%)',
    ];

    public const TARIFS_ENTIERS = [
        'frais_creation_contrat',
        'frais_contrat_actif',
        'abonnement_mensuel_enseignant',
        'abonnement_mensuel_parent',
        'abonnement_mensuel_eleve',
        'frais_rapport_mensuel',
        'abonnement_annuel_admin',
        'maintenance_annuelle_site',
    ];

    public const TAUX_DECIMAUX = [
        'taux_commission_librairie',
        'taux_commission_autres',
    ];

    private const CHAMPS_TXT = [
        'conditions_particulieres',
        'modalites_paiement',
        'responsable_nom',
    ];

    private const LIMITE_TXT = [
        'conditions_particulieres' => 3000,
        'modalites_paiement' => 1000,
        'responsable_nom' => 120,
    ];

    /** @return \Illuminate\Support\Collection<int, ContratCabinet> */
    public function lister(string $cabinetId): \Illuminate\Support\Collection
    {
        return ContratCabinet::query()
            ->where('cabinet_id', $cabinetId)
            ->orderByDesc('version')
            ->orderByDesc('id')
            ->get();
    }

    /** Dernier contrat validé du cabinet, s'il existe. */
    public function enCours(?string $cabinetId): ?ContratCabinet
    {
        return ContratCabinet::chaineValidee($cabinetId);
    }

    public function prendre(?string $cabinetId, int $id): ContratCabinet
    {
        $contrat = ContratCabinet::query()->findOrFail($id);

        if (empty($contrat->cabinet_id) || $contrat->cabinet_id !== $cabinetId) {
            throw ValidationException::withMessages(['contrat' => 'Contrat introuvable pour ce cabinet.']);
        }

        return $contrat;
    }

    /**
     * @param array $data Voir ContratCabinetService::analyseDonnees()
     */
    public function ajouterVersion(string $cabinetId, array $data, ?int $superAdminId): ContratCabinet
    {
        $champs = $this->analyseDonnees($data);

        return DB::transaction(function () use ($cabinetId, $champs, $data, $superAdminId) {
            $derniereVersion = (int) (ContratCabinet::query()
                ->where('cabinet_id', $cabinetId)
                ->max('version') ?? 0);

            return ContratCabinet::create([
                'cabinet_id' => $cabinetId,
                'numero' => app(NumerotationPlateformeService::class)->prochain('CTA', (string) now()->year),
                'version' => $derniereVersion + 1,
                'statut' => ContratCabinet::STATUT_BROUILLON,
                'exonerations' => $data['exonerations'] ?? [],
                'conditions_particulieres' => $data['conditions_particulieres'] ?? null,
                'modalites_paiement' => $data['modalites_paiement'] ?? null,
                'responsable_nom' => $data['responsable_nom'] ?? null,
                'date_effet' => $data['date_effet'] ?? null,
                'date_fin_validite' => $data['date_fin_validite'] ?? null,
                'cree_par_user' => $superAdminId,
            ] + $champs);
        });
    }

    /** Modifications autorisées uniquement sur une version brouillon. */
    public function modifier(ContratCabinet $contrat, array $data): ContratCabinet
    {
        if (! $contrat->estBrouillon()) {
            throw ValidationException::withMessages(['contrat' => 'Un contrat validé est immuable — créez une nouvelle version.']);
        }

        $champs = $this->analyseDonnees($data);
        $champs['exonerations'] = $data['exonerations'] ?? $contrat->exonerations;
        $champs['conditions_particulieres'] = $data['conditions_particulieres'] ?? $contrat->conditions_particulieres;
        $champs['modalites_paiement'] = $data['modalites_paiement'] ?? $contrat->modalites_paiement;
        $champs['responsable_nom'] = $data['responsable_nom'] ?? $contrat->responsable_nom;
        $champs['date_effet'] = $data['date_effet'] ?? $contrat->date_effet;
        $champs['date_fin_validite'] = $data['date_fin_validite'] ?? $contrat->date_fin_validite;

        $contrat->fill($champs)->save();

        return $contrat->refresh();
    }

    /** Brouillon → valide : PDF archivé, versions validées précédentes archivées. */
    public function valider(ContratCabinet $contrat, ?int $superAdminId): ContratCabinet
    {
        DB::transaction(function () use ($contrat, $superAdminId): void {
            ContratCabinet::query()
                ->where('cabinet_id', $contrat->cabinet_id)
                ->where('statut', ContratCabinet::STATUT_VALIDE)
                ->update(['statut' => ContratCabinet::STATUT_ARCHIVE]);

            $contrat->statut = ContratCabinet::STATUT_VALIDE;
            $contrat->valide_par_user = $superAdminId;
            $contrat->valide_le = now();
            $contrat->save();
        });

        app(ContratCabinetPdf::class)->generer($contrat);

        return $contrat->refresh();
    }

    /** Supprime une version brouillon uniquement. */
    public function supprimer(ContratCabinet $contrat): bool
    {
        if (! $contrat->estBrouillon()) {
            throw ValidationException::withMessages(['contrat' => 'Seul un brouillon peut être supprimé.']);
        }

        return (bool) $contrat->delete();
    }

    // ---------------------------------------------------------------

    /**
     * Grille des services du contrat (les 10 colonnes négociables) : libellé,
     * catégorie et explication issus du catalogue éditable
     * (`services_plateforme`) avec repli sur les constantes.
     *
     * @return array<string, array{libelle: string, description: ?string, categorie: string}>
     */
    public function grilleServices(): array
    {
        $grille = [];

        foreach (array_merge(self::TARIFS_ENTIERS, self::TAUX_DECIMAUX) as $cle) {
            $grille[$cle] = [
                'libelle' => self::LIBELLES_TARIFS[$cle] ?? $cle,
                'description' => null,
                'categorie' => 'ponctuel',
            ];
        }

        try {
            foreach (ServicePlateforme::query()->whereIn('cle', array_keys($grille))->get() as $service) {
                $grille[$service->cle] = [
                    'libelle' => (string) $service->libelle,
                    'description' => $service->description,
                    'categorie' => (string) $service->categorie,
                ];
            }
        } catch (\Throwable) {
            // Catalogue absent/migré tard : on garde les libellés constants.
        }

        return $grille;
    }

    /** Lignes types actives (briques de facturation manuelle, éditables). */
    public function lignesTypes(): \Illuminate\Support\Collection
    {
        try {
            return LigneTypePlateforme::actifs()->tries()->get();
        } catch (\Throwable) {
            return collect();
        }
    }

    /** à partir d'un payload `tarifs` d'écran → colonne => int|float|null. */
    private function decoderTarifs(array $brut): array
    {
        $champs = [];

        foreach (self::TARIFS_ENTIERS as $colonne) {
            $valeur = $brut[$colonne] ?? null;

            if ($valeur === null || $valeur === '') {
                $champs[$colonne] = null;
                continue;
            }

            if (! is_numeric($valeur) || floor((float) $valeur) != $valeur) {
                throw ValidationException::withMessages(['tarifs.'.$colonne => 'Entier FCFA attendu.']);
            }

            if ((int) $valeur < 0) {
                throw ValidationException::withMessages(['tarifs.'.$colonne => 'Ne peut être négatif.']);
            }

            if ((int) $valeur > 999_999_999) {
                throw ValidationException::withMessages(['tarifs.'.$colonne => 'Entier incohérent (9 chiffres max).']);
            }

            $champs[$colonne] = (int) $valeur;
        }

        foreach (self::TAUX_DECIMAUX as $colonne) {
            $valeur = $brut[$colonne] ?? null;

            if ($valeur === null || $valeur === '') {
                $champs[$colonne] = null;
                continue;
            }

            // Saisie française acceptée : virgule décimale (5,5 → 5.5).
            $valeur = str_replace(',', '.', (string) $valeur);

            if (! is_numeric($valeur)) {
                throw ValidationException::withMessages(['tarifs.'.$colonne => 'Nombre attendu.']);
            }

            $arrondi = round((float) $valeur, 2);

            if ($arrondi < 0 || $arrondi > 100) {
                throw ValidationException::withMessages(['tarifs.'.$colonne => 'Doit être entre 0 et 100 %.']);
            }

            // 0 % = commission non applicable (défaut Q-A17) ; on stocke null.
            $champs[$colonne] = $arrondi === 0.0 ? null : $arrondi;
        }

        return $champs;
    }

    private function analyseDonnees(array $data): array
    {
        $champs = $this->decoderTarifs($data['tarifs'] ?? []);

        $effet = $data['date_effet'] ?? null;
        $fin = $data['date_fin_validite'] ?? null;

        if ($effet !== null && $fin !== null && $effet > $fin) {
            throw ValidationException::withMessages(['date_fin_validite' => 'La fin de validité doit suivre la date d’effet.']);
        }

        if (isset($data['exonerations'])) {
            if (! is_array($data['exonerations'])) {
                throw ValidationException::withMessages(['exonerations' => 'Liste invalide.']);
            }

            // La grille du contrat (form) encode les exonérations par COLONNE
            // de tarif (ex : `abonnement_mensuel_enseignant`) — le calcul de
            // facture traduit ensuite code de ligne ↔ colonne.
            $colonnes = array_merge(self::TARIFS_ENTIERS, self::TAUX_DECIMAUX);
            $inconnus = array_diff($data['exonerations'], $colonnes);

            if ($inconnus !== []) {
                throw ValidationException::withMessages(['exonerations' => 'Codes inconnus : '.implode(', ', $inconnus)]);
            }
        }

        foreach (self::CHAMPS_TXT as $champ) {
            $valeur = $data[$champ] ?? null;
            if ($valeur !== null && mb_strlen($valeur) > self::LIMITE_TXT[$champ]) {
                throw ValidationException::withMessages([$champ => self::LIMITE_TXT[$champ].' caractères max.']);
            }
        }

        return $champs;
    }
}
