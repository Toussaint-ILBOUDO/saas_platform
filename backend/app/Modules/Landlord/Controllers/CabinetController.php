<?php

namespace App\Modules\Landlord\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Cabinet;
use App\Models\ParametresCabinet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Console Landlord : gestion des cabinets (T2.3).
 */
final class CabinetController extends Controller
{
    /**
     * Modules KEduc proposables au cabinet (fonctionnalités actives).
     */
    public const MODULES = [
        'pedagogie' => 'Pédagogie (classes, matières, contrats…)',
        'planning' => 'Planning des cours',
        'finance' => 'Finance & paie',
        'bibliotheque' => 'Bibliothèque numérique',
        'actualites' => 'Actualités',
        'boutique' => 'Boutique en ligne',
        'temoignages' => 'Témoignages',
        'cms' => 'Pages CMS / FAQ',
    ];

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q'));

        $cabinets = Cabinet::query()
            ->with('parametres')
            ->when($q !== '', fn ($query) => $query
                ->where('id', 'ilike', "%{$q}%")
                ->orWhere('nom', 'ilike', "%{$q}%"))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('landlord.cabinets.index', [
            'cabinets' => $cabinets,
            'q' => $q,
        ]);
    }

    public function create(): View
    {
        return view('landlord.cabinets.form', [
            'cabinet' => null,
            'modules' => self::MODULES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->valider($request);

        // Pas de DB::transaction : le pipeline stancl (TenantCreated) crée une
        // base de données — CREATE DATABASE est impossible dans une transaction.
        $cabinet = Cabinet::create([
                'id' => $data['id'],
                'nom' => $data['nom'],
                'sous_domaine' => $data['sous_domaine'] ?? $data['id'],
                'status' => 'actif',
                'data' => ['email' => $data['email'] ?? null, 'telephone' => $data['telephone'] ?? null],
            ]);

        $domaine = sprintf('%s.%s', $cabinet->sous_domaine, env('TENANCY_DOMAIN_SUFFIX', 'localhost'));
        if (! $cabinet->domains()->where('domain', $domaine)->exists()) {
            $cabinet->domains()->create(['domain' => $domaine]);
        }

        ParametresCabinet::firstOrCreate(
            ['cabinet_id' => $cabinet->id],
            ['tarif_abonnement' => 0, 'tarif_par_eleve' => 0, 'fonctionnalites_activees' => []]
        );

        // Pipeline stancl (TenantCreated) : CreateDatabase → MigrateDatabase → SeedDatabase (D-027)
        request()->session()->flash('success', 'Cabinet créé. Base provisionnée, premier domaine « ' . $cabinet->primary_domain . ' ».');

        return redirect()->route('landlord.cabinets.show', $cabinet);
    }

    public function show(Cabinet $cabinet): View
    {
        $cabinet->load('parametres');

        return view('landlord.cabinets.show', [
            'cabinet' => $cabinet,
            'modules' => self::MODULES,
        ]);
    }

    public function edit(Cabinet $cabinet): View
    {
        return view('landlord.cabinets.form', [
            'cabinet' => $cabinet,
            'modules' => self::MODULES,
        ]);
    }

    public function update(Cabinet $cabinet, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:150'],
            'sous_domaine' => ['nullable', 'string', 'max:40', 'regex:/^[a-z0-9\-]+$/', Rule::unique('tenants', 'sous_domaine')->ignore($cabinet->id)],
            'status' => ['required', Rule::in(['actif', 'suspendu'])],
            'email' => ['nullable', 'email', 'max:150'],
            'telephone' => ['nullable', 'string', 'max:30'],
        ]);

        $data['data'] = ['email' => $data['email'] ?? null, 'telephone' => $data['telephone'] ?? null];

        $cabinet->update($data);

        request()->session()->flash('success', 'Informations du cabinet mises à jour.');

        return redirect()->route('landlord.cabinets.show', $cabinet);
    }

    public function updateParametres(Cabinet $cabinet, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tarif_abonnement' => ['required', 'numeric', 'min:0'],
            'tarif_par_eleve' => ['required', 'numeric', 'min:0'],
            'fonctionnalites_activees' => ['nullable', 'array'],
            'fonctionnalites_activees.*' => ['required', Rule::in(array_keys(self::MODULES))],
        ]);

        $data['fonctionnalites_activees'] = $data['fonctionnalites_activees'] ?? [];

        ParametresCabinet::updateOrCreate(
            ['cabinet_id' => $cabinet->id],
            [
                'tarif_abonnement' => $data['tarif_abonnement'],
                'tarif_par_eleve' => $data['tarif_par_eleve'],
                'fonctionnalites_activees' => $data['fonctionnalites_activees'],
            ]
        );

        request()->session()->flash('success', 'Tarifs et fonctionnalités du cabinet enregistrés.');

        return redirect()->route('landlord.cabinets.show', $cabinet);
    }

    private function valider(Request $request): array
    {
        return $request->validate([
            'id' => [
                'required', 'string', 'max:40',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                'unique:tenants,id',
            ],
            'nom' => ['required', 'string', 'max:150'],
            'sous_domaine' => ['nullable', 'string', 'max:40', 'regex:/^[a-z0-9\-]+$/', 'unique:tenants,sous_domaine'],
            'email' => ['nullable', 'email', 'max:150'],
            'telephone' => ['nullable', 'string', 'max:30'],
        ]);
    }
}