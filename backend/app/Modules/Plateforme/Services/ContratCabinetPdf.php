<?php

namespace App\Modules\Plateforme\Services;

use App\Models\ContratCabinet;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/** PDF d'un contrat commercial de cabinet (base Landlord). */
final class ContratCabinetPdf
{
    /** Enregistre le PDF du contrat et retourne son chemin relatif. */
    public function generer(ContratCabinet $contrat): ?string
    {
        $contenu = $this->rendre($contrat);

        $chemin = 'contrats/cabinet_'.$contrat->cabinet_id.'/'.$contrat->numero.'-v'.$contrat->version.'.pdf';

        Storage::disk('local')->put($chemin, $contenu);
        $contrat->chemin_pdf = $chemin;
        $contrat->saveQuietly();

        return $chemin;
    }

    /** Contenu binaire du PDF (archive disque, sinon re-génération). */
    public function contenu(ContratCabinet $contrat): string
    {
        if ($contrat->chemin_pdf !== null && Storage::disk('local')->exists($contrat->chemin_pdf)) {
            return (string) Storage::disk('local')->get($contrat->chemin_pdf);
        }

        return $this->rendre($contrat);
    }

    /** Rend le PDF de contrat. */
    private function rendre(ContratCabinet $contrat): string
    {
        return Pdf::loadView('pdf.plateforme.contrat', [
            'contrat' => $contrat,
            'tarifs' => $this->lignesTarifs($contrat),
        ] + MetodeDocs::variablesPlateforme($contrat->cabinet_id))->output();
    }

    /** Lignes du tableau « tarifs négociés » (un code de service = une ligne).
     * Libellés + descriptions issus du catalogue éditable (console). */
    private function lignesTarifs(ContratCabinet $contrat): array
    {
        $grille = app(ContratCabinetService::class)->grilleServices();
        $lignes = [];

        foreach ($grille as $code => $service) {
            $lignes[] = [
                'code' => $code,
                'libelle' => $service['libelle'],
                'description' => $service['description'],
                'valeur' => $contrat->{$code},
                'exonere' => is_array($contrat->exonerations)
                    && in_array($code, $contrat->exonerations, true),
                'taux' => str_starts_with($code, 'taux_'),
            ];
        }

        return $lignes;
    }
}
