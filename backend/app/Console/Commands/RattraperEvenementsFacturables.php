<?php

namespace App\Console\Commands;

use App\Models\Commande;
use App\Models\EvenementFacturable;
use App\Modules\Plateforme\Services\JournalFacturable;
use App\Models\Cabinet;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Rattrapage des faits facturables antérieurs au journal (Q-A20).
 *
 * Les services déversent leurs événements dans `evenements_facturables`
 * depuis P8 seulement. Cette commande (idempotente — clé unique
 * {cabinet, type, référence, date}) reconstitue ce qui précède :
 *
 *  - `contrat_cree`       : contrat de cours créé (assiette nulle, tarif fixe) ;
 *  - `rapport_valide`     : rapport mensuel validé (assiette nulle, tarif fixe) ;
 *  - `vente_librairie`    : vente livrée, assiette = montant_total hors
 *                           frais de livraison (Q-A15) ;
 *  - `compte_{role}_active` : compte enseignant/parent/élève actif,
 *                           date de création du compte.
 *
 * Aucune reconstitution de `contrat_reactive` (suspendu→actif) : l'historique
 * des statuts n'existe pas et créer un événement par contrat actif
 * surchargerait les frais de contrat. Les événements reconstitués portent
 * `meta.rattrape = true` (repère « à vérifier » dans l'aperçu de facturation).
 */
class RattraperEvenementsFacturables extends Command
{
    protected $signature = 'facturation:rattraper-evenements
        {--cabinet= : limiter à ce cabinet (id/slug)}
        {--dry-run : simuler et afficher le rapport sans écrire}';

    protected $description = "Reconstitue les événements facturables antérieurs au journal (idempotent, Q-A20)";

    public function handle(): int
    {
        $cabinets = Cabinet::query()->orderBy('id');
        if ($cible = $this->option('cabinet')) {
            $cabinets->whereKey($cible);
        }

        $cabinets = $cabinets->get();
        if ($cabinets->isEmpty()) {
            $this->warn('Aucun cabinet trouvé.');

            return self::SUCCESS;
        }

        $simuler = (bool) $this->option('dry-run');
        $rapport = [];

        foreach ($cabinets as $cabinet) {
            try {
                tenancy()->initialize($cabinet);
            } catch (Throwable $e) {
                $this->error("{$cabinet->id} : tenant non initialisable ({$e->getMessage()})");
                $rapport[] = [$cabinet->id, 'ERREUR tenant', $e->getMessage()];

                continue;
            }

            try {
                $cabinetId = (string) $cabinet->getKey();
                $conte = [
                    'contrat_cree' => $this->rattraperContrats($cabinetId, $simuler),
                    'rapport_valide' => $this->rattraperRapports($cabinetId, $simuler),
                    'vente_librairie' => $this->rattraperVentes($cabinetId, $simuler),
                    'comptes' => $this->rattraperComptes($cabinetId, $simuler),
                ];

                $rapport[] = [$cabinet->id, array_sum($conte), implode(', ', $conte)];
            } catch (Throwable $e) {
                $rapport[] = [$cabinet->id, 'ERREUR', $e->getMessage()];
            } finally {
                tenancy()->end();
            }
        }

        $this->newLine();
        $this->table(['cabinet', 'événements', 'détail'], $rapport);

        if ($simuler) {
            $this->warn('Simulation (--dry-run) : rien n\'a été écrit.');
        }

        return self::SUCCESS;
    }

    /** 1. Contrats de cours créés (quel que soit le statut actuel). */
    private function rattraperContrats(string $cabinetId, bool $simuler): int
    {
        $total = 0;

        $contrats = \DB::table('contrat_cours')
            ->whereNotNull('created_at')
            ->get(['id', 'created_at']);

        foreach ($contrats as $contrat) {
            if ($this->ecrire(
                EvenementFacturable::TYPE_CONTRAT_CREE,
                'contrat',
                $contrat->id,
                $contrat->created_at,
                null,
                $cabinetId,
                $simuler,
            )) {
                $total++;
            }
        }

        return $total;
    }

    /** 2. Rapports mensuels validés. */
    private function rattraperRapports(string $cabinetId, bool $simuler): int
    {
        $total = 0;

        $rapports = \App\Models\RapportMensuelEnseignant::query()
            ->where('statut', 'valide')
            ->whereNotNull('date_validation')
            ->get(['id', 'enseignant_id', 'contrat_cours_id', 'date_validation']);

        foreach ($rapports as $rapport) {
            if ($this->ecrire(
                EvenementFacturable::TYPE_RAPPORT_VALIDE,
                'rapport',
                $rapport->id,
                $rapport->date_validation,
                null,
                $cabinetId,
                $simuler,
                [
                    'enseignant_id' => $rapport->enseignant_id,
                    'contrat_cours_id' => $rapport->contrat_cours_id,
                    'rattrape' => true,
                ],
            )) {
                $total++;
            }
        }

        return $total;
    }

    /** 3. Ventes librairie livrées (Q-A15 : assiette sans frais de livraison). */
    private function rattraperVentes(string $cabinetId, bool $simuler): int
    {
        $total = 0;

        $ventes = Commande::query()
            ->where('statut', Commande::STATUT_LIVREE)
            ->whereNotNull('montant_total')
            ->get(['id', 'reference', 'montant_total', 'updated_at']);

        foreach ($ventes as $vente) {
            if ($this->ecrire(
                EvenementFacturable::TYPE_VENTE_LIBRAIRIE,
                'commande',
                $vente->id,
                $vente->updated_at,
                (int) round((float) $vente->montant_total),
                $cabinetId,
                $simuler,
                ['reference' => $vente->reference, 'rattrape' => true],
            )) {
                $total++;
            }
        }

        return $total;
    }

    /** 4. Comptes enseignants / parents / élèves actifs à ce jour. */
    private function rattraperComptes(string $cabinetId, bool $simuler): int
    {
        $total = 0;

        $comptes = \DB::table('users')
            ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('users.statut', true)
            ->whereIn('roles.name', ['enseignant', 'parent', 'eleve'])
            ->whereNotNull('users.created_at')
            ->select('users.id', 'users.created_at', 'roles.name as role')
            ->get();

        foreach ($comptes as $compte) {
            $type = match ($compte->role) {
                'enseignant' => EvenementFacturable::TYPE_COMPTE_ENSEIGNANT_ACTIVE,
                'parent' => EvenementFacturable::TYPE_COMPTE_PARENT_ACTIVE,
                'eleve' => EvenementFacturable::TYPE_COMPTE_ELEVE_ACTIVE,
                default => null,
            };

            if ($type === null) {
                continue;
            }

            if ($this->ecrire(
                $type,
                'user',
                $compte->id,
                $compte->created_at,
                null,
                $cabinetId,
                $simuler,
                ['rattrape' => true],
            )) {
                $total++;
            }
        }

        return $total;
    }

    /**
     * Écrit l'événement s'il n'existe pas déjà (idempotence Q-A13).
     * En simulation : retourne true si la clé est libre (comptabilisé).
     */
    private function ecrire(
        string $type,
        string $referenceType,
        int|string $referenceId,
        string $date,
        ?int $assiete,
        string $cabinetId,
        bool $simuler,
        array $meta = [],
    ): bool {
        $dateEvenement = Carbon::parse($date)->toDateString();

        $existe = EvenementFacturable::query()
            ->where('cabinet_id', $cabinetId)
            ->where('type', $type)
            ->where('reference_type', $referenceType)
            ->where('reference_id', (string) $referenceId)
            ->where('date_evenement', $dateEvenement)
            ->exists();

        if ($existe) {
            return false;
        }

        if ($simuler) {
            return true;
        }

        JournalFacturable::capturer(
            $type,
            $referenceType,
            $referenceId,
            null,
            $assiete,
            $meta ?: ['rattrape' => true],
            $cabinetId,
            Carbon::parse($date),
        );

        return true;
    }
}
