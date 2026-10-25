<?php

namespace App\Modules\Auth\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\AffectationEnseignant;
use App\Models\Eleve;
use App\Models\User;
use App\Modules\Auth\Http\Requests\Api\UpdateEleveApiRequest;
use App\Modules\Auth\Http\Requests\Api\UpdateMoiApiRequest;
use App\Modules\Auth\Http\Requests\Api\UpdateProfilEnseignantApiRequest;
use App\Modules\Auth\Http\Requests\Api\UpdateProfilParentApiRequest;
use App\Support\SertMediaPublic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Mon profil (API, session web) : identité du compte, photo, et les blocs
 * de profil propres au rôle actif (enseignant / parent / élève).
 *
 * Un profil élève peut être modifié par l'élève lui-même, son parent, un
 * enseignant affecté à son cours ou l'administration du cabinet.
 */
class ProfilApiController extends Controller
{
    use SertMediaPublic;

    /** Chemin public de la photo de l'utilisateur connecté. */
    public const URL_PHOTO = '/api/auth/moi/photo';

    /* ---------------------------------------------------------------
       Compte (User)
       --------------------------------------------------------------- */

    public function updateMoi(UpdateMoiApiRequest $request): JsonResponse
    {
        $user = $request->user('web');
        $user->fill($request->validated());
        $user->save();

        return response()->json([
            'message' => 'Profil mis à jour.',
            'user' => new UserResource($user->fresh()->load('eleve')),
        ]);
    }

