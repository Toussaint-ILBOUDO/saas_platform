<?php

namespace App\Modules\Landlord\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ParametresPlateforme;
use App\Modules\Plateforme\Services\MetodeDocs;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * T2.10 + P8 — Paramètres de la plateforme (base Landlord) :
 * interrupteur d'accès aux écrans web KEduc (B4) et identité de la
 * société éditrice (« Magis Plus Center ») utilisée dans les PDF.
 */
class ParametresController extends Controller
{
    public function index(): View
    {
        return view('landlord.parametres.index', [
            'accesWebKeduc' => (bool) ParametresPlateforme::obtenir(ParametresPlateforme::CLE_ACCES_WEB_KEDUC, false),
            'identite' => MetodeDocs::identite(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'acces_ecrans_web_keduc' => ['nullable', 'boolean'],
            'plateforme_nom' => ['nullable', 'string', 'max:120'],
            'plateforme_tagline' => ['nullable', 'string', 'max:300'],
            'plateforme_contact' => ['nullable', 'string', 'max:300'],
        ]);

        ParametresPlateforme::definir(
            ParametresPlateforme::CLE_ACCES_WEB_KEDUC,
            (bool) ($data['acces_ecrans_web_keduc'] ?? false)
        );

        $identite = MetodeDocs::identite();

        ParametresPlateforme::definir('plateforme', [
            'nom' => $data['plateforme_nom'] ?? $identite['nom'],
            'tagline' => $data['plateforme_tagline'] ?? $identite['tagline'],
            'contact' => $data['plateforme_contact'] ?? $identite['contact'],
            'logo' => $identite['logo'],
        ]);

        request()->session()->flash('success', 'Paramètres de la plateforme mis à jour.');

        return redirect()->route('landlord.parametres.index');
    }
}
