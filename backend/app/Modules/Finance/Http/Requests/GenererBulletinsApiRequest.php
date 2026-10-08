<?php

namespace App\Modules\Finance\Http\Requests;

/**
 * T7A.9 — Génération des bulletins de paie d'une période (API admin).
 *
 * La génération est un acte d'argent : bornée par la policy `create`. Toutes
 * les règles métier (période ouverte, doublon, heures validées) vivent dans le
 * service.
 */
class GenererBulletinsApiRequest extends BaseBulletinsRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\BulletinPaie::class);
    }
}