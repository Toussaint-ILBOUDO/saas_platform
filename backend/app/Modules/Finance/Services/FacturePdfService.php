<?php

namespace App\Modules\Finance\Services;

use App\Models\Facture;
use App\Support\Pdf\DocumentPdf;
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

        $pdf = Pdf::loadView(
                'pdf.facture-parent',
                $data
            )
            ->setPaper('A4', 'portrait')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);

        // Le pied de page n'est dessinable qu'après le rendu : dompdf ne
        // connaît le nombre de pages qu'une fois le flux posé.
        $pdf->render();

        DocumentPdf::piedDePage(
            $pdf->getDomPDF(),
            'Facture · Réf. ' . $facture->numero_facture
        );

        return $pdf;
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
            'parent',
            'periode',
            'lignes.affectation.enseignant.user',
            'lignes.affectation.matiere',
            'ajustements.typeAjustement',
        ]);

        $cabinet = DocumentPdf::cabinet();

        $ajustements = $facture->ajustements;
        $totalCredits = (int) $ajustements->where('type', 'credit')->sum('montant');
        $totalDebits = (int) $ajustements->where('type', 'debit')->sum('montant');

        return [
            'facture'           => $facture,
            'eleve'             => $facture->contrat?->eleve,
            'parent'            => $facture->parent,
            'periode'           => $facture->periode,
            'lignes'            => $facture->lignes,
            'ajustements'       => $ajustements,
            'total_credits'     => $totalCredits,
            'total_debits'      => $totalDebits,
            'montantCours'      => (int) $facture->lignes->sum('montant'),

            // Gabarit commun.
            'cabinet'           => $cabinet,
            'cabinet_nom'       => $cabinet['nom'],
            'cabinet_logo'      => DocumentPdf::logo($cabinet['logo']),
            'document_titre'    => 'Facture',
            'document_sous_titre' => 'Règlement des cours',
            'document_reference' => $facture->numero_facture,
            'date_edition'      => $facture->created_at ?? now(),
            'badge'             => $this->badge($facture),
            'ligne_meta'        => $this->ligneMeta($facture),
        ];
    }

    /**
     * Badge de statut de paiement (couleurs du gabarit).
     */
    protected function badge(Facture $facture): array
    {
        if ($facture->estPayee()) {
            return ['libelle' => 'Payée', 'classe' => 'valide'];
        }

        if ($facture->estEnAttente()) {
            return ['libelle' => 'En attente', 'classe' => 'soumis'];
        }

        return [
            'libelle' => ucfirst(str_replace('_', ' ', (string) $facture->statut_paiement)),
            'classe' => 'info',
        ];
    }

    /**
     * Ligne d'en-tête contextuelle (période couverte).
     */
    protected function ligneMeta(Facture $facture): string
    {
        if ($facture->periode) {
            $meta = '<strong>Période :</strong> ' . e($facture->periode->label);

            if ($facture->periode->date_debut && $facture->periode->date_fin) {
                $meta .= ' &mdash; du '
                    . $facture->periode->date_debut->format('d/m/Y')
                    . ' au '
                    . $facture->periode->date_fin->format('d/m/Y');
            }

            return $meta;
        }

        return '<strong>Facture émise le</strong> '
            . ($facture->created_at?->format('d/m/Y') ?? '—');
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
