<?php

namespace App\Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Users\Services\ParentService;
use App\Modules\Users\Http\Requests\StoreParentRequest;
use App\Modules\Users\Http\Requests\UpdateParentRequest;
use App\Models\User;

class ParentController extends Controller
{
    public function __construct(
        private readonly ParentService $service
    ) {}

    public function index()
    {
        $parents = User::role('parent')
            ->withCount('enfants')
            ->latest()
            ->paginate(15);

        return view(
            'pedagogie.Users.parents.index',
            compact('parents')
        );
    }

    public function create()
    {
        return view('pedagogie.Users.parents.create');
    }

    public function edit($id)
    {
        $parent = User::role('parent')
            ->with('parentProfil')
            ->findOrFail($id);

        return view('pedagogie.Users.parents.edit', compact('parent'));
    }

    public function store(StoreParentRequest $request)
    {
        $this->service->create($request->validated());

        return redirect()
            ->route('parents.index')
            ->with('success', 'Parent créé avec succès');
    }

    public function update(UpdateParentRequest $request, $id)
    {
        $parent = User::findOrFail($id);

        $data = $request->validated();

        // 🔐 On retire password si vide (important)
        if (empty($data['password'])) {
            unset($data['password']);
        }

        // 👤 Update user
        $parent->update([
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'telephone_whatsapp' => $data['telephone_whatsapp'] ?? null,
            'telephone_appel' => $data['telephone_appel'] ?? null,
            'email' => $data['email'] ?? null,
            // password automatiquement hashé grâce à casts()
            'password' => $data['password'] ?? $parent->password,
        ]);

        return redirect()
            ->route('parents.index')
            ->with('success', 'Parent mis à jour avec succès');
    }

    public function show($id)
    {
        $parent = User::with(['parentProfil', 'enfants'])->findOrFail($id);

        return view('pedagogie.Users.parents.show', compact('parent'));
    }
}