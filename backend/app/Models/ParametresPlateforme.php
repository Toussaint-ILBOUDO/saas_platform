<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * Paramètres globaux de la plateforme (T2.10), clé/valeur en base centrale.
 */
class ParametresPlateforme extends Model
{
    use CentralConnection;

    protected $table = 'parametres_plateforme';

    protected $guarded = [];

    protected $casts = [
        'valeur' => 'json',
    ];

    public const CLE_ACCES_WEB_KEDUC = 'acces_ecrans_web_keduc';

    public static function obtenir(string $cle, mixed $defaut = null): mixed
    {
        return optional(self::where('cle', $cle)->first())->valeur ?? $defaut;
    }

    public static function definir(string $cle, mixed $valeur): void
    {
        self::updateOrCreate(['cle' => $cle], ['valeur' => $valeur]);
    }
}