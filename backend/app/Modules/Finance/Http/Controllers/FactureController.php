<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Facture;
use App\Modules\Finance\Http\Requests\GenerateFactureRequest;
use App\Modules\Finance\Services\FacturationService;

class FactureController extends Controller
{
    public function __construct(
        private FacturationService $service
    ) {}

    public function index()
    {
        return Facture::with([
            'eleve',
            'contrat',
            'periode',
            'lignes'
        ])->latest()->paginate();
    }

    public function show(Facture $facture)
    {
        return $facture->load([
            'eleve',
            'contrat',
            'periode',
            'lignes'
        ]);
    }

    public function generate(
        GenerateFactureRequest $request
    ) {
        return response()->json(
            $this->service->generate(
                $request->validated()
            ),
            201
        );
    }
}