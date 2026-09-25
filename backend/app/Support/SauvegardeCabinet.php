<?php

namespace App\Support;

use App\Models\Cabinet;
use RuntimeException;

/**
 * Sauvegarde pg_dump d'une base cabinet (T2.5/T2.8) vers
 * storage/app/backups/<id>-<date>.dump. Jamais de suppression sans sauvegarde.
 */
class SauvegardeCabinet
{
    public function sauvegarder(Cabinet $cabinet): ?string
    {
        if (env('TENANCY_SKIP_BACKUP', false)) {
            return null;
        }

        $repertoire = storage_path('app/backups');
        if (! is_dir($repertoire)) {
            mkdir($repertoire, 0775, true);
        }

        $chemin = $repertoire . '/' . $cabinet->id . '-' . now()->format('Y-m-d-Hi') . '.dump';

        $dsn = config('database.connections.pgsql');
        $cmd = sprintf(
            'PGPASSWORD=%s pg_dump --host=%s --port=%s --username=%s --format=custom --file="%s" "%s" 2>&1',
            escapeshellarg($dsn['password']),
            $dsn['host'],
            $dsn['port'],
            $dsn['username'],
            $chemin,
            config('tenancy.database.prefix') . $cabinet->id
        );

        exec($cmd, $sortie, $code);

        if ($code !== 0) {
            $detail = implode(' | ', $sortie);
            throw new RuntimeException('Sauvegarde impossible (pg_dump absent ou en échec) : ' . $detail);
        }

        return basename($chemin);
    }
}