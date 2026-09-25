<?php

namespace App\Modules\Landlord\Controllers;

use App\Http\Controllers\Controller;
use App\Models\JournalPlateforme;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Consultation du journal de la plateforme (T2.7).
 */
class JournalController extends Controller
{
    public const NIVEAUX = ['info', 'warning', 'error'];

    public function index(Request $request): View
    {
        $requete = JournalPlateforme::query()
            ->with('cabinet:id,nom,sous_domaine,status')
            ->orderByDesc('created_at');

        if ($action = trim((string) $request->query('action', ''))) {
            $requete->where('action', 'like', '%' . $action . '%');
        }

        if ($niveau = $request->query('niveau')) {
            $requete->where('level', $niveau);
        }

        if ($cabinet = trim((string) $request->query('cabinet', ''))) {
            $requete->where('cabinet_id', $cabinet);
        }

        $entrees = $requete->paginate(20)->withQueryString();

        return view('landlord.journal.index', [
            'entrees' => $entrees,
            'niveaux' => self::NIVEAUX,
        ]);
    }
}