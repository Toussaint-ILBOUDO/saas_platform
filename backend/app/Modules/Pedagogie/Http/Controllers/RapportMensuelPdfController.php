<?php

namespace App\Modules\Pedagogie\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\RapportMensuelEnseignant;
use App\Modules\Pedagogie\Services\RapportMensuelPdfService;
use Illuminate\Http\Response;

class RapportMensuelPdfController extends Controller
{

    public function __construct(
        protected RapportMensuelPdfService $pdfService
    ) {}



    /**
     * Afficher le PDF dans le navigateur.
     */
    public function stream(
        RapportMensuelEnseignant $rapport
    ): Response {

        return $this->pdfService->stream($rapport);
    }




    /**
     * Télécharger le PDF.
     */
    public function download(
        RapportMensuelEnseignant $rapport
    ): Response {

        return $this->pdfService->download($rapport);
    }



    /**
     * Générer et sauvegarder définitivement le PDF.
     */
    public function save(
        RapportMensuelEnseignant $rapport
    ) {

        $path = $this->pdfService->save($rapport);


        return response()->json([

            'message' =>
                'PDF généré avec succès.',

            'path' =>
                $path,

        ]);
    }

}