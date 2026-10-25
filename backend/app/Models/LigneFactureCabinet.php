<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * Ligne d'une facture de cabinet : auto (calculée depuis les événements
 * et les tarifs du contrat) ou manuelle (ajoutée par le super-admin).
 */
class LigneFactureCabinet extends Model
{
    use CentralConnection;

    protected $table = 'lignes_facture_cabinet';

    public const SENS_DEBIT = 'debit';
    public const SENS_CREDIT = 'credit';

    public const TYPE_FRAIS_CREATION = 'frais_creation_contrat';
    public const TYPE_FRAIS_CONTRAT_ACTIF = 'frais_contrat_actif';
    public const TYPE_ABONNEMENT_ENSEIGNANT = 'abonnement_enseignant';
    public const TYPE_ABONNEMENT_PARENT = 'abonnement_parent';
    public const TYPE_ABONNEMENT_ELEVE = 'abonnement_eleve';
    public const TYPE_RAPPORT_MENSUEL = 'rapport_mensuel';
    public const TYPE_COMMISSION_LIBRAIRIE = 'commission_librairie';
    public const TYPE_ABONNEMENT_ADMIN = 'abonnement_admin';
    public const TYPE_MAINTENANCE_SITE = 'maintenance_site';
    public const TYPE_LIBRE = 'libre';

    protected $guarded = [];

    protected $casts = [
        'detail' => 'array',
        'calcul' => 'array',
    ];

    public function facture(): BelongsTo
    {
        return $this->belongsTo(FactureCabinet::class, 'facture_cabinet_id');
    }

    public function evenement(): BelongsTo
    {
        return $this->belongsTo(EvenementFacturable::class, 'evenement_facturable_id');
    }
}
