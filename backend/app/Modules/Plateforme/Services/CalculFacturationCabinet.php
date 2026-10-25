<?php

namespace App\Modules\Plateforme\Services;

use App\Models\Cabinet;
use App\Models\ContratCabinet;
use App\Models\EvenementFacturable;
use App\Models\LigneFactureCabinet;
use Illuminate\Support\Carbon;

/**
 * P8 — Calcul des lignes automatiques d'une facture cabinet, à partir
 * des événements journalisés sur la période et des tarifs du contrat
 * validé (aucun tarif codé en dur — consigne P8).
 */
final class CalculFacturationCabinet
{
    /** Événement journalisé → [type de ligne, colonne tarif du contrat]. */
    private const EVENEMENTS = [
        EvenementFacturable::TYPE_CONTRAT_CREE => ['frais_creation_contrat', 'frais_creation_contrat'],
        EvenementFacturable::TYPE_CONTRAT_REACTIVE => ['frais_contrat_actif', 'frais_contrat_actif'],
        EvenementFacturable::TYPE_RAPPORT_VALIDE => ['rapport_mensuel', 'frais_rapport_mensuel'],
        EvenementFacturable::TYPE_VENTE_LIBRAIRIE => ['commission_librairie', null], // assiette × taux
    ];

    /** Abonnements mensuels (comptes tenant par rôle). */
    private const ABONNEMENTS = [
        'abonnement_enseignant' => ['tarif' => 'abonnement_mensuel_enseignant', 'roles' => ['enseignant']],
        'abonnement_parent' => ['tarif' => 'abonnement_mensuel_parent', 'roles' => ['parent']],
        'abonnement_eleve' => ['tarif' => 'abonnement_mensuel_eleve', 'roles' => ['eleve']],
    ];

    /** Abonnements annuels facturables à l'anniversaire du cabinet (Q-A18). */
    private const ANNIVERSAIRES = [
        'abonnement_admin' => 'abonnement_annuel_admin',
        'maintenance_site' => 'maintenance_annuelle_site',
    ];

    /** Libellés affichés dans les lignes de facture (et le PDF). */
    public const LIBELLES_TYPES = [
        'frais_creation_contrat' => 'Frais de création — contrat de service',
        'frais_contrat_actif' => 'Frais de contrat actif',
        'abonnement_enseignant' => 'Abonnement mensuel — enseignant',
        'abonnement_parent' => 'Abonnement mensuel — parent',
        'abonnement_eleve' => 'Abonnement mensuel — élève',
        'rapport_mensuel' => 'Frais — rapport mensuel validé',
        'abonnement_admin' => 'Abonnement annuel — admin du cabinet',
        'maintenance_site' => 'Maintenance annuelle — site web',
        'commission_librairie' => 'Commission — librairie (vente)',
        'libre' => 'Ligne manuelle',
    ];

