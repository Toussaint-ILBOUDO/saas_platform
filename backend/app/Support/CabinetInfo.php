<?php

namespace App\Support;

/**
 * Données publiques du cabinet courant (D-014).
 *
 * Source de vérité : JSON `data` du tenant (base centrale).
 * Repli temporaire sur l'ancienne config statique `keduc.cabinet`
 * le temps que les cabinets soient provisionnés (P1 → P2).
 */
class CabinetInfo
{
    public static function all(): array
    {
        $legacy = (array) config('keduc.cabinet', []);

        if (tenancy()->initialized) {
            $tenant = tenant();
            $data = $tenant ? (array) ($tenant->data ?? []) : [];

            return array_replace($legacy, $data);
        }

        return $legacy;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::all()[$key] ?? $default;
    }
}