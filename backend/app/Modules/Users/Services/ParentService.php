<?php

namespace App\Modules\Users\Services;

use App\Models\User;
use App\Models\EvenementFacturable;
use App\Modules\Plateforme\Services\JournalFacturable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ParentService
{
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {

            $user = User::create([
                'nom' => $data['nom'],
                'prenom' => $data['prenom'],
                'telephone_whatsapp' => $data['telephone_whatsapp'] ?? null,
                'telephone_appel' => $data['telephone_appel'] ?? null,
                'email' => $data['email'] ?? null,
                'password' => Hash::make($data['password']),
            ]);

            $user->assignRole('parent');

            $user->parentProfil()->create([
                'adresse_domicile' => $data['adresse_domicile'] ?? null,
                'profession' => $data['profession'] ?? null,
                'nombre_enfants' => $data['nombre_enfants'] ?? 0,
            ]);

            // P8 : journalisation facturable — compte parent créé
            // (actif dès la création, droit d'abonnement mensuel).
            JournalFacturable::capturer(
                EvenementFacturable::TYPE_COMPTE_PARENT_ACTIVE,
                'user',
                $user->id,
                'Compte parent créé',
            );

            return $user->load('parentProfil');
        });
    }

    /**
     * Modification d'un parent et de son profil par l'admin du cabinet (M3).
     *
     * Le profil est mis à jour ou créé au besoin : un parent hérité d'une
     * ancienne importation peut n'avoir jamais eu de ligne `parent_profils`.
     */
    public function update(User $parent, array $data): User
    {
        return DB::transaction(function () use ($parent, $data) {
            $parent->update([
                'nom' => $data['nom'] ?? $parent->nom,
                'prenom' => $data['prenom'] ?? $parent->prenom,
                'email' => $data['email'] ?? $parent->email,
                'telephone_whatsapp' => $data['telephone_whatsapp'] ?? $parent->telephone_whatsapp,
                'telephone_appel' => $data['telephone_appel'] ?? $parent->telephone_appel,
                'password' => ! empty($data['password'])
                    ? Hash::make($data['password'])
                    : $parent->password,
            ]);

            $profil = $parent->parentProfil;

            $parent->parentProfil()->updateOrCreate([], [
                'adresse_domicile' => $data['adresse_domicile'] ?? $profil?->adresse_domicile,
                'profession' => $data['profession'] ?? $profil?->profession,
                'nombre_enfants' => $data['nombre_enfants'] ?? $profil?->nombre_enfants ?? 0,
            ]);

            return $parent->fresh(['parentProfil', 'enfants.user', 'enfants.classe']);
        });
    }

    /**
     * Refuse de créer un second compte pour un numéro déjà connu.
     *
     * La même famille demande des cours régulièrement ; il faut rattacher la
     * nouvelle demande au parent existant, pas fabriquer un doublon. Le
     * message nomme le compte trouvé pour que l'écran puisse l'afficher tel
     * quel. Partagé par la création depuis une demande de cours et la
     * création directe (`POST /pedagogie/parents`).
     *
     * `$saufUserId` exclut un compte de la recherche : à l'édition (M3), un
     * parent qui garde son propre numéro ne doit pas se voir refuser son
     * propre profil.
     */
    public function refuserSiDejaConnu(?string $whatsapp, ?string $appel, ?int $saufUserId = null): void
    {
        $whatsapp = trim((string) $whatsapp);
        $appel = trim((string) $appel);

        if ($whatsapp === '' && $appel === '') {
            return;
        }

        $existant = User::role('parent')
            ->when(
                $saufUserId !== null,
                fn ($query) => $query->where('id', '!=', $saufUserId)
            )
            ->where(function ($query) use ($whatsapp, $appel): void {
                $query
                    ->when($whatsapp !== '', fn ($q) => $q->where('telephone_whatsapp', $whatsapp))
                    ->when(
                        $appel !== '',
                        fn ($q) => $q->when(
                            $whatsapp !== '',
                            fn ($q2) => $q2->orWhere('telephone_appel', $appel),
                            fn ($q2) => $q2->where('telephone_appel', $appel)
                        )
                    );
            })
            ->first();

        if ($existant) {
            throw ValidationException::withMessages([
                'telephone_whatsapp' => sprintf(
                    'Un parent est déjà enregistré avec ce numéro (%s). Utilisez-le plutôt que d\'en créer un second.',
                    $existant->email ?: 'sans email'
                ),
            ]);
        }
    }
}