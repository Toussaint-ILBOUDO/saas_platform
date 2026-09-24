<?php

namespace App\Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Users\Services\EnseignantService;
use App\Modules\Users\Http\Requests\StoreEnseignantRequest;
use App\Modules\Users\Http\Requests\UpdateEnseignantRequest;
use App\Models\User;
use App\Models\Matiere;

class EnseignantController extends Controller
{
    public function __construct(
        private readonly EnseignantService $service
    ) {}

    public function index()
    {
        $enseignants = User::role('enseignant')
            ->with('enseignantProfil.matieres')
            ->latest()
            ->paginate(15);

        return view(
            'pedagogie.Users.enseignants.index',
            compact('enseignants')
        );
    }

    public function create()
    {
        $matieres = Matiere::where('actif', true)->orderBy('nom')->get();

        return view('pedagogie.Users.enseignants.create', compact('matieres'));
    }

    public function store(StoreEnseignantRequest $request)
    {
        $this->service->create($request->validated());

        return redirect()
            ->route('enseignants.index')
            ->with('success', 'Enseignant créé avec succès');
    }

    public function show($id)
    {
        $enseignant = User::role('enseignant')
            ->with('enseignantProfil.matieres')
            ->findOrFail($id);

        return view('pedagogie.Users.enseignants.show', compact('enseignant'));
    }

    public function edit($id)
    {
        $enseignant = User::role('enseignant')
            ->with('enseignantProfil.matieres')
            ->findOrFail($id);

        $matieres = Matiere::where('actif', true)->orderBy('nom')->get();

        return view('pedagogie.Users.enseignants.edit', compact('enseignant', 'matieres'));
    }

    public function update(UpdateEnseignantRequest $request, $id)
    {
        $this->service->update($id, $request->validated());

        return redirect()
            ->route('enseignants.index')
            ->with('success', 'Enseignant mis à jour avec succès');
    }
}
