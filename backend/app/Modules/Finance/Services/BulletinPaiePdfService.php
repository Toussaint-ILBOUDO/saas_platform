<?php

namespace App\Modules\Finance\Services;

use App\Models\BulletinPaie;
use App\Support\Pdf\DocumentPdf;
use Illuminate\Http\Response;
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

        $pdf = Pdf::loadView(
                'pdf.bulletin-paie',
                $data
            )
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);

        // Le pied de page n'est dessinable qu'après le rendu.
        $pdf->render();

        DocumentPdf::piedDePage(
            $pdf->getDomPDF(),
            'Bulletin de paie · Réf. ' . $bulletin->numero
        );

        return $pdf;
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

        $cabinet = DocumentPdf::cabinet();

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
            'cabinet_nom'       => $cabinet['nom'],
            'cabinet_logo'      => DocumentPdf::logo($cabinet['logo']),
            'total_primes'      => $totalPrimes,
            'total_retenues'    => $totalRetenues,
            'montant_net_final' => $montantNetFinal,

            // Gabarit commun.
            'document_titre'    => 'Bulletin de paie',
            'document_sous_titre' => 'Rémunération de l\'enseignant',
            'document_reference' => $bulletin->numero,
            'date_edition'      => $bulletin->created_at ?? now(),
            'badge'             => $this->badge($bulletin),
            'ligne_meta'        => $this->ligneMeta($bulletin),
        ];
    }

    /**
     * Badge de statut du bulletin (couleurs du gabarit).
     */
    protected function badge(BulletinPaie $bulletin): array
    {
        return match ($bulletin->statut) {
            'valide' => ['libelle' => 'Validé', 'classe' => 'valide'],
            'verse' => ['libelle' => 'Versé', 'classe' => 'info'],
            'conteste' => ['libelle' => 'Contesté', 'classe' => 'rejete'],
            'genere' => ['libelle' => 'Généré', 'classe' => 'soumis'],
            default => [
                'libelle' => ucfirst((string) $bulletin->statut),
                'classe' => 'info',
            ],
        };
    }

    /**
     * Ligne d'en-tête contextuelle (enseignant + période).
     */
    protected function ligneMeta(BulletinPaie $bulletin): string
    {
        $enseignant = trim(
            ($bulletin->enseignant?->user?->prenom ?? '')
            . ' '
            . ($bulletin->enseignant?->user?->nom ?? '')
        );

        $meta = '<strong>Enseignant :</strong> ' . e($enseignant !== '' ? $enseignant : '—');

        if ($bulletin->periode) {
            $meta .= ' &nbsp;·&nbsp; <strong>Période :</strong> ' . e($bulletin->periode->label);

            if ($bulletin->periode->date_debut && $bulletin->periode->date_fin) {
                $meta .= ' &mdash; du '
                    . $bulletin->periode->date_debut->format('d/m/Y')
                    . ' au '
                    . $bulletin->periode->date_fin->format('d/m/Y');
            }
        }

        return $meta;
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
