<?php

namespace App\Modules\Finance\Http\Requests;

/**
 * T7A.9 — Aperçu des bulletins d'une période (sans rien écrire).
 *
 * L'aperçu consomme les rapports VALIDÉS (D-051) : aucune écriture, il montre
 * ce qui sera généré — enseignants, heures, brut, frais, ajustements, net.
 */
class PreviewBulletinsApiRequest extends BaseBulletinsRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\BulletinPaie::class);
    }
}