<?php

namespace App\Modules\Pedagogie\Http\Requests;

use App\Models\RapportSection;

/**
 * Réordonnancement des sections du modèle de rapport.
 */
class ReordonnerSectionsRequest extends ReordonnerRapportModeleRequest
{
    protected string $modele = RapportSection::class;
}