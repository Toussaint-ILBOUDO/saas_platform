<?php

namespace App\Modules\Pedagogie\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pedagogie\Http\Requests\StoreRapportMensuelRequest;
use App\Modules\Pedagogie\Services\RapportMensuelService;
use Illuminate\Support\Facades\Auth;

class RapportMensuelController extends Controller
{
    public function __construct(
        protected RapportMensuelService $service
    ) {}

    public function store(StoreRapportMensuelRequest $request)
    {
        $rapport = $this->service->generate(
            $request->validated(),
            Auth::id()
        );

        return response()->json([
            'message' => 'Rapport mensuel généré avec succès',
            'data' => $rapport
        ]);
    }
}