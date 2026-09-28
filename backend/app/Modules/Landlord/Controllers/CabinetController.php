<?php

namespace App\Modules\Landlord\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Cabinet;
use App\Models\JournalPlateforme;
use App\Models\ParametresCabinet;
use App\Support\FicheCabinet;
use App\Support\SauvegardeCabinet;
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

    /**
     * Champs optionnels de la fiche cabinet (D-044) — source unique du site public.
     */
    public const CHAMPS_FICHE = [
        'slogan' => ['nullable', 'string', 'max:200'],
        'directeur' => ['nullable', 'string', 'max:120'],
        'telephone_2' => ['nullable', 'string', 'max:30'],
        'whatsapp' => ['nullable', 'string', 'max:30'],
        'adresse' => ['nullable', 'string', 'max:255'],
        'horaires' => ['nullable', 'string', 'max:80'],
        'orange_money' => ['nullable', 'string', 'max:30'],
        'moov_money' => ['nullable', 'string', 'max:30'],
        'wave' => ['nullable', 'string', 'max:30'],
        'cash' => ['nullable', 'boolean'],
        'pays' => ['nullable', 'string', 'max:80'],
        'devise' => ['nullable', 'string', 'max:10'],
        'localites' => ['nullable', 'string', 'max:1000'],
        'facebook' => ['nullable', 'string', 'max:255'],
        'tiktok' => ['nullable', 'string', 'max:255'],
        'whatsapp_business' => ['nullable', 'string', 'max:255'],
        'linkedin' => ['nullable', 'string', 'max:255'],
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
            'fiche' => FicheCabinet::defaut(),
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
                // stancl VirtualColumn : toute colonne hors getCustomColumns()
                // est sérialisée dans la colonne json « data » (lu via $cabinet->email).
                'email' => $data['email'] ?? null,
                'telephone' => $data['telephone'] ?? null,
                // Fiche cabinet (D-044) : injectée par le pipeline dans
                // parametres_publics.data.fiche (base tenant).
                'fiche' => FicheCabinet::depuisForm($data),
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
            'fiche' => FicheCabinet::depuisTenant($cabinet),
        ]);
    }

    public function update(Cabinet $cabinet, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:150'],
            'sous_domaine' => ['nullable', 'string', 'max:40', 'regex:/^[a-z0-9\-]+$/', Rule::unique('tenants', 'sous_domaine')->ignore($cabinet->id)],
            'status' => ['required', Rule::in(['actif', 'suspendu', 'archive'])],
            'email' => ['nullable', 'email', 'max:150'],
            'telephone' => ['nullable', 'string', 'max:30'],
            ...self::CHAMPS_FICHE,
        ]);

        // Fiche présente dans le formulaire → on la met à jour (sinon inchangée).
        if ($request->hasAny(array_keys(self::CHAMPS_FICHE))) {
            $data['fiche'] = FicheCabinet::depuisForm($data);
        }

        // stancl VirtualColumn : email/telephone (et fiche) sérialisés dans « data ».
        $cabinet->update($data);

        // P1 : le sous-domaine pilote le premier domaine — on resynchronise la
        // table « domains » quand il change (le renommage est fait par l'admin).
        $domaine = sprintf('%s.%s', $cabinet->sous_domaine ?: $cabinet->id, env('TENANCY_DOMAIN_SUFFIX', 'localhost'));
        if ($cabinet->primary_domain !== $domaine) {
            DB::table('domains')->where('tenant_id', $cabinet->id)->delete();
            $cabinet->domains()->create(['domain' => $domaine]);
        }

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

    /**
     * Suspendre / réactiver / archiver (T2.5). L'accès tenant au domaine est
     * bloqué par le middleware cabinet.actif dès que status ≠ actif.
     */
    public function changeStatut(Cabinet $cabinet, Request $request): RedirectResponse
    {
        $statut = $request->validate([
            'statut' => ['required', Rule::in(['actif', 'suspendu', 'archive'])],
        ])['statut'];

        $avant = $cabinet->status;
        $cabinet->update(['status' => $statut]);

        $actions = [
            'actif' => 'cabinet.reactive',
            'suspendu' => 'cabinet.suspendu',
            'archive' => 'cabinet.archive',
        ];

        JournalPlateforme::ecrire($actions[$statut], 'info', $cabinet, [
            'avant' => $avant,
            'apres' => $statut,
        ]);

        $messages = [
            'actif' => 'Cabinet réactivé.',
            'suspendu' => 'Cabinet suspendu : le site et l\'espace sont bloqués.',
            'archive' => 'Cabinet archivé.',
        ];

        request()->session()->flash('success', $messages[$statut]);

        return redirect()->route('landlord.cabinets.show', $cabinet);
    }

    /**
     * Suppression définitive (T2.5) : sauvegarde pg_dump préalable obligatoire,
     * puis destruction (base via TenantDeleted/DeleteDatabase, centralement).
     */
    public function supprimer(Cabinet $cabinet): RedirectResponse
    {
        $sauvegarde = (new SauvegardeCabinet())->sauvegarder($cabinet);

        $id = $cabinet->id;
        $nom = $cabinet->nom;

        JournalPlateforme::ecrire('cabinet.supprime', 'warning', $cabinet, [
            'sauvegarde' => $sauvegarde,
        ]);

        DB::table('parametres_cabinet')->where('cabinet_id', $id)->delete();
        $cabinet->domains()->delete();
        $cabinet->delete(); // TenantDeleted → DeleteDatabase (DROP de la base)

        request()->session()->flash(
            'success',
            'Cabinet « ' . $nom . ' » supprimé définitivement.'
            . ($sauvegarde ? ' Sauvegarde : ' . $sauvegarde : '')
        );

        return redirect()->route('landlord.cabinets.index');
    }

    /**
     * Émission d'un jeton d'impersonation (T2.6) vers l'admin du cabinet.
     * Consommé une seule fois sur <domaine>/impersonation/{jeton}.
     */
    public function impersoner(Cabinet $cabinet): RedirectResponse
    {
        abort_if($cabinet->status !== 'actif', 409, 'Impossible d\'impersonner un cabinet non actif.');
        abort_unless($cabinet->admin_utilisateur_id, 422, 'Aucun administrateur provisionné à impersonner.');

        $domaine = $cabinet->primary_domain;
        abort_unless($domaine, 422, 'Aucun domaine configuré pour ce cabinet.');

        $jeton = tenancy()->impersonate(
            $cabinet,
            (string) $cabinet->admin_utilisateur_id,
            "http://{$domaine}/",
            'web'
        );

        DB::connection('pgsql')->table('tenant_user_impersonation_tokens')
            ->where('token', $jeton->token)
            ->update(['super_admin_id' => auth('landlord')->id()]);

        JournalPlateforme::ecrire('impersonation.emise', 'info', $cabinet, [
            'super_admin_id' => auth('landlord')->id(),
            'user_id' => $cabinet->admin_utilisateur_id,
        ]);

        request()->session()->flash('success', 'Jeton d\'impersonation émis pour « ' . $cabinet->nom . ' ».');

        return redirect()->away("http://{$domaine}/impersonation/{$jeton->token}");
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
            ...self::CHAMPS_FICHE,
        ]);
    }
}