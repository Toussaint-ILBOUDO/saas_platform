<?php

namespace App\Modules\Public\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\EnseignantPublicResource;
use App\Models\User;
use App\Support\SertMediaPublic;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Enseignants publics (P2) : mêmes critères que la page d'accueil KEduc
 * (statut actif, profil chargé) mais en JSON pour le frontend.
 */
class PublicEnseignantApiController extends Controller
{
    use SertMediaPublic;
    /**
     * Liste paginée des enseignants de la vitrine. La page d'accueil n'affiche
     * que 6 cartes (« Voir plus » charge la suite) : `per_page` permet ce chargement
     * progressif ; `meta` (total…) est celui de la pagination Laravel.
     */
    public function index(Request $request): JsonResponse
    {
        $parPage = min(max((int) $request->query('per_page', 6), 1), 50);

        $enseignants = Role::where('name', 'enseignant')->exists()
            ? User::query()
                ->role('enseignant')
                ->with(['media', 'enseignantProfil', 'enseignantProfil.matieres'])
                ->where('statut', true)
                ->latest('id')
                ->paginate($parPage)
            : new LengthAwarePaginator([], 0, $parPage);

        return EnseignantPublicResource::collection($enseignants)->response();
    }

    /**
     * Binaire de la photo d'un enseignant public (route contrôlée : le disque
     * public du cabinet n'est pas exposé en multi-tenant).
     */
    public function photo(int $id): SymfonyResponse
    {
        $enseignant = User::query()
            ->role('enseignant')
            ->where('statut', true)
            ->findOrFail($id);

        return $this->repondreMedia($enseignant->getFirstMedia('photo_profil'));
    }
}