<?php

namespace App\Modules\Finance\Services;

use App\Models\BulletinPaie;
use App\Models\BulletinPaieAjustement;
use App\Models\TypeAjustement;
use Illuminate\Validation\ValidationException;

class BulletinPaieAdjustmentService
{
    public function __construct(
        private BulletinPaieCalculationService $calculator
    ) {}

    /**
     * Ajoute un ajustement (prime ou retenue) à un bulletin.
     */
    public function ajouter(
        BulletinPaie $bulletin,
        array $data
    ): BulletinPaieAjustement {

        if ($bulletin->statut === 'verse') {
            throw ValidationException::withMessages([
                'bulletin' => 'Impossible de modifier un bulletin déjà versé.',
            ]);
        }

        $ajustement = BulletinPaieAjustement::create([
            'bulletin_paie_id'    => $bulletin->id,
            'type_ajustement_id'  => $data['type_ajustement_id'],
            'type'                => $data['type_ajustement_id']
                ? $this->determinerType($data['type_ajustement_id'])
                : ($data['type'] ?? 'prime'),
            'libelle'             => $data['libelle'],
            'montant'             => $data['montant'],
        ]);

        $this->calculator->recalculerTotaux($bulletin->fresh());

        return $ajustement;
    }

    /**
     * Modifie un ajustement existant.
     */
    public function modifier(
        BulletinPaieAjustement $ajustement,
        array $data
    ): BulletinPaieAjustement {

        $bulletin = $ajustement->bulletin;

        if ($bulletin->statut === 'verse') {
            throw ValidationException::withMessages([
                'bulletin' => 'Impossible de modifier un bulletin déjà versé.',
            ]);
        }

        $ajustement->update([
            'type_ajustement_id' => $data['type_ajustement_id'] ?? $ajustement->type_ajustement_id,
            'type'    => isset($data['type_ajustement_id'])
                ? $this->determinerType($data['type_ajustement_id'])
                : ($data['type'] ?? $ajustement->type),
            'libelle' => $data['libelle'],
            'montant' => $data['montant'],
        ]);

        $this->calculator->recalculerTotaux($bulletin->fresh());

        return $ajustement;
    }

    /**
     * Supprime un ajustement.
     */
    public function supprimer(
        BulletinPaieAjustement $ajustement
    ): void {

        $bulletin = $ajustement->bulletin;

        if ($bulletin->statut === 'verse') {
            throw ValidationException::withMessages([
                'bulletin' => 'Impossible de modifier un bulletin déjà versé.',
            ]);
        }

        $ajustement->delete();

        $this->calculator->recalculerTotaux($bulletin->fresh());
    }

    /**
     * Détermine le type legacy (prime/retenue) à partir d'un TypeAjustement.
     */
    private function determinerType(int $typeAjustementId): string
    {
        $type = TypeAjustement::find($typeAjustementId);

        if ($type && $type->direction === 'debit') {
            return 'retenue';
        }

        return 'prime';
    }
}
