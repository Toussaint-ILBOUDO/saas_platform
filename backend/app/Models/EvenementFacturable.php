<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * Journal d'une activité facturable d'un cabinet (alimenté depuis les
 * bases tenant par JournalFacturable::capturer). Un événement est
 * consommé au plus une fois : statut `facturee` + FK facture (Q-A13).
 */
class EvenementFacturable extends Model
{
    use CentralConnection;

    protected $table = 'evenements_facturables';

    public const STATUT_DISPONIBLE = 'disponible';
    public const STATUT_FACTUREE = 'facturee';

    public const TYPE_CONTRAT_CREE = 'contrat_cree';
    public const TYPE_CONTRAT_REACTIVE = 'contrat_reactive';
    public const TYPE_RAPPORT_VALIDE = 'rapport_valide';
    public const TYPE_VENTE_LIBRAIRIE = 'vente_librairie';
    public const TYPE_COMPTE_ENSEIGNANT_ACTIVE = 'compte_enseignant_active';
    public const TYPE_COMPTE_PARENT_ACTIVE = 'compte_parent_active';
    public const TYPE_COMPTE_ELEVE_ACTIVE = 'compte_eleve_active';
    public const TYPE_COMPTE_ENSEIGNANT_SUSPENDU = 'compte_enseignant_suspendu';
    public const TYPE_COMPTE_PARENT_SUSPENDU = 'compte_parent_suspendu';
    public const TYPE_COMPTE_ELEVE_SUSPENDU = 'compte_eleve_suspendu';

    protected $guarded = [];

    protected $casts = [
        'date_evenement' => 'date',
        'meta' => 'array',
    ];

    public function facture(): BelongsTo
    {
        return $this->belongsTo(FactureCabinet::class, 'facture_cabinet_id');
    }
}
