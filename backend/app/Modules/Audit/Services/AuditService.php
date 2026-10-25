<?php

namespace App\Modules\Audit\Services;

use App\Models\HistoriqueActivite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Journal d'audit du cabinet (M13).
 *
 * Écriture statique (comme `JournalPlateforme::ecrire`) : aucun appel n'est
 * bloqué par l'audit. Trois protections :
 *  - table absente (cabinet non migré, contexte Landlord) → silencieux,
 *    mémoire par nom de connection ;
 *  - échec SQL → `Log::warning` seulement, l'opération métier continue ;
 *  - jamais de données sensibles dans `contexte` (mots de passe, jetons).
 */
class AuditService
{
    /** @var array<string, bool> */
    private static array $tablesConnues = [];

    public static function enregistrer(
        string $action,
        ?string $description = null,
        ?Model $entite = null,
        array $contexte = [],
        string $level = 'info',
    ): void {
        if (! self::tableDisponible()) {
            return;
        }

        try {
            HistoriqueActivite::create([
                'user_id' => auth()->id(),
                'action' => $action,
                'description' => $description !== null ? Str::limit($description, 255, '') : null,
                'level' => $level,
                'entite_type' => $entite !== null ? $entite::class : null,
                'entite_id' => $entite?->getKey(),
                'contexte' => $contexte !== [] ? $contexte : null,
                'ip' => request()?->ip(),
                'user_agent' => Str::limit((string) request()?->userAgent(), 255, ''),
            ]);
        } catch (\Throwable $e) {
            Log::warning("Audit non écrit : {$e->getMessage()}", ['action' => $action]);
        }
    }

    private static function tableDisponible(): bool
    {
        $connection = DB::getDefaultConnection();

        if (! array_key_exists($connection, self::$tablesConnues)) {
            self::$tablesConnues[$connection] = Schema::connection($connection)
                ->hasTable('historique_activites');
        }

        return self::$tablesConnues[$connection];
    }
}
