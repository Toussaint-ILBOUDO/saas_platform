<?php

namespace App\Modules\Finance\Services;

use App\Models\Facture;
use App\Support\CabinetInfo;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class FacturePdfService
{
    /**
     * Télécharger le PDF.
     */
    public function download(Facture $facture): Response
    {
        $pdf = $this->make($facture);

        return $pdf->download(
            $this->filename($facture)
        );
    }

    /**
     * Afficher le PDF dans le navigateur.
     */
    public function stream(Facture $facture): Response
    {
        $pdf = $this->make($facture);

        return $pdf->stream(
            $this->filename($facture)
        );
    }

    /**
     * Sauvegarder le PDF dans le storage.
     */
    public function save(Facture $facture): string
    {
        $pdf = $this->make($facture);

        $path = 'factures/' . $this->filename($facture);

        Storage::disk('public')
            ->put($path, $pdf->output());

        return $path;
    }

    /**
     * Création du PDF DomPDF.
     */
    protected function make(Facture $facture)
    {
        $data = $this->prepareData($facture);

        return Pdf::loadView(
                'pdf.facture-parent',
                $data
            )
            ->setPaper('A4', 'portrait')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);
    }

    /**
     * Préparation des données envoyées à Blade.
     * Aucune logique métier — uniquement du mapping.
     */
    protected function prepareData(Facture $facture): array
    {
        $facture->loadMissing([
            'contrat.eleve.user',
            'contrat.eleve.classe',
            'contrat.typeCours',
            'contrat.affectations.matiere',
            'contrat.affectations.enseignant.user',
            'parent',
            'periode',
            'lignes.affectation.enseignant.user',
            'lignes.affectation.matiere',
        ]);

        $cabinet = CabinetInfo::all();

        $montantCours = $facture->montant_total
            - $facture->frais_suivi
            - $facture->autres_frais
            + $facture->remise;

        return [
            'facture'       => $facture,
            'eleve'         => $facture->contrat?->eleve,
            'parent'        => $facture->parent,
            'periode'       => $facture->periode,
            'lignes'        => $facture->lignes,
            'montantCours'  => $montantCours,
            'cabinet'       => $cabinet,
        ];
    }

    /**
     * Nom propre du fichier.
     */
    protected function filename(Facture $facture): string
    {
        $eleve = str_replace(
            ' ',
            '-',
            strtolower(
                $facture->contrat?->eleve?->user?->nom
                ?? 'eleve'
            )
        );

        return "facture-{$facture->numero_facture}-{$eleve}.pdf";
    }
}
