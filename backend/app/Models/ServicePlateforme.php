<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * Catalogue des fonctionnalités facturables de la plateforme (P8, éditable
 * depuis la console super admin). La colonne `colonne_tarif` relie un
 * service à la colonne négociée du contrat commercial ; les descriptions
 * alimentent l'écran du contrat et ses PDF.
 */
class ServicePlateforme extends Model
{
    use CentralConnection;

    protected $table = 'services_plateforme';

    protected $guarded = [];

    protected $casts = [
        'actif' => 'boolean',
    ];

    /** Catégories d'affichage (libellés fr). */
    public const CATEGORIES = [
        'ouverture' => "Frais d'ouverture du compte",
        'abonnement' => 'Abonnements mensuels',
        'annuel' => 'Services annuels',
        'commission' => 'Commissions',
        'ponctuel' => 'Services ponctuels',
    ];

    public function scopeActifs(Builder $query): Builder
    {
        return $query->where('actif', true);
    }

    public function scopeTries(Builder $query): Builder
    {
        return $query->orderBy('categorie')->orderBy('ordre')->orderBy('id');
    }
}
