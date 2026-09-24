<?php

namespace App\Modules\Finance\Services;

use App\Models\BulletinPaie;
use App\Support\CabinetInfo;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Barryvdh\DomPDF\Facade\Pdf;

class BulletinPaiePdfService
{
    /**
     * Affiche le PDF dans le navigateur.
     */
    public function stream(BulletinPaie $bulletin): Response
    {
        $pdf = $this->make($bulletin);

        return $pdf->stream($this->filename($bulletin));
    }

    /**
     * Télécharge le PDF.
     */
    public function download(BulletinPaie $bulletin): Response
    {
        $pdf = $this->make($bulletin);

        return $pdf->download($this->filename($bulletin));
    }

    /**
     * Génère le PDF DomPDF.
     */
    public function make(BulletinPaie $bulletin)
    {
        $data = $this->prepareData($bulletin);

        return Pdf::loadView(
            'finances.bulletins-paie.pdf',
            $data
        )->setPaper('a4', 'portrait');
    }

    /**
     * Prépare les données pour la vue PDF.
     */
    protected function prepareData(BulletinPaie $bulletin): array
    {
        $bulletin->load([
            'enseignant.user',
            'periode',
            'lignes.eleve.user',
            'lignes.matiere',
            'ajustements',
        ]);

        $cabinet = CabinetInfo::all();

        $totalPrimes = $bulletin->ajustements
            ->where('type', 'prime')
            ->sum('montant');

        $totalRetenues = $bulletin->ajustements
            ->where('type', 'retenue')
            ->sum('montant');

        $montantNetFinal = $bulletin->montant_brut
            - $bulletin->frais_suivi
            + $totalPrimes
            - $totalRetenues;

        return [
            'bulletin'          => $bulletin,
            'cabinet'           => $cabinet,
            'total_primes'      => $totalPrimes,
            'total_retenues'    => $totalRetenues,
            'montant_net_final' => $montantNetFinal,
        ];
    }

    /**
     * Nom du fichier PDF.
     */
    protected function filename(BulletinPaie $bulletin): string
    {
        $nom = $bulletin->enseignant->user->nom ?? 'enseignant';

        $prenom = $bulletin->enseignant->user->prenom ?? '';

        $periode = $bulletin->periode->label ?? 'periode';

        $slug = strtolower(
            preg_replace('/[^a-zA-Z0-9]+/', '-', $prenom . '-' . $nom)
        );

        return "bulletin-paie-{$slug}-{$periode}.pdf";
    }
}
