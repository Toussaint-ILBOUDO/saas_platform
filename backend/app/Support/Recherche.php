<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Recherche textuelle insensible à la casse **et aux accents**.
 *
 * Deux défauts distincts, tous deux visibles immédiatement par l'utilisateur :
 *
 * 1. PostgreSQL compare `LIKE` de façon **sensible à la casse** : `LIKE '%math%'`
 *    ne trouve pas « Mathématiques ». Taper « math » affiche une liste vide.
 * 2. « Ouédraogo » ne s'écrit pas « ouedraogo » au clavier. Sur ce projet les
 *    noms de famille sont massivement accentués (Ouédraogo, Kaboré, Sawadogo,
 *    Nadié…) : sans repli des accents, la moitié des recherches échoue.
 *
 * La casse se règle avec `LOWER()`. Pour les accents on utilise `translate()`,
 * qui est **intégré à PostgreSQL** : à la différence de `unaccent()`, il ne
 * demande pas d'extension à installer — or `unaccent` n'est pas disponible sur
 * les bases existantes et l'ajouter exigerait une migration. `translate` est
 * toutefois strictement 1:1 : les ligatures (œ, æ) ne sont pas dépliées et sont
 * donc laissées telles quelles.
 *
 * Le repli se fait dans les deux sens : le terme saisi est normalisé en PHP,
 * la colonne en SQL. Unaccentuer côté PHP uniquement ne suffirait pas, la
 * colonne restant accentuée en base.
 */
final class Recherche
{
    /**
     * Accents repliés sur leur lettre de base, par groupe de même substitut.
     * Regroupés pour que la table reste lisible et vérifiable d'un coup d'œil.
     *
     * @var array<string, string>
     */
    private const ACCENTS = [
        'àâä' => 'aaa',
        'ç' => 'c',
        'èéêë' => 'eeee',
        'îï' => 'ii',
        'ôõö' => 'ooo',
        'ùûü' => 'uuu',
        'ÿ' => 'y',
        'ñ' => 'n',
    ];

    /** Expression SQL normalisant une colonne : minuscules, sans accents. */
    private static function colonneSql(string $colonne): string
    {
        $de = 'LOWER(' . $colonne . ')';

        if (self::supporteTranslate()) {
            return 'translate(' . $de . ', ' . self::echapperSql(self::sources()) . ', ' . self::echapperSql(self::cibles()) . ')';
        }

        return $de;
    }

    /**
     * Filtre la requête sur plusieurs colonnes, en « OU », sans casse ni accent.
     *
     * @param  array<int, string>  $colonnes
     */
    public static function likeInsensible(Builder $query, array $colonnes, string $terme): Builder
    {
        $motif = '%' . self::echapper(self::normaliser($terme)) . '%';

        $sure = array_values(array_filter(
            $colonnes,
            fn (string $colonne): bool => (bool) preg_match('/^[a-z_][a-z0-9_]*$/i', $colonne)
        ));

        if ($sure === [] || $motif === '%%') {
            return $query;
        }

        $expressions = array_map(
            fn (string $colonne): string => self::colonneSql($colonne) . " LIKE ?",
            $sure
        );

        return $query->where(function (Builder $interne) use ($expressions, $motif) {
            $interne->whereRaw('(' . implode(' OR ', $expressions) . ')', array_fill(0, count($expressions), $motif));
        });
    }

    /** Minuscules + accents repliés, côté terme saisi. */
    public static function normaliser(string $terme): string
    {
        $resultat = mb_strtolower(trim($terme));

        foreach (self::ACCENTS as $de => $vers) {
            $resultat = str_replace(mb_str_split($de), $vers, $resultat);
        }

        return $resultat;
    }

    private static function sources(): string
    {
        return implode('', array_keys(self::ACCENTS));
    }

    private static function cibles(): string
    {
        return implode('', array_values(self::ACCENTS));
    }

    /** `translate` existe sur PostgreSQL et MySQL, pas sur SQLite. */
    private static function supporteTranslate(): bool
    {
        return DB::connection()->getDriverName() !== 'sqlite';
    }

    private static function echapperSql(string $valeur): string
    {
        return "'" . str_replace("'", "''", $valeur) . "'";
    }

    /** Neutralise les jokers `%` et `_` tapés par l'utilisateur. */
    private static function echapper(string $terme): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $terme);
    }
}