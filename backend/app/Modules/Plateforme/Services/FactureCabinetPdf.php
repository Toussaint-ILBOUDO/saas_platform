<?php

namespace App\Modules\Plateforme\Services;

use App\Models\FactureCabinet;
use App\Support\Pdf\DocumentPdf;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/** PDF d'une facture de cabinet (base Landlord). */
final class FactureCabinetPdf
{
    /** Rend le PDF de la facture (brouillon inclus, mention apparente). */
    public function rendre(FactureCabinet $facture): string
    {
        $dompdf = Pdf::loadView('pdf.plateforme.facture', [
            'facture' => $facture->load('lignes', 'paiements', 'evenements'),
        ] + MetodeDocs::variablesPlateforme($facture->cabinet_id));

        $contenu = $dompdf->output();

        DocumentPdf::piedDePage($dompdf->getDompdf(), $this->reference($facture));

        return $contenu;
    }

    /** Enregistre le PDF dans storage/app/factures/cabinet_<id>/. */
    public function archiver(FactureCabinet $facture): ?string
    {
        try {
            $chemin = $this->chemin($facture);
            Storage::disk('local')->put($chemin, $this->rendre($facture));

            return $chemin;
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    private function chemin(FactureCabinet $facture): string
    {
        return 'factures/cabinet_'.$facture->cabinet_id.'/'
            .($facture->numero_facture ?? 'brouillon_'.$facture->getKey()).'.pdf';
    }

    private function reference(FactureCabinet $facture): string
    {
        return 'Facture plateforme — '
            .($facture->numero_facture ?? 'Brouillon #'.$facture->getKey())
            .' — '.($facture->periode_debut?->format('d/m/Y').' au '.$facture->periode_fin?->format('d/m/Y'));
    }
}
