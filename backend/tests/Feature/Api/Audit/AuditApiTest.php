<?php

namespace Tests\Feature\Api\Audit;

use App\Models\HistoriqueActivite;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TenantTestCase;
use Tests\Traits\InteractsWithCabinets;

/**
 * API Audit — journal d'audit du cabinet (M13).
 *
 * Routes `api/admin/audit*` murées par « auth:web » + « role:admin_cabinet »,
 * servies par domaine (`http://<slug>.localhost/api/...`).
 *
 * Deux axes couverts ici :
 *  - l'ÉCRITURE automatique : une action sensible déclenche une entrée
 *    (`utilisateur.suspendu`, `periode.cloturee`) sans code applicatif
 *    supplémentaire au point d'appel ;
 *  - la LECTURE : 401/403, filtres (action, q, dates), pagination, `actions`.
 */
class AuditApiTest extends TenantTestCase
{
    use InteractsWithCabinets;

    /**
     * Cabinet + admin connu, session ouverte via l'API de connexion.
     */
    private function connecteAdmin(string $slug): void
    {
        $this->makeCabinet($slug);

        tenancy()->initialize($slug);
        User::where('email', "admin@{$slug}.local")
            ->update(['password' => Hash::make('Secret1234')]);
        tenancy()->end();

        $this->postJson("http://{$slug}.localhost/api/auth/connexion", [
            'email' => "admin@{$slug}.local",
            'password' => 'Secret1234',
        ])->assertOk();
    }

    private function api(string $slug, string $chemin = ''): string
    {
        return "http://{$slug}.localhost/api/admin/audit{$chemin}";
    }

    public function test_api_exige_connexion_et_role_admin_cabinet(): void
    {
        $this->makeCabinet('audit1');

        // Non connecté → 401.
        $this->getJson($this->api('audit1'))
            ->assertStatus(401)
            ->assertJson(['code' => 'NON_CONNECTE']);

        // Connecté sans rôle admin_cabinet → 403.
        tenancy()->initialize('audit1');
        $enseignant = User::create([
            'nom' => 'Enseignant',
            'prenom' => 'Paul',
            'email' => 'paul@audit1.local',
            'password' => Hash::make('Secret1234'),
            'statut' => true,
        ]);
        $enseignant->assignRole('enseignant');
        tenancy()->end();

        $this->postJson('http://audit1.localhost/api/auth/connexion', [
            'email' => 'paul@audit1.local',
            'password' => 'Secret1234',
        ])->assertOk();

        $this->getJson($this->api('audit1'))->assertStatus(403);
    }

    public function test_suspension_utilisateur_trace_une_entree_completement(): void
    {
        $this->connecteAdmin('audit2');

        // Un utilisateur cible créé dans le tenant.
        tenancy()->initialize('audit2');
        $cible = User::create([
            'nom' => 'Kouassi',
            'prenom' => 'Jean',
            'email' => 'jean@audit2.local',
            'password' => Hash::make('Secret1234'),
            'statut' => true,
        ]);
        tenancy()->end();

        $this->patchJson("http://audit2.localhost/api/admin/utilisateurs/{$cible->id}/suspendre")
            ->assertOk();

        $entree = HistoriqueActivite::where('action', 'utilisateur.suspendu')->firstOrFail();

        // Qui ?
        $admin = User::where('email', 'admin@audit2.local')->first();
        $this->assertSame($admin->id, $entree->user_id);
        $this->assertNotNull($entree->ip);

        // Quoi ?
        $this->assertSame('warning', $entree->level);
        $this->assertSame(User::class, $entree->entite_type);
        $this->assertSame($cible->id, $entree->entite_id);
        $this->assertStringContainsString('jean@audit2.local', $entree->description);

        // Reste lisible dans l'API.
        $this->getJson($this->api('audit2'))
            ->assertOk()
            ->assertJsonPath('data.0.action', 'utilisateur.suspendu')
            ->assertJsonPath('data.0.level', 'warning')
            ->assertJsonPath('data.0.entite.type', 'User')
            ->assertJsonPath('data.0.entite.id', $cible->id);
    }

    public function test_cloture_periode_trace_un_avertissement_avec_contexte(): void
    {
        $this->connecteAdmin('audit3');

        tenancy()->initialize('audit3');
        $periode = \App\Models\PeriodeComptable::create([
            'label' => 'Octobre 2026',
            'date_debut' => '2026-10-01',
            'date_fin' => '2026-10-31',
            'type' => 'mensuel',
            'statut' => \App\Models\PeriodeComptable::OUVERTE,
        ]);
        tenancy()->end();

        $this->patchJson("http://audit3.localhost/api/finance/periodes/{$periode->id}/close")
            ->assertOk();

        $entree = HistoriqueActivite::where('action', 'periode.cloturee')->firstOrFail();
        $this->assertSame('warning', $entree->level);
        $this->assertSame($periode->id, $entree->entite_id);
        $this->assertStringContainsString('Octobre 2026', $entree->description);
    }

    public function test_index_filtre_par_action_recherche_et_dates(): void
    {
        $this->connecteAdmin('audit4');

        tenancy()->initialize('audit4');
        HistoriqueActivite::create([
            'action' => 'facture.emise',
            'description' => 'Facture F-0001 émise',
            'level' => 'info',
            'created_at' => now(),
        ]);
        HistoriqueActivite::create([
            'action' => 'utilisateur.suspendu',
            'description' => 'Utilisateur x@y.local suspendu',
            'level' => 'warning',
            'created_at' => now()->subDays(10),
        ]);
        tenancy()->end();

        // Filtre par action exacte.
        $this->getJson($this->api('audit4', '?action=utilisateur.suspendu'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.action', 'utilisateur.suspendu');

        // Recherche libre (action ou description, insensible à la casse).
        $this->getJson($this->api('audit4', '?q=F-0001'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.action', 'facture.emise');

        // Fenêtre de dates : du = aujourd'hui exclut l'entrée vieille de 10 j.
        $this->getJson($this->api('audit4', '?du=' . now()->toDateString()))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.action', 'facture.emise');

        // Tri : la plus récente en tête.
        $this->getJson($this->api('audit4'))
            ->assertOk()
            ->assertJsonPath('data.0.action', 'facture.emise');
    }

    public function test_index_pagine_avec_per_page_et_alias_par_page(): void
    {
        $this->connecteAdmin('audit5');

        tenancy()->initialize('audit5');
        foreach (range(1, 3) as $i) {
            HistoriqueActivite::create([
                'action' => 'facture.emise',
                'description' => "Facture F-{$i} émise",
                'created_at' => now(),
            ]);
        }
        tenancy()->end();

        $this->getJson($this->api('audit5', '?per_page=2'))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 3);

        // Alias D-058 : `par_page` fait foi s'il est fourni.
        $this->getJson($this->api('audit5', '?par_page=1'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.per_page', 1);
    }

    public function test_actions_liste_les_actions_distinctes(): void
    {
        $this->connecteAdmin('audit6');

        tenancy()->initialize('audit6');
        HistoriqueActivite::create(['action' => 'periode.reouverte', 'created_at' => now()]);
        HistoriqueActivite::create(['action' => 'periode.reouverte', 'created_at' => now()]);
        HistoriqueActivite::create(['action' => 'facture.emise', 'created_at' => now()]);
        tenancy()->end();

        $this->getJson($this->api('audit6', '/actions'))
            ->assertOk()
            ->assertJsonPath('data.0', 'facture.emise')
            ->assertJsonPath('data.1', 'periode.reouverte');
    }
}
