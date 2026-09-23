<?php

namespace App\Modules\Librairie\Services;

use App\Models\Commande;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class CommandePdfService
{
    public function download(Commande $commande): Response
    {
        $pdf = $this->make($commande);

        return $pdf->download(
            $this->filename($commande)
        );
    }

    public function stream(Commande $commande): Response
    {
        $pdf = $this->make($commande);

        return $pdf->stream(
            $this->filename($commande)
        );
    }

    protected function make(Commande $commande)
    {
        $data = $this->prepareData($commande);

        return Pdf::loadView(
                'pdf.librairie-commande',
                $data
            )
            ->setPaper('A4', 'portrait')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);
    }

    protected function prepareData(Commande $commande): array
    {
        $commande->loadMissing([
            'lignes.produit',
            'user',
        ]);

        $cabinet = config('keduc.cabinet');

        return [
            'commande'  => $commande,
            'lignes'    => $commande->lignes,
            'cabinet'   => $cabinet,
        ];
    }

    protected function filename(Commande $commande): string
    {
        $client = str_replace(
            ' ',
            '-',
            strtolower(
                $commande->nom_client
                ?? 'client'
            )
        );

        return "commande-library-{$commande->id}-{$client}.pdf";
    }
}
