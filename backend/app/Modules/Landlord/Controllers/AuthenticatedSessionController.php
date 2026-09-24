<?php

namespace App\Modules\Landlord\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        if (auth('landlord')->check()) {
            return view('landlord.dashboard', []);
        }

        return view('landlord.auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'L\'adresse e-mail est requise.',
            'email.email' => 'L\'adresse e-mail est invalide.',
            'password.required' => 'Le mot de passe est requis.',
        ]);

        if (! auth('landlord')->attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('Ces identifiants ne correspondent pas à un compte super-admin.'),
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('landlord.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        auth('landlord')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landlord.login');
    }
}