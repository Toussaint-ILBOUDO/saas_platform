<?php

namespace App\Modules\Users\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Eleve;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * `GET /api/mes-enfants` — les enfants du parent connecté, ou l'élève lui-même.
 *
 * Tout écran de consultation pédagogique (cahier de texte, factures,
 * objectifs) a besoin de choisir un enfant : sans cet endpoint, chaque écran
 * devrait réinventer la liste, ou pire, emprunter la charge utile d'un
 * autre module — le cahier de texte se retrouvait alors à dépendre du planning
 * pour savoir qui sont ses enfants.
 *
 * `eleves.parent_id` référence `users.id` : le filtre porte donc sur
 * `$request->user()->id`, jamais sur `parentProfil->id` (cf. D-059).
 */
class MesEnfantsApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // L'élève n'a pas d'enfant : il est lui-même l'entrée unique. Il est
        // renvoyé **dans une liste** comme le parent, sinon le frontend
        // devrait traiter deux formes de réponse selon le rôle — ce que la
        // promesse « un seul sélecteur » exclut précisément.
        if ($user->eleve) {
            return response()->json([
                'data' => [$this->presenter($user->eleve)],
            ]);
        }

        abort_if(! $user->parentProfil, 403);

        return response()->json([
            'data' => Eleve::query()
                ->where('parent_id', $user->id)
                ->with(['user', 'classe'])
                ->orderBy('id')
                ->get()
                ->map(fn (Eleve $eleve): array => $this->presenter($eleve))
                ->values(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function presenter(Eleve $eleve): array
    {
        return [
            'id' => $eleve->id,
            'nom' => $eleve->user?->nom,
            'prenom' => $eleve->user?->prenom,
            'classe' => $eleve->classe ? [
                'id' => $eleve->classe->id,
                'nom' => $eleve->classe->nom,
                'sigle' => $eleve->classe->sigle,
            ] : null,
            'compte_actif' => (bool) $eleve->statut && (bool) $eleve->user?->statut,
        ];
    }
}
