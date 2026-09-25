<?php

namespace App\Console\Commands;

use App\Models\Cabinet;
use App\Models\JournalPlateforme;
use App\Support\SauvegardeCabinet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Throwable;

/**
 * Enveloppe de tenants:migrate (T2.8) : migrate les bases de tous les
 * cabinets avec sauvegarde pg_dump préalable et rapport par cabinet
 * (journal {cabinet.migre, cabinet.migre.erreur}).
 */
class MigrerTousLesCabinets extends Command
{
    protected $signature = 'cabinet:migrer-tous
        {--tenant= : migrer uniquement ce cabinet (id/slug)}
        {--force-nosauvegarde : ne pas faire de sauvegarde pg_dump}';

    protected $description = 'Migre les bases de tous les cabinets avec sauvegarde et rapport';

    public function handle(): int
    {
        $sauvegarde = new SauvegardeCabinet();

        $requete = Cabinet::query()->orderBy('id');
        if ($tenant = $this->option('tenant')) {
            $requete->whereKey($tenant);
        }

        $cabinets = $requete->get();

        if ($cabinets->isEmpty()) {
            $this->warn('Aucun cabinet à migrer.');

            return self::SUCCESS;
        }

        $rapport = [];

        foreach ($cabinets as $cabinet) {
            $this->line("Migre « {$cabinet->nom} » ({$cabinet->id})…");

            $sauvegardeFichier = null;
            $code = null;

            try {
                if (! $this->option('force-nosauvegarde')) {
                    $sauvegardeFichier = $sauvegarde->sauvegarder($cabinet);
                }

                $code = Artisan::call('tenants:migrate', ['--tenants' => [$cabinet->id]]);

                JournalPlateforme::ecrire('cabinet.migre', 'info', $cabinet, [
                    'sauvegarde' => $sauvegardeFichier,
                    'artisan' => $code,
                ]);

                $this->info('  OK' . ($sauvegardeFichier ? ' (sauvegarde ' . $sauvegardeFichier . ')' : ''));
                $rapport[] = [$cabinet->id, $cabinet->nom, $sauvegardeFichier ?: '—', 'OK', ''];
            } catch (Throwable $e) {
                JournalPlateforme::ecrire('cabinet.migre.erreur', 'error', $cabinet, [
                    'erreur' => $e->getMessage(),
                ]);

                $this->error('  ERREUR : ' . $e->getMessage());
                $rapport[] = [$cabinet->id, $cabinet->nom, $sauvegardeFichier ?: '—', 'ERREUR', $e->getMessage()];
            }
        }

        $this->newLine();
        $this->table(['id', 'nom', 'sauvegarde', 'résultat', 'détail'], $rapport);

        return self::SUCCESS;
    }
}