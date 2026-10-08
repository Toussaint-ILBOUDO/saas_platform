<?php

namespace App\Modules\Finance\Services;

use Illuminate\Support\Facades\DB;

/**
 * Numérotation atomique des documents financiers (D-048 / §6.5 de la
 * spécification).
 *
 * KEduc calculait les numéros par `count()+1` sur les documents du mois
 * (`FacturationService::genererNumeroFacture`,
 * `BulletinPaieGenerationService::genererNumero`). Deux générations
 * concurrentes obtenaient donc **le même numéro**, et l'unique en base faisait
 * planter la requête ; une suppression faussait la séquence en plus.
 *
 * Ici le numéro est calculé à partir du **maximum** existant — jamais d'un
 * `count()`, qu'une suppression ferait reculer — et la lecture comme
 * l'écriture se font dans la transaction de l'appelant. Deux transactions
 * concurrentes|numérotant le même mois sont sérialisées par un verrou
 * consultatif PostgreSQL, y compris quand le mois n'a encore aucun document
 * (cas où un simple `lockForUpdate()` ne verrouillerait rien).
 */
class NumerotationDocuments
{
    /**
     * Prochain numéro `PREFIXE-YYYYMM-NNNNN` pour une date donnée.
     *
     * À appeler **dans** la transaction de création.
     */
    public function prochain(string $prefixe, string $colonne, \DateTimeInterface $date): string
    {
        $mois = $date->format('Ym');

        $debut = sprintf('%s-%s-', $prefixe, $mois);
        $longueur = strlen($debut);

        /*
        | Sérialisation par verrou consultatif PostgreSQL, sur la paire
        | (type de document, mois).
        |
        | `lockForUpdate()` ne suffit pas : il ne verrouille AUCUNE ligne quand
        | le mois n'a pas encore de document — c'est-à-dire exactement au
        | moment où deuxcabinets facturent pour la première fois le même mois.
        | Les deux transactions lisaient donc un maximum vide, attribuaient
        | 00001, et l'une des deux se faisait rejeter par l'index unique.
        |
        | `pg_advisory_xact_lock` verrouille une clé logique, qu'il existe ou
        | non des lignes : la seconde transaction attend la validation de la
        | première, puis relit le maximum — désormais à jour. Le verrou est
        | rendu à la fin de la transaction (ou en cas de rollback), sans table
        | supplémentaire à maintenir.
        */
        if (DB::transactionLevel() > 0) {
            DB::select(
                'SELECT pg_advisory_xact_lock(hashtext(?))',
                [$debut]
            );
        }

        $dernier = DB::table($this->tablePour($colonne))
            ->where($colonne, 'like', $debut . '%')
            ->orderByDesc($colonne)
            ->lockForUpdate()
            ->value($colonne);

        $sequence = 1;

        if (is_string($dernier) && strlen($dernier) > $longueur) {
            $sequence = ((int) substr($dernier, $longueur)) + 1;
        }

        return sprintf('%s%s', $debut, str_pad((string) $sequence, 5, '0', STR_PAD_LEFT));
    }

    /**
     * La table déduite de la colonne ciblée.
     */
    private function tablePour(string $colonne): string
    {
        return match ($colonne) {
            'numero_facture' => 'factures',
            'numero' => 'bulletins_paie',
            default => throw new \InvalidArgumentException(
                sprintf('Colonne de numérotation non gérée : « %s ».', $colonne)
            ),
        };
    }
}