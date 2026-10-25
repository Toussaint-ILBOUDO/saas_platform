<?php

namespace App\Modules\Users\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Recherche;
use App\Modules\Users\Http\Requests\ImportEnseignantApiRequest;
use App\Modules\Users\Http\Requests\StoreEnseignantRequest;
use App\Modules\Users\Http\Requests\UpdateEnseignantRequest;
use App\Modules\Users\Http\Resources\EnseignantResource;
use App\Modules\Users\Services\EnseignantService;
use App\Modules\Users\Services\ImportEnseignantCsvService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * API référentiel — enseignants (T7A.2).
 *
 * L'enseignant est un `User` + `EnseignantProfil` : on ne supprime jamais un
 * compte (il est porté par des rapports, bulletins et contrats), donc pas de
 * `destroy`. Le rôle « enseignant » est posé par le service, jamais par la
 * requête (sinon un admin pourrait promouvoir un parent en enseignant).
 */
class EnseignantApiController extends Controller
{
    public function __construct(
        private readonly EnseignantService $service
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $enseignants = User::role('enseignant')
            ->with('enseignantProfil.matieres')
            ->when($request->filled('search'), function ($query) use ($request) {
                Recherche::likeInsensible(
                    $query,
                    ['nom', 'prenom', 'email'],
                    $request->string('search')->toString()
                );
            })
            ->latest()
            ->paginate((int) $request->integer('per_page', $request->integer('par_page', 15)))
            ->withQueryString();

        return EnseignantResource::collection($enseignants);
    }

    public function store(StoreEnseignantRequest $request): JsonResponse
    {
        $enseignant = $this->service->create($request->validated());

        return EnseignantResource::make($enseignant)
            ->toResponse(request())
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(int $enseignant): JsonResponse
    {
        $user = User::role('enseignant')
            ->with('enseignantProfil.matieres')
            ->findOrFail($enseignant);

        return EnseignantResource::make($user)->toResponse(request());
    }

    public function update(UpdateEnseignantRequest $request, int $enseignant): JsonResponse
    {
        $user = $this->service->update($enseignant, $request->validated());

        return EnseignantResource::make($user)->toResponse(request());
    }

    /**
     * Import CSV en masse (M1) : rapport ligne par ligne, import partiel
     * possible (une ligne en erreur n'annule pas les autres).
     */
    public function import(
        ImportEnseignantApiRequest $request,
        ImportEnseignantCsvService $import
    ): JsonResponse {
        $rapport = $import->importer(
            $request->file('fichier'),
            $request->string('password_defaut')->toString()
        );

        $message = $rapport['importees'] === 0
            ? 'Aucune ligne importée.'
            : "{$rapport['importees']} enseignant(s) importé(s).";

        if ($rapport['ignorees'] > 0) {
            $message .= " {$rapport['ignorees']} ligne(s) ignorée(s).";
        }

        return response()->json([
            'message' => $message,
            'data' => $rapport,
        ], $rapport['importees'] > 0 ? Response::HTTP_CREATED : Response::HTTP_OK);
    }

    /**
     * Photo de profil enseignant côté admin (M1) : la même collection media
     * `photo_profil` que l'auto-service `/auth/moi/photo`, donc la photo
     * publique `/api/public/enseignants/{id}/photo` la retrouve sans autre
     * intégration. La route GET reste accessible même pour un compte suspendu :
     * c'est l'admin qui doit pouvoir voir la fiche.
     */
    public function photo(int $enseignant): BinaryFileResponse|JsonResponse
    {
        $user = User::role('enseignant')->findOrFail($enseignant);
        $media = $user->getFirstMedia('photo_profil');

        if ($media === null) {
            return response()->json([
                'message' => 'Aucune photo pour cet enseignant.',
                'code' => 'PHOTO_ABSENTE',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->file($media->getPath(), [
            'Content-Type' => $media->mime_type,
            'Cache-Control' => 'private, max-age=0, must-revalidate',
        ]);
    }

    public function majPhoto(Request $request, int $enseignant): JsonResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'max:2048'],
        ], [
            'photo.required' => 'Une photo est obligatoire.',
            'photo.image' => 'Le fichier doit être une image.',
            'photo.max' => 'La photo ne doit pas dépasser 2 Mo.',
        ]);

        $user = User::role('enseignant')->findOrFail($enseignant);

        $user->addMedia($request->file('photo'))
            ->preservingOriginal()
            ->toMediaCollection('photo_profil');

        return response()->json([
            'message' => 'Photo mise à jour.',
            'photo_url' => "/api/admin/enseignants/{$user->id}/photo?v=" . time(),
        ]);
    }

    public function supprimerPhoto(int $enseignant): JsonResponse
    {
        $user = User::role('enseignant')->findOrFail($enseignant);
        $user->clearMediaCollection('photo_profil');

        return response()->json([
            'message' => 'Photo supprimée.',
            'photo_url' => null,
        ]);
    }
}