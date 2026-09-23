<?php

namespace App\Modules\Librairie\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Librairie\Services\LibrairieService;
use App\Modules\Librairie\Services\CommandePdfService;
use App\Models\Commande;

class LibrairieController extends Controller
{
    public function __construct(
        protected LibrairieService $service
    ) {}

    public function index()
    {
        $commandes = $this->service->getCommandesForUser(auth()->id());

        return view('librairie.mes-commandes.index', compact('commandes'));
    }

    public function show(Commande $commande)
    {
        $this->authorize('view', $commande);

        $commande->load('lignes.produit.media');

        return view('librairie.mes-commandes.show', compact('commande'));
    }

    public function pdf(Commande $commande, CommandePdfService $pdfService)
    {
        $this->authorize('view', $commande);

        $commande->load('lignes.produit.media');

        return $pdfService->download($commande);
    }
}
