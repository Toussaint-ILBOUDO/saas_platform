<?php

namespace App\Jobs;

use App\Mail\CabinetIdentifiants;
use App\Models\Cabinet;
use App\Models\JournalPlateforme;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLDatabaseManager;
use Throwable;

/**
 * Pipeline TenantCreated — dernière étape (T2.4) :
 * créer l'admin du cabinet, notifier les identifiants par email, tracer au
 * journal. En cas d'échec : rollback complet (base + tenants + domains +
 * parametres) pour que le cabinet n'existe nulle part.
 */
class CreerAdminCabinetEtNotifier implements ShouldQueue
{
    public function __construct(public Cabinet $cabinet) {}

    public function handle(): void
    {
        try {
            $this->creerAdminEtNotifier();
        } catch (Throwable $e) {
            $this->rollback();
            JournalPlateforme::ecrire('cabinet.echec', 'error', $this->cabinet, [
                'erreur' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function creerAdminEtNotifier(): void
    {
        if (env('TENANCY_SIMULATE_ECHEC', false)) {
            throw new \RuntimeException('Échec simulé du pipeline (test T2.4).');
        }

        $password = env('CABINET_ADMIN_PASSWORD') ?: Str::password(14);
        // stancl VirtualColumn : email/telephone sont des attributs top-level
        // (sérialisés dans la colonne json « data »).
        $email = $this->cabinet->email ?: "admin@{$this->cabinet->id}.local";

        tenancy()->initialize($this->cabinet);
        try {
            $admin = User::create([
                'nom' => 'Administrateur',
                'prenom' => 'Cabinet',
                'email' => $email,
                'password' => $password,
                'statut' => true,
            ]);
            $admin->assignRole('admin_cabinet');

            // Cible d'impersonation (T2.6) : colonne centrale du tenant.
            $this->cabinet->admin_utilisateur_id = $admin->id;
            $this->cabinet->save();
        } finally {
            tenancy()->end();
        }

        $contexte = [
            'identifiants' => ['email' => $email, 'password' => $password],
        ];

        $emailContact = $this->cabinet->email;
        if ($emailContact) {
            Mail::to($emailContact)->send(new CabinetIdentifiants($this->cabinet, $email, $password));
            $contexte['email_envoye_vers'] = $emailContact;
        }

        JournalPlateforme::ecrire('admin_cabinet.cree', 'info', $this->cabinet, $contexte);
    }

    /**
     * Supprime toute trace du cabinet (base physique + enregistrements centraux).
     * Suppression en SQL direct : éviter l'événement TenantDeleted qui relancerait
     * un DeleteDatabase sur une base déjà supprimée.
     */
    private function rollback(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        $manager = new PostgreSQLDatabaseManager();
        $manager->setConnection('pgsql');

        try {
            $manager->deleteDatabase($this->cabinet);
        } catch (Throwable) {
        }

        DB::table('parametres_cabinet')->where('cabinet_id', $this->cabinet->id)->delete();
        DB::table('domains')->where('tenant_id', $this->cabinet->id)->delete();
        DB::table('tenants')->where('id', $this->cabinet->id)->delete();
    }
}