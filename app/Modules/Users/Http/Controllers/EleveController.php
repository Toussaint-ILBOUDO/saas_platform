<?php

namespace App\Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Users\Services\EleveService;
use App\Modules\Users\Http\Requests\StoreEleveRequest;
use App\Modules\Users\Http\Requests\UpdateEleveRequest;
use App\Modules\Users\Http\Requests\ActivateEleveAccountRequest;
use App\Models\User;
use App\Models\Classe;
use App\Models\Eleve;

class EleveController extends Controller
{
    public function __construct(
        private readonly EleveService $service
    ) {}

    public function index()
    {
        $this->authorize('viewAny', Eleve::class);

        $eleves = Eleve::query()
            ->with([
                'user',
                'parent',
                'classe',
            ])
            ->latest()
            ->paginate(15);

        return view(
            'pedagogie.users.eleves.index',
            compact('eleves')
        );
    }

    public function create()
    {
        $this->authorize('create', Eleve::class);

        return view('pedagogie.users.eleves.create', [
            'parents' => User::role('parent')->get(),
            'classes' => Classe::all(),
        ]);
    }

    public function store(StoreEleveRequest $request)
    {
        $this->authorize('create', Eleve::class);

        $this->service->create($request->validated());

        return redirect()
            ->route('eleves.index')
            ->with('success', 'Élève créé avec succès');
    }

    public function edit($id)
    {
        $eleve = Eleve::with(['user', 'parent', 'classe'])->findOrFail($id);

        $this->authorize('update', $eleve);

        return view('pedagogie.users.eleves.edit', [
            'eleve' => $eleve,
            'parents' => User::role('parent')->get(),
            'classes' => Classe::all(),
        ]);
    }

    public function update(UpdateEleveRequest $request, $id)
    {
        $eleve = Eleve::with('user')->findOrFail($id);

        $this->authorize('update', $eleve);

        $data = $request->validated();

        $eleve->user->update([
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'email' => $data['email'] ?? null,
            'telephone_whatsapp' => $data['telephone_whatsapp'] ?? null,
            'telephone_appel' => $data['telephone_appel'] ?? null,
        ]);

        $eleve->update([
            'parent_id' => $data['parent_id'],
            'classe_id' => $data['classe_id'],
            'ecole' => $data['ecole'] ?? null,
            'date_naissance' => $data['date_naissance'] ?? null,
            'lieu_naissance' => $data['lieu_naissance'] ?? null,
            'parent_charge' => $data['parent_charge'] ?? null,
            'etablissement_origine' => $data['etablissement_origine'] ?? null,
            'profession_pere' => $data['profession_pere'] ?? null,
            'profession_mere' => $data['profession_mere'] ?? null,
            'regime_etude' => $data['regime_etude'] ?? null,
            'loisirs_sport' => $data['loisirs_sport'] ?? null,
            'religion_enfant' => $data['religion_enfant'] ?? null,
            'maladies_allergies' => $data['maladies_allergies'] ?? null,
            'interdits_familiaux' => $data['interdits_familiaux'] ?? null,
            'boisson_preferee' => $data['boisson_preferee'] ?? null,
            'nourriture_preferee' => $data['nourriture_preferee'] ?? null,
            'autres_precautions' => $data['autres_precautions'] ?? null,
            'autres_observations' => $data['autres_observations'] ?? null,
        ]);

        return redirect()
            ->route('eleves.index')
            ->with('success', 'Élève mis à jour avec succès');
    }

    public function show(Eleve $eleve)
    {
        $this->authorize('view', $eleve);

        $eleve->load(['user', 'parent', 'classe']);

        return view('pedagogie.users.eleves.show', compact('eleve'));
    }

    public function mesEnfants()
    {
        $eleves = auth()->user()->enfants()
            ->with('user')
            ->latest()
            ->get();

        return view(
            'pedagogie.users.eleves.mes-enfants',
            compact('eleves')
        );
    }

    public function accountForm(Eleve $eleve)
    {
        $this->authorize('update', $eleve);

        return view(
            'pedagogie.users.eleves.account',
            compact('eleve')
        );
    }

    public function activateAccount(
        ActivateEleveAccountRequest $request,
        Eleve $eleve
    ) {
        $this->authorize('update', $eleve);

        $this->service->activateAccount(
            $eleve,
            $request->validated()
        );

        return redirect()
            ->route('eleves.edit', $eleve)
            ->with(
                'success',
                'Compte élève activé avec succès.'
            );
    }

    
}