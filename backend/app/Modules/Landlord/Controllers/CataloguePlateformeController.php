<?php

namespace App\Modules\Landlord\Controllers;

use App\Http\Controllers\Controller;
use App\Models\LigneTypePlateforme;
use App\Models\ServicePlateforme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * P8 — Écran « Catalogue » de la console super-admin : liste ET édition des
 * services facturables (contrat commercial) et des lignes types de facture.
 * Les libellés / descriptions / prix de départ sont modifiables à tout
 * moment ; ils alimentent les écrans de contrat, la facturation et les PDF.
 */
final class CataloguePlateformeController extends Controller
{
    public function index(): View
    {
        $services = ServicePlateforme::query()->tries()->get()->groupBy('categorie');
        $categorieLibelles = ServicePlateforme::CATEGORIES;

        // Catégories ordonnées, inconnues en fin de liste.
        $ordreCategories = array_merge(
            array_keys($categorieLibelles),
            $services->keys()->reject(fn ($c) => isset($categorieLibelles[$c]))->all(),
        );

        return view('landlord.catalogue', [
            'services' => $services,
            'lignesTypes' => LigneTypePlateforme::query()->tries()->get(),
            'categories' => collect($ordreCategories)
                ->mapWithKeys(fn ($c) => [$c => $categorieLibelles[$c] ?? ucfirst($c)]),
        ]);
    }

    /** Mise à jour du texte d'un service (libellé / description / catégorie / actif). */
    public function majService(Request $request, ServicePlateforme $service): RedirectResponse
    {
        $data = $request->validate([
            'libelle' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'categorie' => ['required', 'string', 'max:40'],
            'actif' => ['nullable', 'boolean'],
        ]);

        $service->update($data);

        return back()->with('success', 'Service mis à jour : '.$service->libelle);
    }

    /** Création d'une ligne type de facture (brique réutilisable). */
    public function creerLigneType(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'libelle' => ['required', 'string', 'max:200'],
            'sens' => ['required', 'in:debit,credit'],
            'montant' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:2000'],
            'ordre' => ['nullable', 'integer', 'min:0'],
        ]);

        LigneTypePlateforme::create($data + ['actif' => true, 'ordre' => $data['ordre'] ?? 99]);

        return back()->with('success', 'Ligne type créée : '.$data['libelle']);
    }

    /** Mise à jour d'une ligne type. */
    public function majLigneType(Request $request, LigneTypePlateforme $ligne): RedirectResponse
    {
        $data = $request->validate([
            'libelle' => ['required', 'string', 'max:200'],
            'sens' => ['required', 'in:debit,credit'],
            'montant' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:2000'],
            'actif' => ['nullable', 'boolean'],
            'ordre' => ['nullable', 'integer', 'min:0'],
        ]);

        $ligne->update($data);

        return back()->with('success', 'Ligne type mise à jour.');
    }

    /** Désactivation (pas de suppression : les factures passées restent lisibles). */
    public function supprimerLigneType(LigneTypePlateforme $ligne): RedirectResponse
    {
        $ligne->update(['actif' => false]);

        return back()->with('success', 'Ligne type désactivée : "'.$ligne->libelle.'"');
    }
}