    /**
     * @param  string  $cabinetId  Id du cabinet (tenants.id)
     * @param  Carbon  $debut  Début de la période de facture (inclus)
     * @param  Carbon  $fin  Fin de la période de facture (inclus)
     * @param  ContratCabinet|null  $contrat  Dernier contrat validé du cabinet (peut être null)
     * @return array{0: \Illuminate\Support\Collection<int, array>, 1: array<string, string>}
     */
    public function calculer(string $cabinetId, Carbon $debut, Carbon $fin, ?ContratCabinet $contrat): array
    {
        if ($contrat === null) {
            return [collect(), []];
        }

        $periode = $debut->format('Y-m-d').' → '.$fin->format('Y-m-d');
        $lignes = collect();

        // 1) Événements journalisés sur la période et disponibles.
        $evenements = EvenementFacturable::query()
            ->where('cabinet_id', $cabinetId)
            ->where('statut', EvenementFacturable::STATUT_DISPONIBLE)
            ->whereNull('facture_cabinet_id')
            ->whereBetween('date_evenement', [$debut->copy()->startOfDay(), $fin->copy()->endOfDay()])
            ->orderBy('date_evenement')
            ->orderBy('id')
            ->get();

        foreach ($evenements as $evenement) {
            $definition = self::EVENEMENTS[$evenement->type] ?? null;

            if ($definition === null) {
                continue;
            }

            [$type, $colonneTarif] = $definition;

            if ($this->exonere($contrat, $type)) {
                continue;
            }

            $ligne = [
                'type' => $type,
                'sens' => LigneFactureCabinet::SENS_DEBIT,
                'libelle' => $evenement->libelle ?? $this->libelleType($type),
                'quantite' => 1,
                'prix_unitaire' => null,
                'montant' => null,
                'source' => 'auto',
                'evenement_facturable_id' => $evenement->getKey(),
                'reference_type' => $evenement->reference_type,
                'reference_id' => $evenement->reference_id,
                'detail' => null,
                'motif' => null,
                'fiable' => true,
                'calcul' => ['periode' => $periode, 'tarif' => $colonneTarif],
            ];

            if ($type === 'commission_librairie') {
                $taux = $contrat->taux_commission_librairie;
                $assiette = (int) ($evenement->montant_assiette ?? 0);
                $ligne['calcul']['taux'] = $taux;
                $ligne['calcul']['assiette'] = $assiette;
                $ligne['montant'] = $taux === null ? null : (int) round($assiette * $taux / 100);
                $ligne['libelle'] = ($evenement->libelle ?? 'Vente').' — commission '.$taux.' %';
            } else {
                $tarif = $tarif = $colonneTarif === null ? null : $contrat->{$colonneTarif};
                $ligne['montant'] = $tarif;
                $ligne['prix_unitaire'] = $tarif;
                $ligne['libelle'] = ($ligne['libelle'] ?: $this->libelleType($type));
            }

            if ($ligne['montant'] !== null && $ligne['montant'] > 0) {
                $lignes->push($ligne);
            }
        }

        // 2) Abonnements mensuels (nombre de comptes actifs sur la période).
        foreach (self::ABONNEMENTS as $type => $definition) {
            $tarif = $contrat->{$definition['tarif']};

            if ($tarif === null || $tarif <= 0 || $this->exonere($contrat, $type)) {
                continue;
            }

            $comptage = $this->compteComptes($cabinetId, $definition['roles'], $debut, $fin);

            if ($comptage['total'] === 0) {
                continue;
            }

            $lignes->push([
                'type' => $type,
                'sens' => LigneFactureCabinet::SENS_DEBIT,
                'libelle' => $this->libelleType($type).' : '.$comptage['total'].' compte'.($comptage['total'] > 1 ? 's' : ''),
                'quantite' => $comptage['total'],
                'prix_unitaire' => $tarif,
                'montant' => $tarif * $comptage['total'],
                'source' => 'auto',
                'evenement_facturable_id' => null,
                'reference_type' => 'compte',
                'reference_id' => $type,
                'detail' => ['ids' => $comptage['ids']],
                'motif' => null,
                'fiable' => $comptage['fiable'],
                'calcul' => [
                    'periode' => $periode,
                    'tarif' => $definition['tarif'],
                    'comptes' => $comptage['total'],
                    'reconstitution' => $comptage['fiable'] ? 'exacte' : 'approximative',
                ],
            ]);
        }

        // 3) Anniversaires de création du cabinet (Q-A18).
        $anniversaireBase = Cabinet::query()
            ->where('id', $cabinetId)
            ->value('created_at');

        if ($anniversaireBase !== null) {
            $cible = Carbon::parse($anniversaireBase)->startOfDay()
                ->year((int) $fin->year);

            if ($cible->isBefore($debut)) {
                $cible->addYear();
            }

            if ($cible->between($debut, $fin)) {
                foreach (self::ANNIVERSAIRES as $type => $colonneTarif) {
                    $tarif = $contrat->{$colonneTarif};

                    if ($tarif === null || $tarif <= 0 || $this->exonere($contrat, $type)) {
                        continue;
                    }

                    $lignes->push([
                        'type' => $type,
                        'sens' => LigneFactureCabinet::SENS_DEBIT,
                        'libelle' => $this->libelleType($type),
                        'quantite' => 1,
                        'prix_unitaire' => $tarif,
                        'montant' => $tarif,
                        'source' => 'auto',
                        'evenement_facturable_id' => null,
                        'reference_type' => 'anniversaire',
                        'reference_id' => $cible->format('Y'),
                        'detail' => null,
                        'motif' => null,
                        'fiable' => true,
                        'calcul' => ['periode' => $periode, 'tarif' => $colonneTarif, 'anniversaire' => $cible->format('Y-m-d')],
                    ]);
                }
            }
        }

        // 4) Services avec tarif jamais négocié → signal (Q-A17) pour le
        //    super-admin, qui peut ajouter une ligne manuelle s'il veut.
        $manquants = $this->tarifsManquants($contrat);

        return [$lignes, $manquants];
    }

