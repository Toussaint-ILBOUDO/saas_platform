<?php

declare(strict_types=1);

namespace App\Modules\Public\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Communication\Services\FaqService;
use App\Modules\Temoignages\Services\TemoignageService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

final class HomeController extends Controller
{
    public function __construct(
        protected FaqService $faqService,
        protected TemoignageService $temoignageService
    ) {}

    public function index(): View
    {
        $enseignants = User::query()
            ->role('enseignant')
            ->with([
                'media',
                'enseignantProfil',
                'enseignantProfil.matieres',
            ])
            ->where('statut', true)
            ->latest('id')
            ->take(10) // par exemple les 10 premiers
            ->get();

        $counts = DB::selectOne("
            SELECT
                (SELECT COUNT(*) FROM enseignant_profils) as nb_enseignants,
                (SELECT COUNT(*) FROM eleves) as nb_eleves,
                (SELECT COUNT(*) FROM users) as nb_users,
                (SELECT COUNT(*) FROM contrat_cours) as nb_contrats,
                (SELECT COUNT(*) FROM parent_profils) as nb_familles
        ");

        return view('publicpages.pages.home', [
            'nb_enseignants' => (int) ($counts->nb_enseignants ?? 0),
            'nb_eleves'       => (int) ($counts->nb_eleves ?? 0),
            'nb_users'        => (int) ($counts->nb_users ?? 0),
            'nb_contrats'     => (int) ($counts->nb_contrats ?? 0),
            'nb_familles'     => (int) ($counts->nb_familles ?? 0),

            'enseignants'     => $enseignants,

            'faqSections'     => $this->faqService->getActiveSectionsWithQuestions(),

            'temoignages'     => $this->temoignageService->topHome(3),
        ]);
    }
}
