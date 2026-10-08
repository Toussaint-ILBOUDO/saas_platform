<?php

namespace App\Modules\Pedagogie\Exceptions;

use RuntimeException;

/**
 * Lancée quand les tables du modèle de rapport (`rapport_sections` /
 * `rapport_elements`) sont absentes de la base du cabinet — la migration
 * `tenants:migrate` n'a pas encore été appliquée à ce tenant.
 *
 * Transformée par `bootstrap/app.php` en réponse JSON compréhensible et
 * actionnable, au lieu d'un « Erreur interne du serveur. » sans explication.
 */
class ModeleRapportNonMigreException extends RuntimeException
{
}