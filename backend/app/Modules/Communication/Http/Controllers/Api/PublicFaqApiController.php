<?php

namespace App\Modules\Communication\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\FaqSectionPublicResource;
use App\Modules\Communication\Services\FaqService;
use Illuminate\Http\JsonResponse;

class PublicFaqApiController extends Controller
{
    public function __construct(protected FaqService $service)
    {
    }

    public function index(): JsonResponse
    {
        $sections = $this->service->getActiveSectionsWithQuestions();

        return response()->json([
            'data' => FaqSectionPublicResource::collection($sections),
        ]);
    }
}