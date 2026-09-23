<?php

namespace App\Modules\Communication\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Communication\Enums\ActualiteDestinataire;
use App\Modules\Communication\Services\ActualiteService;
use Illuminate\Http\Request;

class PanelActualiteController extends Controller
{
    public function __construct(
        protected ActualiteService $service
    ) {}

    /**
     * Actualités internes destinées aux rôles connectés de la communauté
     * scolaire (élève, enseignant, parent).
     */
    public function index(Request $request)
    {
        $actualites = $this->service->listInternes(
            $this->destinatairesForUser($request->user())
        );

        return view('communication.actualites.internes', compact('actualites'));
    }

    /**
     * Lecture d'une actualité interne pour l'utilisateur connecté.
     */
    public function show(Request $request, string $slug)
    {
        $actualite = $this->service->getInterneBySlug(
            $slug,
            $this->destinatairesForUser($request->user())
        );

        return view('communication.actualites.internes-show', compact('actualite'));
    }

    /**
     * Rôles métier de l'utilisateur convertis en destinataires d'actualité.
     */
    protected function destinatairesForUser($user): array
    {
        return collect($user->getRoleNames())
            ->map(fn (string $role) => ActualiteDestinataire::fromSpatieRole($role))
            ->filter()
            ->values()
            ->all();
    }
}