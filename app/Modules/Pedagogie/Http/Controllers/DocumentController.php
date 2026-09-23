<?php

namespace App\Modules\Pedagogie\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class DocumentController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $media = $user->media()
            ->where('collection_name', 'like', '%_pdf')
            ->get();

        $stats = [
            'cahiers' => $media->where('collection_name', 'cahier_texte_pdf')->count(),
            'rapports' => $media->where('collection_name', 'rapport_mensuel_pdf')->count(),
            'factures' => $media->where('collection_name', 'facture_pdf')->count(),
        ];

        return view('documents.index', compact('stats'));
    }

    public function cahiers()
    {
        $documents = Auth::user()
            ->media()
            ->where('collection_name', 'cahier_texte_pdf')
            ->latest()
            ->get();

        return view('documents.cahiers', compact('documents'));
    }

    public function rapports()
    {
        $documents = Auth::user()
            ->media()
            ->where('collection_name', 'rapport_mensuel_pdf')
            ->latest()
            ->get();

        return view('documents.rapports', compact('documents'));
    }
}