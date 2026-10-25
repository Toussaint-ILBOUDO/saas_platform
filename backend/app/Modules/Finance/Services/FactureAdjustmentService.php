<?php

namespace App\Modules\Finance\Services;

use App\Models\Facture;
use App\Models\FactureAjustement;
use App\Models\TypeAjustement;
use Illuminate\Validation\ValidationException;

/**
 * Ajustements d'une facture parent : bonus (crédit) et débits (frais, retenue)
 * ajoutés par l'administration après génération. Le montant total de la
 * facture est recalculé à chaque mouvement, exactement comme pour un bulletin.
 */
class FactureAdjustmentService
{
    /**
     * Ajoute un ajustement à une facture.
     */
    public function ajouter(Facture $facture, array $data): FactureAjustement
    {
        $this->assertModifiable($facture);

        $ajustement = FactureAjustement::create([
            'facture_id' => $facture->id,
            'type_ajustement_id' => $data['type_ajustement_id'],
            'type' => $this->direction($data['type_ajustement_id']),
            'libelle' => $data['libelle'],
            'montant' => $data['montant'],
        ]);

        $this->recalculerTotaux($facture->fresh());

        return $ajustement;
    }

    /**
     * Supprime un ajustement d'une facture.
     */
    public function supprimer(FactureAjustement $ajustement): void
    {
        $facture = $ajustement->facture;

        if (! $facture) {
            return;
        }

        $this->assertModifiable($facture);

        $ajustement->delete();

        $this->recalculerTotaux($facture->fresh());
    }

    /**
     * Recalcule le montant total : cours + frais figés + crédits - débits.
     */
    public function recalculerTotaux(Facture $facture): void
    {
        $facture->loadMissing('lignes', 'ajustements');

        $montantCours = (int) $facture->lignes->sum('montant');

        $credits = (int) $facture->ajustements
            ->where('type', 'credit')
            ->sum('montant');

        $debits = (int) $facture->ajustements
            ->where('type', 'debit')
            ->sum('montant');

        $facture->update([
            'montant_total' => $montantCours
                + (int) $facture->frais_suivi
                + (int) $facture->autres_frais
                - (int) $facture->remise
                + $credits
                - $debits,
        ]);
    }

    /**
     * Direction ('credit'|'debit') portée par le type d'ajustement.
     */
    private function direction(int $typeAjustementId): string
    {
        $type = TypeAjustement::find($typeAjustementId);

        return $type && $type->direction === 'debit' ? 'debit' : 'credit';
    }

    /**
     * Une facture réglée est figée : ses montants sont définitifs.
     */
    private function assertModifiable(Facture $facture): void
    {
        if ($facture->estPayee()) {
            throw ValidationException::withMessages([
                'facture' => 'Impossible de modifier une facture déjà payée.',
            ]);
        }
    }
}
