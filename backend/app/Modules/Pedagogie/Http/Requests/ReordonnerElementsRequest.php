<?php

namespace App\Modules\Pedagogie\Http\Requests;

use App\Models\RapportElement;

/**
 * Réordonnancement des éléments (questions) d'une section.
 */
class ReordonnerElementsRequest extends ReordonnerRapportModeleRequest
{
    protected string $modele = RapportElement::class;
}