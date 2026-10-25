<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * Ligne type de facture (P8, éditable depuis la console super admin) :
 * briques de facturation réutilisables — libellé, prix de départ,
 * texte explicatif, sens — insérées en un clic dans une facture cabinet.
 */
class LigneTypePlateforme extends Model
{
    use CentralConnection;

    protected $table = 'lignes_types_plateforme';

    protected $guarded = [];

    protected $casts = [
        'actif' => 'boolean',
        'montant' => 'integer',
    ];

    public function scopeActifs(Builder $query): Builder
    {
        return $query->where('actif', true);
    }

    public function scopeTries(Builder $query): Builder
    {
        return $query->orderBy('ordre')->orderBy('id');
    }
}
