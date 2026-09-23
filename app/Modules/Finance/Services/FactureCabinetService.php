<?php

namespace App\Modules\Finance\Services;

use App\Models\FactureCabinet;
use App\Models\LigneFactureCabinet;
use App\Models\PaiementCabinet;
use App\Models\TypeCommission;
use App\Models\ContratCours;
use App\Models\Eleve;
use App\Models\Facture;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FactureCabinetService
{
    /**
     * Preview : calcule les commissions sans créer la facture.
     */
    public function calculerPreview(array $periode, array $inputs = []): array
    {
        $dateDebut = $periode['date_debut'];
        $dateFin   = $periode['date_fin'];

        $types = TypeCommission::query()
            ->whereIn('nom_du_type', ['Cours', 'Inscription', 'Vente'])
            ->get()
            ->keyBy('nom_du_type');

        $typeCours       = $types->get('Cours');
        $typeInscription = $types->get('Inscription');
        $typeVente       = $types->get('Vente');

        $tauxCours       = (int) ($inputs['taux_cours'] ?? 2000);
        $tauxInscription = (int) ($inputs['taux_inscription'] ?? 3000);
        $tauxVente       = (float) ($inputs['taux_vente'] ?? 10);

        // Nombre de cours actifs pendant la période
        $totalCours = ContratCours::query()
            ->where('statut', 'actif')
            ->where('date_debut', '<=', $dateFin)
            ->where(function ($q) use ($dateDebut, $dateFin) {
                $q->whereNull('date_fin')
                  ->orWhere('date_fin', '>=', $dateDebut);
            })
            ->count();

        // Nombre d'inscriptions (élèves créés dans la période)
        $totalInscriptions = Eleve::query()
            ->where('created_at', '>=', $dateDebut)
            ->where('created_at', '<=', $dateFin)
            ->count();

        // Nombre de ventes (factures clients payées dans la période)
        $totalVentes = Facture::query()
            ->where('statut_paiement', 'payee')
            ->where('date_paiement', '>=', $dateDebut)
            ->where('date_paiement', '<=', $dateFin)
            ->count();

        // Montants
        $montantCours       = $totalCours * $tauxCours;
        $montantInscriptions = $totalInscriptions * $tauxInscription;
        $montantVentes      = (int) round($totalVentes * $tauxVente);

        $montantTotal = $montantCours + $montantInscriptions + $montantVentes;

        $lignes = [];

        if ($typeCours) {
            $lignes[] = [
                'type_commission_id' => $typeCours->id,
                'type_nom'           => $typeCours->nom_du_type,
                'quantite'           => $totalCours,
                'base_calcul'        => $tauxCours,
                'taux_commission'    => 100,
                'montant'            => $montantCours,
            ];
        }

        if ($typeInscription) {
            $lignes[] = [
                'type_commission_id' => $typeInscription->id,
                'type_nom'           => $typeInscription->nom_du_type,
                'quantite'           => $totalInscriptions,
                'base_calcul'        => $tauxInscription,
                'taux_commission'    => 100,
                'montant'            => $montantInscriptions,
            ];
        }

        if ($typeVente && $totalVentes > 0) {
            $lignes[] = [
                'type_commission_id' => $typeVente->id,
                'type_nom'           => $typeVente->nom_du_type,
                'quantite'           => $totalVentes,
                'base_calcul'        => (int) round($tauxVente),
                'taux_commission'    => 10,
                'montant'            => $montantVentes,
            ];
        }

        return [
            'date_debut'         => $dateDebut,
            'date_fin'           => $dateFin,
            'total_cours'        => $totalCours,
            'total_inscriptions' => $totalInscriptions,
            'total_ventes'       => $totalVentes,
            'lignes'             => $lignes,
            'montant_commission' => $montantCours + $montantInscriptions,
            'montant_total_du'   => $montantTotal,
        ];
    }

    /**
     * Génère une facture cabinet à partir des données calculées.
     */
    public function generer(array $data): FactureCabinet
    {
        return DB::transaction(function () use ($data) {

            $dateDebut = $data['date_debut'];
            $dateFin   = $data['date_fin'];

            // Vérifier qu'il n'y a pas déjà une facture pour cette période
            $existante = FactureCabinet::query()
                ->where('periode_debut', $dateDebut)
                ->where('periode_fin', $dateFin)
                ->exists();

            if ($existante) {
                throw ValidationException::withMessages([
                    'periode' => 'Une facture cabinet existe déjà pour cette période.',
                ]);
            }

            // Calculer le preview
            $preview = $this->calculerPreview(
                ['date_debut' => $dateDebut, 'date_fin' => $dateFin],
                [
                    'taux_cours'       => $data['taux_cours'] ?? 2000,
                    'taux_inscription' => $data['taux_inscription'] ?? 3000,
                    'taux_vente'       => $data['taux_vente'] ?? 10,
                ]
            );

            // Créer la facture
            $facture = FactureCabinet::create([
                'numero'              => FactureCabinet::genererNumero(),
                'periode_debut'       => $dateDebut,
                'periode_fin'         => $dateFin,
                'total_cours'         => $preview['total_cours'],
                'total_ventes'        => $preview['total_ventes'],
                'total_inscriptions'  => $preview['total_inscriptions'],
                'montant_commission'  => $preview['montant_commission'],
                'montant_total_du'    => $preview['montant_total_du'],
                'statut'              => 'en_attente',
                'date_facture'        => now()->toDateString(),
            ]);

            // Créer les lignes
            foreach ($preview['lignes'] as $ligne) {
                LigneFactureCabinet::create([
                    'facture_cabinet_id'  => $facture->id,
                    'type_commission_id'  => $ligne['type_commission_id'],
                    'quantite'            => $ligne['quantite'],
                    'base_calcul'         => $ligne['base_calcul'],
                    'taux_commission'     => $ligne['taux_commission'],
                    'montant'             => $ligne['montant'],
                ]);
            }

            return $facture->load('lignes.typeCommission');
        });
    }

    /**
     * Enregistre un paiement sur une facture cabinet.
     */
    public function enregistrerPaiement(
        FactureCabinet $facture,
        array $data
    ): PaiementCabinet {

        return DB::transaction(function () use ($facture, $data) {

            $paiement = PaiementCabinet::create([
                'facture_cabinet_id' => $facture->id,
                'montant_paye'       => $data['montant_paye'],
                'mode_paiement'      => $data['mode_paiement'],
                'reference_paiement' => $data['reference_paiement'] ?? null,
                'date_paiement'      => $data['date_paiement'],
                'statut'             => 'valide',
            ]);

            // Mettre à jour le statut de la facture
            $montantPaye = $facture->fresh()->montant_paye;

            if ($montantPaye >= $facture->montant_total_du) {
                $facture->update([
                    'statut'       => 'payee',
                    'date_paiement' => $data['date_paiement'],
                ]);
            } elseif ($montantPaye > 0) {
                $facture->update([
                    'statut' => 'partiel',
                ]);
            }

            return $paiement;
        });
    }

    /**
     * Marque un paiement comme validé.
     */
    public function validerPaiement(PaiementCabinet $paiement): PaiementCabinet
    {
        $paiement->update(['statut' => 'valide']);
        $this->recalculerStatutFacture($paiement->factureCabinet);
        return $paiement->fresh();
    }

    /**
     * Annule un paiement.
     */
    public function annulerPaiement(PaiementCabinet $paiement): PaiementCabinet
    {
        $paiement->update(['statut' => 'annule']);
        $this->recalculerStatutFacture($paiement->factureCabinet);
        return $paiement->fresh();
    }

    /**
     * Recalcule le statut d'une facture basé sur ses paiements validés.
     */
    private function recalculerStatutFacture(FactureCabinet $facture): void
    {
        $montantPaye = $facture->fresh()->montant_paye;

        if ($montantPaye >= $facture->montant_total_du) {
            $facture->update(['statut' => 'payee']);
        } elseif ($montantPaye > 0) {
            $facture->update(['statut' => 'partiel']);
        } else {
            $facture->update([
                'statut'        => 'en_attente',
                'date_paiement' => null,
            ]);
        }
    }

    /**
     * Annule une facture cabinet.
     */
    public function annuler(FactureCabinet $facture): FactureCabinet
    {
        return DB::transaction(function () use ($facture) {

            // Annuler tous les paiements
            $facture->paiements()
                ->where('statut', '!=', 'annule')
                ->update(['statut' => 'annule']);

            $facture->update([
                'statut'        => 'annulee',
                'date_paiement' => null,
            ]);

            return $facture->fresh('lignes.typeCommission', 'paiements');
        });
    }
}
