<?php

namespace App\Modules\Pedagogie\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CahierTexte;
use App\Models\Eleve;
use App\Modules\Pedagogie\Http\Requests\ExportCahierTextePdfRequest;
use App\Modules\Pedagogie\Services\CahierTextePdfService;
use Illuminate\Http\Request;

class CahierTextePdfController extends Controller
{
    public function __construct(
        private CahierTextePdfService $service
    ) {}

    /**
     * PDF d’une séance unique
     */
    public function download(CahierTexte $cahier)
    {
        $this->authorize('view', $cahier);

        return $this->service->downloadSingle($cahier);
    }

    /**
     * Formulaire export historique
     */
    public function create(Eleve $eleve)
    {
        $this->authorize('viewHistory', [CahierTexte::class, $eleve->id]);

        return view('pdf.cahiers-textes.form', compact('eleve'));
    }

    /**
     * Génération PDF historique
     */
    public function store(ExportCahierTextePdfRequest $request, Eleve $eleve)
    {
        $this->authorize('viewHistory', [CahierTexte::class, $eleve->id]);

        return $this->service->downloadHistory(
            $eleve,
            $request->date_debut,
            $request->date_fin
        );
    }
}