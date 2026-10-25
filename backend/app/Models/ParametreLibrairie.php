<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * Réglages de la structure commerciale (librairie du cabinet).
 *
 * Table clé/valeur : chaque cabinet possède les siens (multi-tenant), avec
 * des valeurs par défaut définies ici. Deux familles de clés :
 *  - réglages commerciaux (sigle des commandes, frais de livraison) ;
 *  - DÉLÉGATION d'accès : le libraire peut interdire à l'administrateur
 *    général de consulter le tableau de bord, le chiffre d'affaires ou les
 *    commandes clients. Ces restrictions sont lues par les contrôleurs et
 *    les policies : elles ne peuvent pas être contournées par une URL
 *    directe, un appel API ou une requête frontend modifiée.
 */
class ParametreLibrairie extends Model
{
    public const CLE_SIGLE = 'sigle';

    public const CLE_FRAIS_LIVRAISON = 'frais_livraison';

    // Délégation : '1' (par défaut) = l'admin du cabinet y a accès, '0' = refusé.
    public const CLE_ADMIN_DASHBOARD = 'admin_peut_voir_dashboard';
    public const CLE_ADMIN_CA = 'admin_peut_voir_ca';
    public const CLE_ADMIN_COMMANDES = 'admin_peut_voir_commandes';

    public const CLES_BOOLEANENNES = [
        self::CLE_ADMIN_DASHBOARD,
        self::CLE_ADMIN_CA,
        self::CLE_ADMIN_COMMANDES,
    ];

    public const CLES_EDITEES_PAR_LIBRAIRE = [
        self::CLE_SIGLE,
        self::CLE_FRAIS_LIVRAISON,
        self::CLE_ADMIN_DASHBOARD,
        self::CLE_ADMIN_CA,
        self::CLE_ADMIN_COMMANDES,
    ];

    protected $table = 'parametres_librairie';

    protected $fillable = ['cle', 'valeur'];

    /**
     * Lit une valeur, ou la valeur par défaut si la clé est inconnue.
     * `Schema::hasTable` protège les appels survenant pendant les migrations
     * (la table n'existe pas encore au premier backfill de référence).
     */
    public static function lire(string $cle): ?string
    {
        try {
            if (! Schema::hasTable('parametres_librairie')) {
                return self::defaut($cle);
            }

            $valeur = self::where('cle', $cle)->value('valeur');
        } catch (\Throwable) {
            return self::defaut($cle);
        }

        return $valeur === null ? self::defaut($cle) : (string) $valeur;
    }

    public static function ecrire(string $cle, ?string $valeur): void
    {
        self::updateOrCreate(['cle' => $cle], ['valeur' => $valeur]);
    }

    public static function bool(string $cle): bool
    {
        return in_array(self::lire($cle), ['1', 'true', 'oui', 'on'], true);
    }

    public static function defaut(string $cle): ?string
    {
        return match ($cle) {
            self::CLE_ADMIN_DASHBOARD,
            self::CLE_ADMIN_CA,
            self::CLE_ADMIN_COMMANDES => '1',
            self::CLE_FRAIS_LIVRAISON => '0',
            default => null,
        };
    }

    /**
     * Sigle de la structure pour les codes de commande
     * (`SIGLE-AAAAMMJJ-IDENTIFIANT`). Configurable par la librairie,
     * dérivé de l'identifiant du cabinet sinon.
     */
    public static function sigle(): string
    {
        $sigle = self::lire(self::CLE_SIGLE);

        if ($sigle === null || trim($sigle) === '') {
            $slug = function_exists('tenant') && tenant() ? (string) tenant()->id : '';
            $nettoye = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $slug) ?? '');
            $sigle = $nettoye !== '' ? substr($nettoye, 0, 4) : 'CAB';
        }

        return strtoupper(trim($sigle));
    }

    /** Frais de livraison configurés (0 = aucun frais, jamais inventés). */
    public static function fraisLivraison(): float
    {
        return max(0, (float) self::lire(self::CLE_FRAIS_LIVRAISON));
    }
}
