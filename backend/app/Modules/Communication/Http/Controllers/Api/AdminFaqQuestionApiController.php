<?php

namespace App\Modules\Communication\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\FaqQuestionAdminResource;
use App\Models\FaqQuestion;
use App\Models\FaqSection;
use App\Modules\Communication\Http\Requests\StoreFaqQuestionRequest;
use App\Modules\Communication\Http\Requests\UpdateFaqQuestionRequest;
use App\Modules\Communication\Services\FaqService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Backoffice FAQ — questions (backoffice MVP T3.5).
 */
class AdminFaqQuestionApiController extends Controller
{
    public function __construct(protected FaqService $service)
    {
    }

    public function index(Request $request, FaqSection $faqSection): JsonResponse
    {
        $this->authorize('view', $faqSection);

        $perPage = min((int) $request->input('per_page', 50), 100);
        $questions = $this->service->listQuestions($faqSection, $perPage);

        return response()->json([
            'data' => FaqQuestionAdminResource::collection($questions),
            'meta' => [
                'total' => $questions->total(),
                'per_page' => $questions->perPage(),
                'current_page' => $questions->currentPage(),
                'last_page' => $questions->lastPage(),
            ],
        ]);
    }

    public function store(StoreFaqQuestionRequest $request): JsonResponse
    {
        $this->authorize('create', FaqQuestion::class);

        $question = $this->service->createQuestion($request->validated());

        return response()->json([
            'message' => 'Question FAQ créée.',
            'data' => new FaqQuestionAdminResource($question),
        ], 201);
    }

    public function show(FaqQuestion $faqQuestion): JsonResponse
    {
        $this->authorize('view', $faqQuestion);

        return response()->json(['data' => new FaqQuestionAdminResource($faqQuestion)]);
    }

    public function update(UpdateFaqQuestionRequest $request, FaqQuestion $faqQuestion): JsonResponse
    {
        $this->authorize('update', $faqQuestion);

        $question = $this->service->updateQuestion($faqQuestion, $request->validated());

        return response()->json([
            'message' => 'Question FAQ mise à jour.',
            'data' => new FaqQuestionAdminResource($question),
        ]);
    }

    public function destroy(FaqQuestion $faqQuestion): JsonResponse
    {
        $this->authorize('delete', $faqQuestion);

        $this->service->deleteQuestion($faqQuestion);

        return response()->json(['message' => 'Question FAQ supprimée.']);
    }
}