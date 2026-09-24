<?php

namespace App\Modules\Landlord\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Pages provisoires des sections Landlord (Cabinets, Facturation, Journal).
 * Remplacées par les vues réelles de T2.3, T2.7 et P7.
 */
final class SectionPlaceholderController extends Controller
{
    public function __invoke(string $section): View
    {
        $sections = [
            'cabinets' => ['titre' => 'Cabinets', 'description' => 'Liste et gestion des cabinets (T2.3).'],
            'facturation' => ['titre' => 'Facturation', 'description' => 'Facturation de la plateforme (P7).'],
            'journal' => ['titre' => 'Journal', 'description' => 'Journal de la plateforme (T2.7).'],
        ];

        abort_unless(isset($sections[$section]), 404);

        return view('landlord.placeholder', [
            'titre' => $sections[$section]['titre'],
            'description' => $sections[$section]['description'],
        ]);
    }
}