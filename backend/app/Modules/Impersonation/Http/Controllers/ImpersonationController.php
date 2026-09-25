<?php

namespace App\Modules\Impersonation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Cabinet;
use App\Models\JournalPlateforme;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Consommation du jeton d'impersonation côté cabinet (T2.6).
 * Jeton à usage unique, émis sur la plateforme via tenancy()->impersonate().
 */
class ImpersonationController extends Controller
{
    private const DUREE_JETON_MINUTES = 5;

    /**
     * Accès à la table centrale des jetons : toujours sur la connexion
     * Landlord ('pgsql'), même quand le tenancy tenant est initialisé.
     */
    private function jetons(): \Illuminate\Database\Query\Builder
    {
        return DB::connection('pgsql')->table('tenant_user_impersonation_tokens');
    }

    public function entrer(Request $request, string $jeton): RedirectResponse
    {
        $data = $this->jetons()->where('token', $jeton)->first();

        abort_unless($data, 404, 'Jeton d\'impersonation invalide ou déjà utilisé.');
        abort_if(((string) $data->tenant_id) !== (string) tenant('id'), 403, 'Jeton non valable pour ce cabinet.');
        abort_if(Carbon::parse($data->created_at)->addMinutes(self::DUREE_JETON_MINUTES)->isPast(), 419, 'Jeton d\'impersonation expiré.');

        $cible = User::find($data->user_id);
        abort_unless($cible, 403, 'Utilisateur cible introuvable.');

        Auth::guard($data->auth_guard)->login($cible);

        $request->session()->put('impersonation', [
            'cabinet_id' => (string) $data->tenant_id,
            'super_admin_id' => (int) $data->super_admin_id,
            'user_id' => (int) $data->user_id,
        ]);

        JournalPlateforme::ecrire('impersonation.debut', 'info', Cabinet::find($data->tenant_id), [
            'super_admin_id' => $data->super_admin_id,
            'user_id' => $data->user_id,
        ]);

        $this->jetons()->where('token', $jeton)->delete();

        return redirect($data->redirect_url ?: '/');
    }

    public function sortir(Request $request): RedirectResponse
    {
        $impersonation = $request->session()->pull('impersonation');

        if ($impersonation) {
            JournalPlateforme::ecrire('impersonation.fin', 'info', Cabinet::find($impersonation['cabinet_id']), [
                'super_admin_id' => $impersonation['super_admin_id'] ?? null,
                'user_id' => $impersonation['user_id'] ?? null,
            ]);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}