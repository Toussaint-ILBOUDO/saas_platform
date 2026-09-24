<?php

namespace App\Modules\Pedagogie\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pedagogie\Http\Requests\StoreCahierTexteRequest;
use App\Modules\Pedagogie\Services\CahierTexteService;
use Illuminate\Http\Request;

class CahierTexteController extends Controller
{
    public function __construct(
        protected CahierTexteService $service
    ) {}

    public function store(StoreCahierTexteRequest $request)
    {
        $data = $request->validated();

        $cahier = $this->service->create($data);

        return response()->json([
            'message' => 'Séance enregistrée avec succès',
            'data' => $cahier
        ]);
    }

    public function index(Request $request)
    {
        return $this->service->listByAffectation(
            $request->affectation_enseignant_id
        );
    }

    
}