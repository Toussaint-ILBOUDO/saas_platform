<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function attemptLogin(array $data): ?User
    {
        $user = User::where('email', $data['email'])->first();

        if (!$user) {
            return null;
        }

        if (!Hash::check($data['password'], $user->password)) {
            return null;
        }

        return $user;
    }

    /**
     * Motif de refus de connexion, ou null si l'utilisateur peut se connecter.
     * Utilisé par l'API (T3.2), miroir des règles du web : élève inactif
     * bloqué, compte sans rôle inutilisable.
     */
    public function motifDeRefusConnexion(User $user): ?string
    {
        if ($user->hasRole('eleve') && (!$user->eleve || !$user->eleve->statut)) {
            return 'COMPTE_ELEVE_INACTIF';
        }

        if ($user->getRoleNames()->isEmpty()) {
            return 'AUCUN_ROLE';
        }

        return null;
    }

    public function login(array $data, Request $request)
    {
        $user = $this->attemptLogin($data);

        if (!$user) {
            return back()
                ->withInput()
                ->withErrors([
                    'email' => 'Email ou mot de passe incorrect.'
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 🔐 BLOQUAGE ÉLÈVE SI INACTIF
        |--------------------------------------------------------------------------
        */
        if (
            $user->hasRole('eleve') &&
            (!$user->eleve || !$user->eleve->statut)
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'email' => 'Compte élève désactivé.'
                ]);
        }

        Auth::login($user);

        $request->session()->regenerate();

        $roles = $user->getRoleNames();

        /*
        |--------------------------------------------------------------------------
        | Aucun rôle
        |--------------------------------------------------------------------------
        */
        if ($roles->isEmpty()) {

            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => 'Aucun rôle attribué à ce compte.'
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Un seul rôle
        |--------------------------------------------------------------------------
        */
        if ($roles->count() === 1) {

            session([
                'active_role' => $roles->first(),
            ]);

            return redirect()->route('dashboard');
        }

        /*
        |--------------------------------------------------------------------------
        | Plusieurs rôles
        |--------------------------------------------------------------------------
        */
        return redirect()->route('role.select');
    }
    public function selectRolePage()
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $roles = $user->getRoleNames();

        /*
        |--------------------------------------------------------------------------
        | Sécurité : fallback si accès direct
        |--------------------------------------------------------------------------
        */
        if ($roles->count() === 1) {

            session([
                'active_role' => $roles->first(),
            ]);

            return redirect()->route('dashboard');
        }

        return view('auth.select-role', [
            'roles' => $roles
        ]);
    }

    public function setRole(string $role)
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        /*
        |--------------------------------------------------------------------------
        | IMPORTANT FIX (cause de ton 403)
        |--------------------------------------------------------------------------
        */
        if (!$user->hasRole($role)) {
            return redirect()
                ->route('role.select')
                ->withErrors([
                    'role' => 'Rôle invalide ou non autorisé.'
                ]);
        }

        session([
            'active_role' => $role
        ]);

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->forget('active_role');

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function hasMultipleRoles(User $user): bool
    {
         return auth()->user()
            ->getRoleNames()
            ->count() > 1;
    }
}