<?php

namespace App\Support;

use App\Models\Cabinet;

/**
 * Fiche cabinet (D-044) : données essentielles saisies à la création (Landlord)
 * et modifiables par l'admin via « contenu-public » (base tenant
 * `parametres_publics.data.fiche`). Structure unique = contrat avec le frontend.
 */
final class FicheCabinet
{
    /**
     * Squelette complet — les valeurs réelles remplacent ces défauts.
     */
    public static function defaut(): array
    {
        return [
            'identite' => [
                'slogan' => null,
                'directeur' => null,
            ],
            'contact' => [
                'telephone' => null,
                'telephone_2' => null,
                'whatsapp' => null,
                'email' => null,
                'adresse' => null,
                'horaires' => '24h/24, 7j/7',
            ],
            'paiements' => [
                'orange_money' => null,
                'moov_money' => null,
                'wave' => null,
                'cash' => true,
            ],
            'zones' => [
                'pays' => null,
                'devise' => null,
                'localites' => [],
            ],
            'reseaux' => [
                'facebook' => null,
                'tiktok' => null,
                'whatsapp_business' => null,
                'linkedin' => null,
            ],
        ];
    }

    /**
     * Extrait la fiche depuis les champs du formulaire Landlord (création/édition).
     * Champs optionnels : une chaîne vide devient null.
     */
    public static function depuisForm(array $d): array
    {
        $texte = static function (mixed $v): ?string {
            $v = trim((string) $v);

            return $v === '' ? null : $v;
        };
        $url = static function (mixed $v): ?string {
            $v = trim((string) $v);

            return $v === '' ? null : $v;
        };

        return array_replace_recursive(self::defaut(), [
            'identite' => [
                'slogan' => $texte($d['slogan'] ?? null),
                'directeur' => $texte($d['directeur'] ?? null),
            ],
            'contact' => [
                'telephone' => $texte($d['telephone'] ?? null),
                'telephone_2' => $texte($d['telephone_2'] ?? null),
                'whatsapp' => $texte($d['whatsapp'] ?? null),
                'email' => $texte($d['email'] ?? null),
                'adresse' => $texte($d['adresse'] ?? null),
                'horaires' => $texte($d['horaires'] ?? null),
            ],
            'paiements' => [
                'orange_money' => $texte($d['orange_money'] ?? null),
                'moov_money' => $texte($d['moov_money'] ?? null),
                'wave' => $texte($d['wave'] ?? null),
                'cash' => isset($d['cash']) && (bool) $d['cash'],
            ],
            'zones' => [
                'pays' => $texte($d['pays'] ?? null),
                'devise' => $texte($d['devise'] ?? null),
                'localites' => collect(explode("\n", (string) ($d['localites'] ?? '')))
                    ->map(fn ($ligne) => trim($ligne))
                    ->filter(fn ($ligne) => $ligne !== '')
                    ->values()
                    ->all(),
            ],
            'reseaux' => [
                'facebook' => $url($d['facebook'] ?? null),
                'tiktok' => $url($d['tiktok'] ?? null),
                'whatsapp_business' => $url($d['whatsapp_business'] ?? null),
                'linkedin' => $url($d['linkedin'] ?? null),
            ],
        ]);
    }

    /**
     * Fiche depuis le tenant (virtual column « fiche » sérialisée dans tenants.data).
     */
    public static function depuisTenant(Cabinet $cabinet): array
    {
        return array_replace_recursive(self::defaut(), (array) ($cabinet->fiche ?? []));
    }
}