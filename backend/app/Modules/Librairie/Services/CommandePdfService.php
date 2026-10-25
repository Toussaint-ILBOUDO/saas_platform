<?php

namespace App\Modules\Librairie\Services;

use App\Models\Commande;
use App\Support\Pdf\DocumentPdf;
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

        $pdf = Pdf::loadView(
                'pdf.librairie-commande',
                $data
            )
            ->setPaper('A4', 'portrait')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);

        // Le pied de page n'est dessinable qu'après le rendu.
        $pdf->render();

        DocumentPdf::piedDePage(
            $pdf->getDomPDF(),
            'Commande · Réf. ' . $commande->reference
        );

        return $pdf;
    }

    protected function prepareData(Commande $commande): array
    {
        $commande->loadMissing([
            'lignes.produit',
            'user',
        ]);

        $cabinet = DocumentPdf::cabinet();

        return [
            'commande'  => $commande,
            'lignes'    => $commande->lignes,

            // Gabarit commun.
            'cabinet'           => $cabinet,
            'cabinet_nom'       => $cabinet['nom'],
            'cabinet_logo'      => DocumentPdf::logo($cabinet['logo']),
            'document_titre'    => 'Bon de commande',
            'document_sous_titre' => 'Librairie',
            'document_reference' => $commande->reference,
            'date_edition'      => $commande->created_at ?? now(),
            'badge'             => $this->badge($commande),
            'ligne_meta'        => $this->ligneMeta($commande),
        ];
    }

    /**
     * Badge de statut de la commande (couleurs du gabarit).
     */
    protected function badge(Commande $commande): array
    {
        return match ($commande->statut) {
            'livree' => ['libelle' => Commande::statutLabel($commande->statut), 'classe' => 'valide'],
            'annulee' => ['libelle' => Commande::statutLabel($commande->statut), 'classe' => 'rejete'],
            'en_attente' => ['libelle' => Commande::statutLabel($commande->statut), 'classe' => 'soumis'],
            default => ['libelle' => Commande::statutLabel($commande->statut), 'classe' => 'info'],
        };
    }

    /**
     * Ligne d'en-tête contextuelle (client).
     */
    protected function ligneMeta(Commande $commande): string
    {
        return '<strong>Client :</strong> ' . e($commande->nom_client)
            . ' &nbsp;·&nbsp; <strong>Téléphone :</strong> '
            . e($commande->telephone_client ?: '—');
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
