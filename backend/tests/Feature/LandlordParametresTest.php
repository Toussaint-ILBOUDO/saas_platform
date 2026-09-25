<?php

namespace Tests\Feature;

use App\Models\ParametresPlateforme;
use App\Models\SuperAdmin;
use Illuminate\Support\Str;
use Tests\TenantTestCase;
use Tests\Traits\InteractsWithCabinets;

/**
 * Interrupteur global d'accès aux écrans web KEduc (T2.10, décision B4) :
 * par défaut le web gelé n'est pas servi aux cabinets ; le Landlord le
 * délivre depuis les paramètres de la plateforme. L'impersonation et la
 * console Landlord n'en dépendent jamais.
 */
class LandlordParametresTest extends TenantTestCase
{
    use InteractsWithCabinets;

    public function test_interrupteur_desactive_par_defaut_bloque_le_web_keduc(): void
    {
        $this->makeCabinet('c1');

        $this->get('http://c1.localhost/')->assertNotFound();

        // La console Landlord reste joignable, quoi qu'il en soit.
        $this->get('http://admin.localhost/admin/login')->assertOk();
    }

    public function test_activation_landlord_serve_le_web_keduc(): void
    {
        $this->makeCabinet('c1');

        $this->actingAs(
            SuperAdmin::create(['nom' => 'Super Admin', 'email' => 'admin@saascd.test', 'password' => 'motdepasse']),
            'landlord'
        );

        $this->put('http://admin.localhost/admin/parametres', [
            'acces_ecrans_web_keduc' => '1',
        ])->assertRedirect(route('landlord.parametres.index'));

        $this->assertTrue((bool) ParametresPlateforme::obtenir(ParametresPlateforme::CLE_ACCES_WEB_KEDUC, false));

        // actingAs('landlord') force le guard par défaut à landlord ; on le
        // restaure à 'web' pour visiter le domaine cabinet côté tenant.
        $this->app['config']->set('auth.defaults.guard', 'web');
        auth()->forgetGuards();

        $this->get('http://c1.localhost/')->assertOk();
    }

    public function test_desactivation_rebloque_le_web_keduc(): void
    {
        $this->makeCabinet('c1');
        ParametresPlateforme::definir(ParametresPlateforme::CLE_ACCES_WEB_KEDUC, true);

        $this->actingAs(
            SuperAdmin::create(['nom' => 'Super Admin', 'email' => 'admin@saascd.test', 'password' => 'motdepasse']),
            'landlord'
        );

        $this->put('http://admin.localhost/admin/parametres', [])->assertRedirect(route('landlord.parametres.index'));

        $this->assertFalse((bool) ParametresPlateforme::obtenir(ParametresPlateforme::CLE_ACCES_WEB_KEDUC, true));
        $this->get('http://c1.localhost/')->assertNotFound();
    }

    public function test_impersonation_independante_de_l_interrupteur(): void
    {
        $this->makeCabinet('c1');

        // Interrupteur OFF : le web est bloqué, mais les routes d'impersonation
        // restent disponibles (sinon le Landlord serait prisonnier).
        ParametresPlateforme::definir(ParametresPlateforme::CLE_ACCES_WEB_KEDUC, false);
        $this->get('http://c1.localhost/impersonation/' . Str::random(64))->assertNotFound();

        // Un jeton valide est toujours consommé (redirection vers le panneau).
        $this->actingAs(
            SuperAdmin::create(['nom' => 'Super Admin', 'email' => 'admin@saascd.test', 'password' => 'motdepasse']),
            'landlord'
        );
        $this->post("http://admin.localhost/admin/cabinets/c1/impersoner")->assertRedirect();
    }
}