<?php

namespace App\Modules\Users\Services;

use App\Models\User;
use App\Models\Eleve;
use App\Models\EvenementFacturable;
use App\Modules\Plateforme\Services\JournalFacturable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EleveService
{
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {

            // ======================
            // USER ELEVE
            // ======================
            $user = User::create([
                'nom' => $data['nom'],
                'prenom' => $data['prenom'],
                'email' => $data['email'] ?? null,
                'telephone_whatsapp' => $data['telephone_whatsapp'] ?? null,
                'telephone_appel' => $data['telephone_appel'] ?? null,
                'password' => isset($data['password'])
                    ? Hash::make($data['password'])
                    : Hash::make('password123'), // temporaire
            ]);

            $user->assignRole('eleve');

            // ======================
            // PROFIL ELEVE
            // ======================
            $user->eleve()->create([
                'parent_id' => $data['parent_id'],
                'classe_id' => $data['classe_id'],
                'ecole' => $data['ecole'] ?? null,
                'date_naissance' => $data['date_naissance'] ?? null,
                'lieu_naissance' => $data['lieu_naissance'] ?? null,
                'parent_charge' => $data['parent_charge'] ?? null,
                'etablissement_origine' => $data['etablissement_origine'] ?? null,

                'profession_pere' => $data['profession_pere'] ?? null,
                'profession_mere' => $data['profession_mere'] ?? null,

                'regime_etude' => $data['regime_etude'] ?? null,
                'loisirs_sport' => $data['loisirs_sport'] ?? null,
                'religion_enfant' => $data['religion_enfant'] ?? null,

                'maladies_allergies' => $data['maladies_allergies'] ?? null,
                'interdits_familiaux' => $data['interdits_familiaux'] ?? null,

                'boisson_preferee' => $data['boisson_preferee'] ?? null,
                'nourriture_preferee' => $data['nourriture_preferee'] ?? null,

                'autres_precautions' => $data['autres_precautions'] ?? null,
                'autres_observations' => $data['autres_observations'] ?? null,
                'statut' => false,
                
            ]);

            return $user->load('eleve');
        });
    }

    /**
     * Modification de la fiche d'un élève par l'admin du cabinet (M3).
     *
     * Deux tables sont touchées : `users` (identité du compte) et `eleves`
     * (rattachements `parent_id`/`classe_id` + fiche scolaire) — dans une
     * seule transaction, sinon un parent changé et une fiche non, ou l'inverse,
     * laisserait un élève rattaché à un compte qui n'est plus le sien.
     */
    public function update(Eleve $eleve, array $data): Eleve
    {
        return DB::transaction(function () use ($eleve, $data) {
            $eleve->update([
                'parent_id' => $data['parent_id'],
                'classe_id' => $data['classe_id'],
                'ecole' => $data['ecole'] ?? $eleve->ecole,
                'date_naissance' => $data['date_naissance'] ?? $eleve->date_naissance,
                'lieu_naissance' => $data['lieu_naissance'] ?? $eleve->lieu_naissance,
                'parent_charge' => $data['parent_charge'] ?? $eleve->parent_charge,
                'etablissement_origine' => $data['etablissement_origine'] ?? $eleve->etablissement_origine,
                'profession_pere' => $data['profession_pere'] ?? $eleve->profession_pere,
                'profession_mere' => $data['profession_mere'] ?? $eleve->profession_mere,
                'regime_etude' => $data['regime_etude'] ?? $eleve->regime_etude,
                'loisirs_sport' => $data['loisirs_sport'] ?? $eleve->loisirs_sport,
                'religion_enfant' => $data['religion_enfant'] ?? $eleve->religion_enfant,
                'maladies_allergies' => $data['maladies_allergies'] ?? $eleve->maladies_allergies,
                'interdits_familiaux' => $data['interdits_familiaux'] ?? $eleve->interdits_familiaux,
                'boisson_preferee' => $data['boisson_preferee'] ?? $eleve->boisson_preferee,
                'nourriture_preferee' => $data['nourriture_preferee'] ?? $eleve->nourriture_preferee,
                'autres_precautions' => $data['autres_precautions'] ?? $eleve->autres_precautions,
                'autres_observations' => $data['autres_observations'] ?? $eleve->autres_observations,
            ]);

            $this->mettreAJourIdentite($eleve->user, $data);

            return $eleve->fresh(['user', 'parent', 'classe']);
        });
    }

    /**
     * Identité du compte (users) : nom, prénom, email, téléphones et
     * éventuel nouveau mot de passe. `password` n'est repris que s'il est
     * fourni et non vide — sinon le hash existant est conservé tel quel.
     */
    private function mettreAJourIdentite(User $user, array $data): void
    {
        $user->update([
            'nom' => $data['nom'] ?? $user->nom,
            'prenom' => $data['prenom'] ?? $user->prenom,
            'email' => $data['email'] ?? $user->email,
            'telephone_whatsapp' => $data['telephone_whatsapp'] ?? $user->telephone_whatsapp,
            'telephone_appel' => $data['telephone_appel'] ?? $user->telephone_appel,
            'password' => ! empty($data['password'])
                ? Hash::make($data['password'])
                : $user->password,
        ]);
    }

    /**
     * Activation du compte élève : les flags passent à true et, si le
     * formulaire d'activation en fournit un, le mot de passe est (re)posé.
     *
     * `$data` est volontairement optionnel : le même service sert l'activation
     * « création de compte » du parcours legacy (email + mot de passe
     * obligatoires) et la simple réactivation depuis l'admin M3 (aucun champ
     * requis — un compte suspendu conserve ses identifiants).
     *
     * | 🔐 DOUBLE FLAG (AuthService)
     * | La connexion d'un élève exige `eleves.statut` ET `users.statut`
     * | (cf. `motifDeRefusConnexion`) : les DEUX doivent passer à true,
     * | sinon l'élève « activé » reste bloqué au login.
     */
    public function activateAccount(Eleve $eleve, array $data = []): void
    {
        $eleve->update([
            'statut' => true,
        ]);

        $user = $eleve->user;

        $user->update([
            'email' => $data['email'] ?? $user->email,
            'password' => ! empty($data['password'])
                ? Hash::make($data['password'])
                : $user->password,
            'statut' => true,
        ]);

        // P8 : journalisation facturable — compte élève activé (abonnement).
        JournalFacturable::capturer(
            EvenementFacturable::TYPE_COMPTE_ELEVE_ACTIVE,
            'eleve',
            $eleve->id,
            'Compte élève activé',
            meta: ['user_id' => $user->id],
        );
    }

    /**
     * Suspension du compte élève : inverse de l'activation, double flag
     * également — poser un seul des deux laisserait un compte dont l'état
     * dépend de qui l'a touché en dernier (D-…cf. activation).
     *
     * Rien n'est supprimé : l'élève garde ses contrats, cahiers de texte et
     * factures ; il ne peut plus tout simplement se connecter.
     */
    public function suspendreCompte(Eleve $eleve): void
    {
        $eleve->update([
            'statut' => false,
        ]);

        $eleve->user->update([
            'statut' => false,
        ]);

        // P8 : journalisation facturable — compte élève suspendu (borne).
        JournalFacturable::capturer(
            EvenementFacturable::TYPE_COMPTE_ELEVE_SUSPENDU,
            'eleve',
            $eleve->id,
            'Compte élève suspendu',
            meta: ['user_id' => $eleve->user->id],
        );
    }
}