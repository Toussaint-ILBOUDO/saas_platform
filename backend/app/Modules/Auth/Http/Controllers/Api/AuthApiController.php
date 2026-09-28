<?php

namespace App\Modules\Auth\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Modules\Auth\Http\Requests\Api\ChangerMotDePasseApiRequest;
use App\Modules\Auth\Http\Requests\Api\ConnexionApiRequest;
use App\Modules\Auth\Http\Requests\Api\MotDePasseOublieApiRequest;
use App\Modules\Auth\Http\Requests\Api\ReinitialiserMotDePasseApiRequest;
use App\Modules\Auth\Http\Requests\Api\RoleActifApiRequest;
use App\Modules\Auth\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

/**
 * Authentification API par session (T3.2, décision D8) : mêmes règles que le
 * web (guard `web`, cookie de session), consommée par Angular sur la même base.
 */
class AuthApiController extends Controller
{
    public function __construct(protected AuthService $authService)
    {
    }

    public function connexion(ConnexionApiRequest $request): JsonResponse
    {
        $user = $this->authService->attemptLogin($request->validated());

        if (! $user) {
            return response()->json([
                'message' => 'Email ou mot de passe incorrect.',
                'code' => 'IDENTIFIANTS_INCORRECTS',
            ], 422);
        }

        $motif = $this->authService->motifDeRefusConnexion($user);

        if ($motif !== null) {
            return response()->json([
                'message' => $motif === 'COMPTE_ELEVE_INACTIF'
                    ? 'Compte élève désactivé.'
                    : 'Aucun rôle attribué à ce compte.',
                'code' => $motif,
            ], 403);
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        // Rôle actif : seul rôle → directement actif ; plusieurs → choisi côté
        // frontend puis confirmé via /role-actif.
        $roles = $user->getRoleNames();

        if ($roles->count() === 1) {
            session(['active_role' => $roles->first()]);
        }

        return response()->json([
            'message' => 'Connexion réussie.',
            'user' => new UserResource($user),
        ]);
    }

    public function deconnexion(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->forget('active_role');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Déconnexion réussie.']);
    }

    public function moi(Request $request): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($request->user('web')),
        ]);
    }

    public function roleActif(RoleActifApiRequest $request): JsonResponse
    {
        $user = $request->user('web');

        if (! $user->hasRole($request->input('role'))) {
            return response()->json([
                'message' => 'Rôle invalide ou non autorisé.',
                'code' => 'ROLE_INVALIDE',
            ], 403);
        }

        session(['active_role' => $request->input('role')]);

        return response()->json([
            'message' => 'Rôle actif sélectionné.',
            'active_role' => $request->input('role'),
        ]);
    }

    public function motDePasseOublie(MotDePasseOublieApiRequest $request): JsonResponse
    {
        try {
            Password::broker()->sendResetLink($request->validated());
        } catch (\Throwable $e) {
            // En dev, aucun SMTP (MAIL_MAILER=smtp vers 127.0.0.1:1025) :
            // l'échec d'envoi ne doit jamais remonter en erreur 500. On
            // journalise et on garde la réponse neutre (pas d'énumération).
            report($e);
        }

        // Pas d'énumération de comptes : réponse identique qu'on connaisse
        // l'adresse ou non.
        return response()->json([
            'message' => 'Si cette adresse est associée à un compte, un email de réinitialisation a été envoyé.',
        ]);
    }

    public function reinitialiserMotDePasse(ReinitialiserMotDePasseApiRequest $request): JsonResponse
    {
        $reponse = Password::broker()->reset(
            $request->only('email', 'token', 'password', 'password_confirmation'),
            function ($user, $password) {
                // Cast « hashed » du modèle : on stocke le clair, haché une fois.
                $user->forceFill(['password' => $password])->save();
                Auth::guard('web')->login($user);
                $request = request();
                $request->session()->regenerate();
            }
        );

        if ($reponse !== Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Jeton de réinitialisation invalide ou expiré.',
                'code' => 'JETON_INVALIDE',
            ], 422);
        }

        return response()->json(['message' => 'Mot de passe réinitialisé.']);
    }

    public function changerMotDePasse(ChangerMotDePasseApiRequest $request): JsonResponse
    {
        $user = $request->user('web');

        if (! Hash::check($request->input('mot_de_passe_actuel'), $user->password)) {
            return response()->json([
                'message' => 'Le mot de passe actuel est incorrect.',
                'code' => 'MOT_DE_PASSE_ACTUEL_INCORRECT',
            ], 422);
        }

        $user->forceFill(['password' => $request->input('nouveau_mot_de_passe')])->save();

        return response()->json(['message' => 'Mot de passe modifié.']);
    }
}