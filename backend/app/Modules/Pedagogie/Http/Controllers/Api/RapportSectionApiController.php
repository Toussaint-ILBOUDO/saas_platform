<?php

namespace App\Modules\Pedagogie\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RapportElement;
use App\Models\RapportSection;
use App\Modules\Pedagogie\Http\Requests\ReordonnerElementsRequest;
use App\Modules\Pedagogie\Http\Requests\ReordonnerRapportModeleRequest;
use App\Modules\Pedagogie\Http\Requests\ReordonnerSectionsRequest;
use App\Modules\Pedagogie\Http\Requests\StoreRapportElementRequest;
use App\Modules\Pedagogie\Http\Requests\StoreRapportSectionRequest;
use App\Modules\Pedagogie\Http\Requests\UpdateRapportElementRequest;
use App\Modules\Pedagogie\Http\Requests\UpdateRapportSectionRequest;
use App\Modules\Pedagogie\Services\RapportModeleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Modèle de rapport mensuel — configuration par l'administration.
 *
 * L'admin construit le canevas que l'enseignant remplit au dépôt : des
 * sections (blocs) et, dans chaque section, des éléments (questions). Les
 * informations générales et le bilan des activités ne sont pas ici : ils sont
 * automatiques (contrat, période, cahier de texte).
 *
 * Toutes les routes sont réservées au rôle `admin_cabinet` par la route.
 */
class RapportSectionApiController extends Controller
{
    public function __construct(
        private readonly RapportModeleService $modele,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->modele->auFormat(),
        ]);
    }

    public function store(StoreRapportSectionRequest $request): JsonResponse
    {
        $section = $this->modele->creerSection(
            $request->validated('libelle'),
            $request->validated('description'),
        );

        return response()->json([
            'data' => $section->fresh(),
            'message' => 'Section ajoutée.',
        ], Response::HTTP_CREATED);
    }

    public function update(
        UpdateRapportSectionRequest $request,
        RapportSection $section
    ): JsonResponse {
        $section = $this->modele->modifierSection($section, $request->validated());

        return response()->json([
            'data' => $this->modele->auFormat(),
        ]);
    }

    public function destroy(RapportSection $section): JsonResponse
    {
        $this->modele->supprimerSection($section);

        return response()->json([
            'message' => 'Section supprimée.',
        ]);
    }

    /**
     * Réordonne les sections : `ids` dans le nouvel ordre voulu.
     */
    public function reordonnerSections(ReordonnerSectionsRequest $request): JsonResponse
    {
        $this->modele->reordonnerSections($this->ids($request));

        return response()->json([
            'message' => 'Ordre des sections enregistré.',
            'data' => $this->modele->auFormat(),
        ]);
    }

    public function creerElement(StoreRapportElementRequest $request): JsonResponse
    {
        $this->modele->creerElement(
            (int) $request->validated('section_id'),
            $request->validated(),
        );

        return response()->json([
            'data' => $this->modele->auFormat(),
            'message' => 'Question ajoutée à la section.',
        ], Response::HTTP_CREATED);
    }

    public function modifierElement(
        UpdateRapportElementRequest $request,
        RapportElement $element
    ): JsonResponse {
        $this->modele->modifierElement($element, $request->validated());

        return response()->json([
            'data' => $this->modele->auFormat(),
        ]);
    }

    public function supprimerElement(RapportElement $element): JsonResponse
    {
        $this->modele->supprimerElement($element);

        return response()->json([
            'message' => 'Question supprimée.',
        ]);
    }

    /**
     * Réordonne les éléments : `ids` dans le nouvel ordre voulu.
     */
    public function reordonnerElements(ReordonnerElementsRequest $request): JsonResponse
    {
        $this->modele->reordonnerElements($this->ids($request));

        return response()->json([
            'message' => 'Ordre des questions enregistré.',
            'data' => $this->modele->auFormat(),
        ]);
    }

    /**
     * @return array<int, int>
     */
    private function ids(ReordonnerRapportModeleRequest $request): array
    {
        return array_map('intval', (array) $request->input('ids', []));
    }
}