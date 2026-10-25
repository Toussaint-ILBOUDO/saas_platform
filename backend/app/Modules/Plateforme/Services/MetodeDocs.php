<?php

namespace App\Modules\Plateforme\Services;

use App\Models\Cabinet;
use App\Models\ParametresPlateforme;
use App\Support\FicheCabinet;

/**
 * P8 — Variables d'entête communes aux PDF de la plateforme
 * (facture cabinet, contrat commercial). L'identité porte sur la
 * PLATEFORME (commune à tous les cabinets) — la société éditrice est
 * paramétrée dans `parametres_plateforme` (cle `plateforme`) :
 * nom « Magis Plus Center », tagline, contact ; le cabinet cible est
 * identifié par tenants + fiche (nom / code cabinet / contact).
 */
final class MetodeDocs
{
    public static function variablesPlateforme(?string $cabinetId = null): array
    {
        $cabinet = $cabinetId === null ? null : Cabinet::query()->find($cabinetId);
        $fiche = $cabinet === null ? FicheCabinet::defaut() : FicheCabinet::depuisTenant($cabinet);

        $identite = self::identite();

        return [
            'plateforme_nom' => $identite['nom'],
            'plateforme_tagline' => $identite['tagline'],
            'plateforme_contact' => $identite['contact'],
            'plateforme_logo' => $identite['logo'],
            'cabinet_nom' => $cabinet?->nom ?? 'Cabinet',
            'cabinet_code' => (string) ($cabinet?->getKey() ?? ''),
        ];
    }

    /** Identité éditrice (paramètre éditable, repli sur la config). */
    public static function identite(): array
    {
        try {
            $parametre = ParametresPlateforme::obtenir('plateforme');
        } catch (\Throwable) {
            $parametre = null;
        }

        return [
            'nom' => $parametre['nom'] ?? config('app.name', "Magis Plus Center"),
            'tagline' => $parametre['tagline'] ?? 'Plateforme de gestion de cabinets de soutien scolaire',
            'contact' => $parametre['contact'] ?? 'Magis Plus Center — support commercial',
            'logo' => $parametre['logo'] ?? null,
        ];
    }
}
