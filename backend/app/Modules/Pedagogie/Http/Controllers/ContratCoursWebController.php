<?php

namespace App\Modules\Pedagogie\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ContratCours;
use App\Models\EnseignantMatiere; 
use App\Modules\Pedagogie\Http\Requests\StoreContratCoursRequest;
use App\Modules\Pedagogie\Services\ContratCoursService;
use App\Modules\Pedagogie\Services\ContratCoursQueryService;
use App\Models\Eleve;
use App\Models\EnseignantProfil;
use App\Models\Matiere;
use App\Models\TypeCours;

class ContratCoursWebController extends Controller
{
    public function __construct(
        private readonly ContratCoursService $service,
        private readonly ContratCoursQueryService $queryService
    ) {}

    public function index()
    {
        return view('pedagogie.contrats.index', [
            'contrats' => $this->queryService->paginate()
        ]);
    }

    public function mesCours()
    {
        $user = auth()->user();

        if ($user->hasRole('enseignant') && $user->enseignantProfil) {
            return view('pedagogie.mes-cours.index', [
                'contrats' => $this->queryService->paginateForEnseignant($user->enseignantProfil->id),
            ]);
        }

        if ($user->hasRole('eleve') && $user->eleve) {
            return view('pedagogie.mes-cours.eleve', [
                'contrats' => $this->queryService->paginateForEleve($user->eleve->id),
            ]);
        }

        abort(403);
    }

    public function create()
    {
        return view('pedagogie.contrats.create', [
            'eleves' => Eleve::with('user')->get(),
            'enseignants' => EnseignantProfil::with('user')->get(),
            'matieres' => Matiere::all(),
            'typesCours' => TypeCours::all(),
        ]);
    }

    public function store(StoreContratCoursRequest $request)
    {
        $this->service->create($request->validated());

        return redirect()
            ->route('contrats.index')
            ->with('success', 'Contrat créé avec succès');
    }

    public function show(ContratCours $contrat)
    {
        $contrat->load(['eleve.user', 'typeCours']);

        $user = auth()->user();

        /*
        |--------------------------------------------------------------------------
        | Portée des affectations affichées
        |--------------------------------------------------------------------------
        | - admin / super-admin : TOUTES les affectations (même si ce compte
        |   porte aussi un profil enseignant, il a la vue globale).
        | - enseignant (rôle seul) : uniquement ses propres affectations.
        | - parent / élève : le policy view() garantit déjà que le contrat
        |   concerne leur enfant / eux-mêmes -> toutes les affectations y sont
        |   légitimes.
        |--------------------------------------------------------------------------
        */
        $isAdmin = $user->hasRole('admin') || $user->hasRole('super-admin');
        $enseignant = $user->enseignantProfil;

        if ($enseignant && !$isAdmin) {
            $contrat->load(['affectations' => function ($query) use ($enseignant) {
                $query->where('enseignant_id', $enseignant->id)
                    ->with(['enseignant.user', 'matiere']);
            }]);
        } else {
            $contrat->load(['affectations.enseignant.user', 'affectations.matiere']);
        }

        return view('pedagogie.contrats.show', compact('contrat'));
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