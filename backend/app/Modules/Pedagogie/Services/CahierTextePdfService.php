<?php

namespace App\Modules\Pedagogie\Services;

use App\Models\CahierTexte;
use App\Models\Eleve;
use App\Support\Pdf\DocumentPdf;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Modules\Pedagogie\Enums\PdfCollection;

class CahierTextePdfService
{
    /*
    |--------------------------------------------------------------------------
    | PUBLIC METHODS
    |--------------------------------------------------------------------------
    */

    public function downloadSingle(CahierTexte $cahier)
    {
        $cahier->load([
            'affectation.matiere',
            'affectation.enseignant.user',
            'affectation.contrat.eleve.user',
        ]);

        $cabinet = $this->getCabinet();

        return $this->buildPdf(
            self::SINGLE_VIEW,
            array_merge($cabinet, [
                'cahier'  => $cahier,
                'document_titre'    => 'Cahier de texte',
                'document_sous_titre' => 'Séance du '
                    . ($cahier->date_seance?->format('d/m/Y') ?? '—'),
                'document_reference' => 'CT-' . $cahier->id,
                'date_edition'      => now(),
                'ligne_meta'        => $this->ligneMetaSeance($cahier),
            ]),
            'cahier-' . $cahier->id . '.pdf',
            PdfCollection::CAHIER_TEXTE,
            $cahier,
            'Cahier de texte · Réf. CT-' . $cahier->id
        );
    }

    public function downloadHistory(
        Eleve $eleve,
        ?string $dateDebut,
        ?string $dateFin
    ) {
        $eleve->loadMissing('user');

        $cahiers = $this->getHistory($eleve, $dateDebut, $dateFin);

        return $this->buildPdf(
            self::HISTORY_VIEW,
            array_merge($this->getCabinet(), [
                'eleve'     => $eleve,
                'cahiers'   => $cahiers,
                'dateDebut' => $dateDebut,
                'dateFin'   => $dateFin,
                'document_titre'    => 'Historique des cahiers de texte',
                'document_sous_titre' => trim(
                    ($eleve->user?->prenom ?? '') . ' ' . ($eleve->user?->nom ?? '')
                ),
                'document_reference' => 'CT-HIST-' . $eleve->id,
                'date_edition'      => now(),
                'ligne_meta'        => '<strong>Période :</strong> '
                    . ($dateDebut ?? '—') . ' &rarr; ' . ($dateFin ?? '—'),
            ]),
            $this->makeFilename($eleve, $dateDebut, $dateFin),
            PdfCollection::CAHIER_TEXTE,
            null,
            'Cahiers de texte · Élève ' . $eleve->id
        );
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY LOGIC
    |--------------------------------------------------------------------------
    */

    private function getHistory(
        Eleve $eleve,
        ?string $dateDebut,
        ?string $dateFin
    ): Collection {
        return CahierTexte::query()
            ->with([
                'affectation.matiere',
                'affectation.enseignant.user',
            ])
            ->forEleve($eleve->id)
            ->betweenDates($dateDebut, $dateFin)
            ->orderBy('date_seance')
            ->orderBy('heure_debut')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | PDF BUILDER
    |--------------------------------------------------------------------------
    */


    private function buildPdf(
        string $view,
        array $data,
        string $filename,
        string $mediaCollection = 'cahier_texte_pdf',
        ?object $model = null,
        string $reference = 'Cahier de texte'
    ) {
        // 1. Générer le PDF en mémoire
        $pdf = Pdf::loadView($view, $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);

        // Le pied de page n'est dessinable qu'après le rendu.
        $pdf->render();

        DocumentPdf::piedDePage($pdf->getDomPDF(), $reference);

        $output = $pdf->output();

        if (empty($output)) {
            throw new \RuntimeException(
                "DomPDF a retourné un contenu vide pour [{$view}]. " .
                "Vérifiez la vue et ses données."
            );
        }

        // 2. Attacher à Media Library si demandé (via fichier temp) —
        //    uniquement si le modèle implémente HasMedia et qu'aucun PDF
        //    n'existe déjà dans la collection (évite la duplication à
        //    chaque téléchargement, source de fuite de stockage).
        //    La requête passe par media() (fraîche) et non par la relation
        //    chargée en mémoire, qui peut être obsolète après un addMedia.
        if ($model && method_exists($model, 'media')) {
            $existeDeja = $model->media()
                ->where('collection_name', $mediaCollection)
                ->exists();

            if (!$existeDeja) {
                $tmp = tempnam(sys_get_temp_dir(), 'keduc_pdf_');
                file_put_contents($tmp, $output);

                $model->addMedia($tmp)
                    ->usingFileName($filename)
                    ->toMediaCollection($mediaCollection);
                // addMedia() déplace $tmp — le fichier temp est consommé, pas le PDF final
            }
        }

        // 3. Streamer directement depuis la mémoire — aucun fichier disque
        return response($output, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length'      => strlen($output),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    private function makeFilename(
        Eleve $eleve,
        ?string $dateDebut,
        ?string $dateFin
    ): string {
        $nom = str(
            $eleve->user->nom . '-' . $eleve->user->prenom
        )->slug();

        return sprintf(
            '%s_%s-%s.pdf',
            $nom,
            Carbon::parse($dateDebut)->format('Ymd'),
            Carbon::parse($dateFin)->format('Ymd')
        );
    }

    /**
     * Identité du cabinet courant, mise en forme pour le gabarit commun.
     */
    private function getCabinet(): array
    {
        $cabinet = DocumentPdf::cabinet();

        return [
            'cabinet' => $cabinet,
            'cabinet_nom' => $cabinet['nom'],
            'cabinet_logo' => DocumentPdf::logo($cabinet['logo']),
        ];
    }

    /**
     * Ligne d'en-tête d'une séance : élève, matière, enseignant.
     */
    private function ligneMetaSeance(CahierTexte $cahier): string
    {
        $affectation = $cahier->affectation;
        $eleve = $affectation?->contrat?->eleve?->user;
        $enseignant = $affectation?->enseignant?->user;

        return '<strong>Élève :</strong> '
            . e(trim(($eleve?->prenom ?? '') . ' ' . ($eleve?->nom ?? '')) ?: '—')
            . ' &nbsp;·&nbsp; <strong>Matière :</strong> '
            . e($affectation?->matiere?->nom ?? '—')
            . ' &nbsp;·&nbsp; <strong>Enseignant :</strong> '
            . e(trim(($enseignant?->prenom ?? '') . ' ' . ($enseignant?->nom ?? '')) ?: '—');
    }

    /*
    |--------------------------------------------------------------------------
    | CONSTANTS
    |--------------------------------------------------------------------------
    */

    private const HISTORY_VIEW = 'pdf.cahiers-textes.historique';
    private const SINGLE_VIEW  = 'pdf.cahiers-textes.seance';
}