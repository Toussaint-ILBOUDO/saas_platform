<?php

namespace App\Modules\Landlord\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ParametresPlateforme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Paramètres globaux de la plateforme (T2.10) : interrupteur d'accès aux
 * écrans web KEduc (décision B4).
 */
class ParametresController extends Controller
{
    public function index(): View
    {
        return view('landlord.parametres.index', [
            'accesWebKeduc' => (bool) ParametresPlateforme::obtenir(ParametresPlateforme::CLE_ACCES_WEB_KEDUC, false),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'acces_ecrans_web_keduc' => ['nullable', 'boolean'],
        ]);

        ParametresPlateforme::definir(
            ParametresPlateforme::CLE_ACCES_WEB_KEDUC,
            (bool) ($data['acces_ecrans_web_keduc'] ?? false)
        );

        request()->session()->flash('success', 'Interrupteur d\'accès aux écrans web KEduc mis à jour.');

        return redirect()->route('landlord.parametres.index');
    }
}