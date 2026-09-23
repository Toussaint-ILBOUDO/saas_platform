<?php

namespace App\Modules\Pedagogie\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ContratCours;
use App\Models\EnseignantMatiere;
use App\Modules\Pedagogie\Http\Requests\StoreContratCoursRequest;
use App\Modules\Pedagogie\Services\ContratCoursService;
use App\Modules\Pedagogie\Services\ContratCoursQueryService;
use Illuminate\Http\JsonResponse;

class ContratCoursController extends Controller
{
    public function __construct(
        private readonly ContratCoursService $service,
        private readonly ContratCoursQueryService $queryService
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->queryService->paginate()
        ]);
    }

    public function show(ContratCours $contrat): JsonResponse
    {
        return response()->json([
            'data' => $this->queryService->show($contrat)
        ]);
    }

    public function store(StoreContratCoursRequest $request): JsonResponse
    {
        $contrat = $this->service->create($request->validated());

        return response()->json([
            'message' => 'Contrat créé avec succès',
            'data' => $contrat
        ], 201);
    }

    public function enseignantsParMatiere($matiereId)
    {
        $rows = EnseignantMatiere::query()
            ->with('enseignant.user')
            ->where('matiere_id', $matiereId)
            ->get();

        $enseignants = $rows->map(function ($row) {

            if (!$row->enseignant || !$row->enseignant->user) {
                return null;
            }

            return [
                'id' => $row->enseignant->id,
                'nom' => $row->enseignant->user->nom,
                'prenom' => $row->enseignant->user->prenom,
            ];
        })->filter()->values();

        return response()->json($enseignants);
    }
}