    /**
     * Enregistre la photo de profil (Spatie, collection `photo_profil`).
     * Le fichier est stocké sur le disque public du cabinet ; l'URL de
     * restitution est une route contrôlée (`/api/auth/moi/photo`), le
     * disque public tenant n'étant pas exposé en multi-tenant.
     */
    public function updatePhoto(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'max:2048'],
        ], [
            'photo.required' => 'Une photo est obligatoire.',
            'photo.image' => 'Le fichier doit être une image.',
            'photo.max' => 'La photo ne doit pas dépasser 2 Mo.',
        ]);

        $user = $request->user('web');

        $user->addMedia($request->file('photo'))
            ->preservingOriginal()
            ->toMediaCollection('photo_profil');

        return response()->json([
            'message' => 'Photo mise à jour.',
            'photo_url' => self::URL_PHOTO.'?v='.time(),
        ]);
    }

    public function destroyPhoto(Request $request): JsonResponse
    {
        $request->user('web')->clearMediaCollection('photo_profil');

        return response()->json([
            'message' => 'Photo supprimée.',
            'photo_url' => null,
        ]);
    }

    /** Binaire de la photo de l'utilisateur connecté (route contrôlée). */
    public function photo(Request $request): SymfonyResponse
    {
        $user = $request->user('web');

        return $this->repondreMedia($user->getFirstMedia('photo_profil'));
    }

    /* ---------------------------------------------------------------
       Blocs de profil selon le rôle actif
       --------------------------------------------------------------- */

    public function profils(Request $request): JsonResponse
    {
        $user = $request->user('web')->load([
            'enseignantProfil.matieres',
            'parentProfil',
            'eleve.classe',
        ]);

        $role = session('active_role') ?: $user->getRoleNames()->first();

        $donnees = ['role' => $role];

        if ($role === 'enseignant') {
            $profil = $user->enseignantProfil;
            $donnees['enseignant'] = [
                'numero_orange_money' => $profil?->numero_orange_money,
                'diplome_max' => $profil?->diplome_max,
                'lieu_de_service' => $profil?->lieu_de_service,
                'domicile' => $profil?->domicile,
                'matieres' => $profil
                    ? $profil->matieres->pluck('id')->values()
                    : collect(),
                'matieres_libelles' => $profil
                    ? $profil->matieres->sortBy('nom')->pluck('nom')->values()
                    : collect(),
            ];
        } elseif ($role === 'parent') {
            $profil = $user->parentProfil;
            $donnees['parent'] = [
                'adresse_domicile' => $profil?->adresse_domicile,
                'profession' => $profil?->profession,
                'nombre_enfants' => $profil?->nombre_enfants ?? 0,
            ];
            $donnees['enfants'] = Eleve::query()
                ->where('parent_id', $user->id)
                ->with('classe')
                ->orderBy('id')
                ->get()
                ->map(fn (Eleve $e) => [
                    'id' => $e->id,
                    'nom_complet' => trim(($e->user->prenom ?? '').' '.($e->user->nom ?? '')),
                    'classe' => $e->classe?->nom,
                    'ecole' => $e->ecole,
                ])
                ->values();
        } elseif ($role === 'eleve') {
            $donnees['eleve'] = $user->eleve
                ? $this->resenterEleve($user->eleve)
                : null;
        }

        return response()->json(['data' => $donnees]);
    }

    public function updateEnseignant(UpdateProfilEnseignantApiRequest $request): JsonResponse
    {
        $user = $request->user('web');
        $data = $request->validated();

        $profil = $user->enseignantProfil()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'numero_orange_money' => $data['numero_orange_money'] ?? null,
                'diplome_max' => $data['diplome_max'] ?? null,
                'lieu_de_service' => $data['lieu_de_service'] ?? null,
                'domicile' => $data['domicile'] ?? null,
            ]
        );

        if (array_key_exists('matieres', $data)) {
            $profil->matieres()->sync($data['matieres'] ?? []);
        }

        return response()->json([
            'message' => 'Profil enseignant mis à jour.',
        ]);
    }

    public function updateParent(UpdateProfilParentApiRequest $request): JsonResponse
    {
        $user = $request->user('web');
        $data = $request->validated();

        $user->parentProfil()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'adresse_domicile' => $data['adresse_domicile'] ?? null,
                'profession' => $data['profession'] ?? null,
                'nombre_enfants' => $data['nombre_enfants'] ?? 0,
            ]
        );

        return response()->json([
            'message' => 'Profil parent mis à jour.',
        ]);
    }

    /* ---------------------------------------------------------------
       Fiche élève (droits partagés : élève, parent, enseignant, admin)
       --------------------------------------------------------------- */

    /** Fiche complète d'un élève (mêmes droits que la modification). */
    public function showEleve(Request $request, Eleve $eleve): JsonResponse
    {
        $user = $request->user('web');

        if (! $this->peutModifierEleve($user, $eleve)) {
            return response()->json([
                'message' => 'Vous n\'êtes pas autorisé à consulter la fiche de cet élève.',
                'code' => 'ACCES_REFUSE',
            ], 403);
        }

        return response()->json([
            'eleve' => $this->resenterEleve($eleve->load('classe')),
        ]);
    }

    public function updateEleve(UpdateEleveApiRequest $request, Eleve $eleve): JsonResponse
    {
        $user = $request->user('web');

        if (! $this->peutModifierEleve($user, $eleve)) {
            return response()->json([
                'message' => 'Vous n\'êtes pas autorisé à modifier la fiche de cet élève.',
                'code' => 'ACCES_REFUSE',
            ], 403);
        }

        $eleve->fill($request->validated());
        $eleve->save();

        return response()->json([
            'message' => 'Fiche élève mise à jour.',
            'eleve' => $this->resenterEleve($eleve->fresh()->load('classe')),
        ]);
    }

    /**
     * Droits d'édition d'une fiche élève : l'élève lui-même (compte lié),
     * son parent, un enseignant affecté à l'un de ses contrats de cours,
     * ou l'administration du cabinet.
     */
    protected function peutModifierEleve(User $user, Eleve $eleve): bool
    {
        if ($user->hasRole('admin_cabinet')) {
            return true;
        }

        if ((int) $eleve->parent_id === (int) $user->id) {
            return true;
        }

        if ($eleve->user_id !== null && (int) $eleve->user_id === (int) $user->id) {
            return true;
        }

        if ($user->hasRole('enseignant') && $user->enseignantProfil !== null) {
            return AffectationEnseignant::query()
                ->where('enseignant_id', $user->enseignantProfil->id)
                ->whereHas('contrat', fn ($q) => $q->where('eleve_id', $eleve->id))
                ->exists();
        }

        return false;
    }

    protected function resenterEleve(Eleve $eleve): array
    {
        return [
            'id' => $eleve->id,
            'classe_id' => $eleve->classe_id,
            'classe' => $eleve->classe?->nom,
            'ecole' => $eleve->ecole,
            'date_naissance' => $eleve->date_naissance?->toDateString(),
            'lieu_naissance' => $eleve->lieu_naissance,
            'parent_charge' => $eleve->parent_charge,
            'etablissement_origine' => $eleve->etablissement_origine,
            'profession_pere' => $eleve->profession_pere,
            'profession_mere' => $eleve->profession_mere,
            'regime_etude' => $eleve->regime_etude,
            'loisirs_sport' => $eleve->loisirs_sport,
            'religion_enfant' => $eleve->religion_enfant,
            'maladies_allergies' => $eleve->maladies_allergies,
            'interdits_familiaux' => $eleve->interdits_familiaux,
            'boisson_preferee' => $eleve->boisson_preferee,
            'nourriture_preferee' => $eleve->nourriture_preferee,
            'autres_precautions' => $eleve->autres_precautions,
            'autres_observations' => $eleve->autres_observations,
        ];
    }
}
