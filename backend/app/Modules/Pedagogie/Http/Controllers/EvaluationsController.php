<?php

namespace App\Modules\Pedagogie\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\EvaluationCours;

class EvaluationsController extends Controller
{
    public function index()
    {
        $eleve = auth()->user()->eleve;

        abort_unless($eleve, 403);

        $evaluations = EvaluationCours::query()
            ->with(['enseignant.user', 'auteur'])
            ->where('eleve_id', $eleve->id)
            ->latest()
            ->paginate(20);

        return view(
            'pedagogie.evaluations.index',
            compact('evaluations')
        );
    }
}