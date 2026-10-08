<?php

namespace Tests\Feature\Api;

use App\Models\Classe;
use App\Models\Matiere;
use App\Models\Notification;
use App\Models\TypeCours;
use App\Models\User;
use Tests\TenantTestCase;
use Tests\Traits\InteractsWithCabinets;

/**
 * Demande de cours publique -> notification interne de l'admin.
 *
 * Bout en bout : formulaire public → service → dispatcher → notification. Aucun
 * e-mail n'est attendu ici (une demande publique n'a pas vocation à déclencher
 * un mail ; les mails métier restent réservés à l'actualité).
 */
class DemandeCoursNotificationTest extends TenantTestCase
{
    use InteractsWithCabinets;

    public function test_demande_publique_notifie_l_admin_du_cabinet(): void
    {
        $this->makeCabinet('c1');

        tenancy()->initialize('c1');
        $classeId = Classe::create(['nom' => 'Terminale', 'sigle' => 'Tle'])->id;
        $matiereId = Matiere::create(['nom' => 'Mathématiques', 'sigle' => 'MATH'])->id;
        $typeCoursId = TypeCours::create([
            'code' => 'DOM',
            'libelle' => 'Cours à domicile',
            'actif' => true,
        ])->id;
        tenancy()->end();

        $this->postJson('http://c1.localhost/api/public/demandes-cours', [
            'nom_parent' => 'Ouédraogo',
            'prenom_parent' => 'Awa',
            'telephone' => '+226 70 00 00 00',
            'type_cours_id' => $typeCoursId,
            'classe_id' => $classeId,
            'volume_horaire_estime' => 4,
            'matieres' => [$matiereId],
            'message' => 'Je souhaite un soutien en mathématiques.',
        ])->assertStatus(201);

        tenancy()->initialize('c1');
        $admin = User::where('email', 'admin@c1.local')->first();
        $notification = Notification::where('user_id', $admin->id)->first();
        tenancy()->end();

        $this->assertNotNull($notification, 'La demande publique doit notifier l\'admin du cabinet.');
        $this->assertSame('demande_cours', $notification->type);
        $this->assertSame('Nouvelle demande de cours', $notification->titre);
        $this->assertStringContainsString('Awa', $notification->contenu);
        $this->assertFalse($notification->lu);
    }
}