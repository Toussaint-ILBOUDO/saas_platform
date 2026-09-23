<?php

namespace App\Modules\Pedagogie\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Eleve;
use Illuminate\Http\Request;
use App\Modules\Pedagogie\Services\CahierTextePdfService;

class HistoriquePedagogiquePdfController extends Controller
{
    public function __construct(
        protected CahierTextePdfService $pdfService
    ) {}

    public function download(
        Request $request,
        Eleve $eleve
    ) {

        return $this->pdfService
            ->downloadForEleve(
                $eleve,
                $request->date_debut,
                $request->date_fin
            );
    }
}