<?php

namespace App\Modules\Pedagogie\Services;

use App\Models\CahierTexte;
use App\Models\Eleve;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
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

        return $this->buildPdf(
            self::SINGLE_VIEW,
            [
                'cabinet' => $this->getCabinet(),
                'cahier'  => $cahier,
            ],
            'cahier-' . $cahier->id . '.pdf',
            PdfCollection::CAHIER_TEXTE,
            $cahier
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
            [
                'cabinet'   => $this->getCabinet(),
                'eleve'     => $eleve,
                'cahiers'   => $cahiers,
                'dateDebut' => $dateDebut,
                'dateFin'   => $dateFin,
            ],
            $this->makeFilename($eleve, $dateDebut, $dateFin),
            PdfCollection::CAHIER_TEXTE,
            null
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
        ?object $model = null
    ) {
        // 1. Générer le PDF en mémoire
        $pdf = Pdf::loadView($view, $data)
            ->setPaper('a4', 'portrait');

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

    private function getCabinet(): array
    {
        return config('keduc.cabinet');
    }

    /*
    |--------------------------------------------------------------------------
    | CONSTANTS
    |--------------------------------------------------------------------------
    */

    private const HISTORY_VIEW = 'pdf.cahiers-textes.historique';
    private const SINGLE_VIEW  = 'pdf.cahiers-textes.seance';
}