    /** Comptage des comptes tenant d'un ou plusieurs rôles, actifs sur la période. */
    private function compteComptes(string $cabinetId, array $roles, Carbon $debut, Carbon $fin): array
    {
        \Stancl\Tenancy\Facades\Tenancy::initialize($cabinetId);
        try {
            $utilisateurs = \App\Models\User::query()
                ->where('statut', true)
                ->whereHas('roles', fn ($q) => $q->whereIn('name', $roles))
                ->where('created_at', '<=', $fin->copy()->endOfDay())
                ->orderBy('nom')
                ->get(['id', 'nom', 'prenom']);

            $ids = $utilisateurs->modelKeys();
            $total = count($ids);

            // Reconstitution « actif sur la période passée » : exacte au
            // v1 si la période est récente, approximative sinon (flag Q-A14).
            $fiable = $fin->copy()->addDays(30)->isFuture();
        } finally {
            \Stancl\Tenancy\Facades\Tenancy::end();
        }

        return ['total' => $total, 'ids' => $ids, 'fiable' => $fiable];
    }

    /** Codes de ligne facturables dont le tarif n'est pas négocié (Q-A17). */
    private function tarifsManquants(ContratCabinet $contrat): array
    {
        $manquants = [];

        foreach (self::ABONNEMENTS as $type => $definition) {
            if ($contrat->{$definition['tarif']} === null && ! $this->exonere($contrat, $type)) {
                $manquants[$type] = 'Tarif non renseigné : service non facture (Q-A17).';
            }
        }

        return $manquants;
    }

    /** Vrai si un service du contrat est exonéré pour le cabinet (Q-A17).
     * Le contrat stocke les exonérations par COLONNE de tarif (ex :
     * `abonnement_mensuel_enseignant`) ; le calcul travaille par CODE de
     * ligne (ex : `abonnement_enseignant`) — la traduction est ici. */
    private function exonere(ContratCabinet $contrat, ?string $typeCodeLigne): bool
    {
        if ($typeCodeLigne === null || ! is_array($contrat->exonerations)) {
            return false;
        }

        $colonne = self::CODE_LIGNE_VS_COLONNE_CONTRAT[$typeCodeLigne] ?? $typeCodeLigne;

        return in_array($colonne, $contrat->exonerations, true);
    }

    /** Transaction code de ligne facture ↔ colonne de tarif du contrat. */
    private const CODE_LIGNE_VS_COLONNE_CONTRAT = [
        'frais_creation_contrat' => 'frais_creation_contrat',
        'frais_contrat_actif' => 'frais_contrat_actif',
        'rapport_mensuel' => 'frais_rapport_mensuel',
        'commission_librairie' => 'taux_commission_librairie',
        'abonnement_enseignant' => 'abonnement_mensuel_enseignant',
        'abonnement_parent' => 'abonnement_mensuel_parent',
        'abonnement_eleve' => 'abonnement_mensuel_eleve',
        'abonnement_admin' => 'abonnement_annuel_admin',
        'maintenance_site' => 'maintenance_annuelle_site',
    ];

    public function libelleType(string $type): string
    {
        return self::LIBELLES_TYPES[$type] ?? ucfirst($type);
    }
}
