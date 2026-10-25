<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * Contrat commercial d'un cabinet (versions immuables une fois validées).
 * Tarifs en entiers FCFA ; null = non négocié, 0 = gratuit négocié (Q-A17).
 */
class ContratCabinet extends Model
{
    use CentralConnection;

    protected $table = 'contrats_cabinet';

    public const STATUT_BROUILLON = 'brouillon';
    public const STATUT_VALIDE = 'valide';
    public const STATUT_ARCHIVE = 'archive';

    /** Codes de lignes de facture exonérables (Q-A17). */
    public const SERVICES = [
        'frais_creation_contrat',
        'frais_contrat_actif',
        'abonnement_enseignant',
        'abonnement_parent',
        'abonnement_eleve',
        'rapport_mensuel',
        'commission_librairie',
        'abonnement_admin',
        'maintenance_site',
    ];

    protected $guarded = [];

    protected $casts = [
        'exonerations' => 'array',
        'taux_commission_librairie' => 'float',
        'taux_commission_autres' => 'float',
        'date_effet' => 'date',
        'date_fin_validite' => 'date',
        'valide_le' => 'datetime',
    ];

    public function factures(): HasMany
    {
        return $this->hasMany(FactureCabinet::class, 'cabinet_id', 'cabinet_id');
    }

    /** Un contrat validé (numéro + version) est immuable : dernière version validée par cabinet. */
    public static function chaineValidee(?string $cabinetId): ?self
    {
        return self::query()
            ->where('cabinet_id', (string) $cabinetId)
            ->where('statut', self::STATUT_VALIDE)
            ->orderByDesc('version')
            ->first();
    }

    public function estBrouillon(): bool
    {
        return $this->statut === self::STATUT_BROUILLON;
    }

    public function estValide(): bool
    {
        return $this->statut === self::STATUT_VALIDE;
    }
}
