<?php

namespace App\Modules\Communication\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\FaqSectionAdminResource;
use App\Models\FaqSection;
use App\Modules\Communication\Http\Requests\StoreFaqSectionRequest;
use App\Modules\Communication\Http\Requests\UpdateFaqSectionRequest;
use App\Modules\Communication\Services\FaqService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Backoffice FAQ — sections (backoffice MVP T3.5), sur le service FaqService.
 */
class AdminFaqSectionApiController extends Controller
{
    public function __construct(protected FaqService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 50), 100);
        $sections = $this->service->listSections($perPage);

        return response()->json([
            'data' => FaqSectionAdminResource::collection($sections),
            'meta' => [
                'total' => $sections->total(),
                'per_page' => $sections->perPage(),
                'current_page' => $sections->currentPage(),
                'last_page' => $sections->lastPage(),
            ],
        ]);
    }

    public function store(StoreFaqSectionRequest $request): JsonResponse
    {
        $this->authorize('create', FaqSection::class);

        $data = $request->validated();

        $section = $this->service->createSection($data);

        return response()->json([
            'message' => 'Section FAQ créée.',
            'data' => new FaqSectionAdminResource($section),
        ], 201);
    }

    public function show(FaqSection $faqSection): JsonResponse
    {
        $this->authorize('view', $faqSection);

        return response()->json(['data' => new FaqSectionAdminResource($faqSection)]);
    }

    public function update(UpdateFaqSectionRequest $request, FaqSection $faqSection): JsonResponse
    {
        $this->authorize('update', $faqSection);

        $section = $this->service->updateSection($faqSection, $request->validated());

        return response()->json([
            'message' => 'Section FAQ mise à jour.',
            'data' => new FaqSectionAdminResource($section),
        ]);
    }

    public function destroy(FaqSection $faqSection): JsonResponse
    {
        $this->authorize('delete', $faqSection);

        $this->service->deleteSection($faqSection);

        return response()->json(['message' => 'Section FAQ supprimée.']);
    }
}