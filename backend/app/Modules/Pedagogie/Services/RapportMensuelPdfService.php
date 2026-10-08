<?php

namespace App\Modules\Pedagogie\Services;

use App\Models\RapportMensuelEnseignant;
use App\Support\FicheCabinet;
use Barryvdh\DomPDF\Facade\Pdf;
use Dompdf\Css\Color;
use Dompdf\Dompdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class RapportMensuelPdfService
{
    public function __construct(
        protected RapportMensuelCalculator $calculator,
    ) {}
    /**
     * Télécharger le PDF.
     */
    public function download(
        RapportMensuelEnseignant $rapport
    ): Response {

        $pdf = $this->make($rapport);

        return $pdf->download(
            $this->filename($rapport)
        );
    }


    /**
     * Afficher le PDF dans le navigateur.
     */
    public function stream(
        RapportMensuelEnseignant $rapport
    ): Response {

        $pdf = $this->make($rapport);

        return $pdf->stream(
            $this->filename($rapport)
        );
    }


    /**
     * Sauvegarder le PDF dans le storage.
     */
    public function save(
        RapportMensuelEnseignant $rapport
    ): string {


        $pdf = $this->make($rapport);


        $path = "rapports-mensuels/"
            .$this->filename($rapport);


        Storage::disk('local')
            ->put(
                $path,
                $pdf->output()
            );


        return $path;
    }



    /**
     * Création du PDF DomPDF.
     */
    protected function make(
        RapportMensuelEnseignant $rapport
    ) {


        $data = $this->prepareData($rapport);


        $pdf = Pdf::loadView(
                'pedagogie.rapport-mensuel.rapport-mensuel',
                $data
            )
            ->setPaper(
                'A4',
                'portrait'
            )
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);

        // Le pied de page « Page X / Y » ne peut être dessiné qu'après le
        // rendu : dompdf ne connaît le nombre de pages qu'une fois le flux
        // posé (les callbacks `page_script` parcourent alors les pages).
        $pdf->render();

        $this->dessinerPiedDePage($pdf->getDomPDF(), $data);


        return $pdf;
    }


    /**
     * Filet + mentions de pied de page sur CHAQUE page, dans la marge basse :
     * référence du document à gauche, « Page X / Y » aligné à droite.
     *
     * DomPDF ne gère ni les marges `@page` ni les compteurs CSS `counter(page)`
     * : la pagination passe par un callback canvas (`page_text`/`page_script`).
     */
    protected function dessinerPiedDePage(Dompdf $dompdf, array $data): void
    {
        // A4 portrait : 595.28 pt de large, marge de 42 pt de chaque côté.
        $margeGauche = 42.0;
        $margeDroite = 553.28;
        $ligneFilet = 776.0;
        $ligneTexte = 787.0;
        $taille = 8.0;

        $reference = 'Rapport mensuel · Réf. ' . $data['numero'];

        $dompdf->getCanvas()->page_script(
            static function (
                int $numero,
                int $total,
                $canvas,
                $fontMetrics
            ) use ($margeGauche, $margeDroite, $ligneFilet, $ligneTexte, $taille, $reference): void {

                $font = $fontMetrics->getFont('Helvetica');
                $gris = Color::parse('#64748b');
                $filet = Color::parse('#cbd5e1');

                $canvas->line(
                    $margeGauche,
                    $ligneFilet,
                    $margeDroite,
                    $ligneFilet,
                    $filet,
                    0.6
                );

                $canvas->text(
                    $margeGauche,
                    $ligneTexte,
                    $reference,
                    $font,
                    $taille,
                    $gris
                );

                $pagination = "Page {$numero} / {$total}";
                $x = $margeDroite
                    - $fontMetrics->getTextWidth($pagination, $font, $taille);

                $canvas->text($x, $ligneTexte, $pagination, $font, $taille, $gris);
            }
        );
    }



    /**
     * Préparation des données envoyées à Blade.
     *
     * Le PDF veut montrer TROIS familles d'informations :
     *  1. les données automatiques (élève, période, heures, ventilation,
     *     bilan des activités lu depuis le cahier de texte) ;
     *  2. les sections/éléments administrés avec les réponses de l'enseignant ;
     *  3. l'identité du cabinet (nom, logo, slogan, contacts) en en-tête.
     *
     * Public : le test de périmètre (« seules SES matières ») assert sur cette
     * préparation sans avoir à extraire le texte du PDF.
     */
    public function prepareData(
        RapportMensuelEnseignant $rapport
    ): array {

        $rapport->loadMissing([

            /*
             | Professeur
             */
            'enseignant.user',


            /*
             | Contrat
             */
            'contratCours.eleve.user',
            'contratCours.eleve.parent',
            'contratCours.eleve.classe',
            'contratCours.typeCours',


            /*
             | Matières
             */
            'contratCours.affectations.matiere',


            /*
             | Période & ventilation persistée
             */
            'periode',
            'lignes.affectation.matiere',

        ]);


        $contrat = $rapport->contratCours;


        /*
         | Seules les affectations de L'ENSEIGNANT : un contrat peut être
         | partagé entre plusieurs professeurs, et le PDF ne doit présenter
         | que les matières dont l'enseignant a été chargé sur ce cours
         | (le calculateur applique le même filtre pour les heures).
         */
        $affectations = $contrat
            ? $contrat->affectations
                ->where('enseignant_id', $rapport->enseignant_id)
            : collect();


        // Relu du cahier de texte : même logique que le rapport lui-même,
        // pour que le « bilan des activités » du PDF soit exactement celui
        // qui a produit la ventilation.
        $stats = $this->calculator->calculate(
            contratId: (int) $rapport->contrat_cours_id,
            enseignantId: (int) $rapport->enseignant_id,
            periodeId: (int) $rapport->periode_id,
        );

        /*
         | Identité du cabinet CONCERNÉ (tenant courant) : même source que le
         | front (`parametres_publics.data`) via `FicheCabinet`. Le document ne
         | doit jamais afficher les coordonnées d'un autre cabinet (la config
         | legacy `keduc.cabinet` est proscrite ici).
         */
        $identite = FicheCabinet::courante();
        $fiche = $identite['fiche'];

        $cabinet = [
            'nom' => $identite['nom'],
            'slogan' => $fiche['identite']['slogan'],
            'directeur' => $fiche['identite']['directeur'],
            'telephone' => $fiche['contact']['telephone'],
            'telephone_2' => $fiche['contact']['telephone_2'],
            'whatsapp' => $fiche['contact']['whatsapp'],
            'email' => $fiche['contact']['email'],
            'adresse' => $fiche['contact']['adresse'],
            'horaires' => $fiche['contact']['horaires'],
            'logo' => $identite['logo'],
        ];

        return [

            'rapport' => $rapport,


            'enseignant' =>
                $rapport->enseignant,


            'professeur' =>
                $rapport
                    ->enseignant
                    ?->user,


            'contrat' =>
                $contrat,


            'eleve' =>
                $contrat?->eleve,


            'parent' =>
                $contrat?->eleve?->parent,


            'classe' =>
                $contrat?->eleve?->classe,


            'typeCours' =>
                $contrat?->typeCours,


            'periode' =>
                $rapport->periode,


            'matieres' =>
                $affectations
                    ->pluck('matiere')
                    ->filter()
                    ->unique('id')
                    ->values(),


            'affectations' =>
                $affectations,


            /*
             | Activités réalisées (cahier de texte) + ventilation paramétrée
             */
            'cahiers' =>
                $stats['cahiers'],

            'total_seances' =>
                $stats['nombre_seances'],

            'lignes' =>
                $rapport->lignes,


            /*
             | Modèle de rapport : sections/éléments + réponses de l'enseignant
             */
            'sections' =>
                $rapport->sectionsRenseignees(),


            /*
             | En-tête : identité COMPLÈTE du cabinet (nom, slogan, téléphones,
             | whatsapp, email, adresse) pour un en-tête digne de ce nom.
             */
            'cabinet' =>
                $cabinet,

            'cabinet_nom' =>
                $cabinet['nom'],

            'cabinet_logo' =>
                $this->resoudreLogo($cabinet['logo']),

            /*
             | Référence du document + date d'édition (bandeau de titre,
             | pied de page).
             */
            'numero' =>
                sprintf(
                    'RM-%04d/%d',
                    $rapport->id,
                    $rapport->periode?->date_debut?->year
                        ?? now()->year
                ),

            'date_edition' =>
                now(),

        ];
    }

    /**
     * Résout le logo vers une source lisible par DomPDF :
     * un data URI (fichier local) ou l'URL si c'en est une.
     */
    protected function resoudreLogo(mixed $logo): ?string
    {
        if (empty($logo)) {
            return null;
        }

        $candidates = [
            $logo,
            base_path((string) $logo),
            public_path((string) $logo),
            storage_path('app/private/' . ltrim((string) $logo, '/')),
            storage_path('app/public/' . ltrim((string) $logo, '/')),
        ];

        foreach ($candidates as $chemin) {
            if (! is_file($chemin)) {
                continue;
            }

            $extension = strtolower(pathinfo($chemin, PATHINFO_EXTENSION));
            $mime = [
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
            ][$extension] ?? null;

            if ($mime === null || filesize($chemin) > 2_000_000) {
                return $chemin;
            }

            /*
             | DomPDF n'intègre pas les chemins disque (`C:\...`) dans le PDF
             | dès que setOptions() pose chroot/protocoles : un data URI est
             | accepté dans tous les cas et l'image est dédupliquée sur les
             | pages multiples.
             */
            $contenu = file_get_contents($chemin);

            if ($contenu === false) {
                return $chemin;
            }

            return 'data:' . $mime . ';base64,' . base64_encode($contenu);
        }

        if (filter_var((string) $logo, FILTER_VALIDATE_URL)) {
            return (string) $logo;
        }

        return null;
    }




    /**
     * Nom propre du fichier.
     */
    protected function filename(
        RapportMensuelEnseignant $rapport
    ): string {


        $nom = str_replace(
            ' ',
            '-',
            strtolower(
                $rapport
                    ->enseignant
                    ?->user
                    ?->nom
                    ?? 'enseignant'
            )
        );


        $periode = str_replace(
            ' ',
            '-',
            strtolower(
                $rapport
                    ->periode
                    ?->label
                    ?? now()->format('Y-m')
            )
        );


        return "rapport-mensuel-{$nom}-{$periode}.pdf";
    }
}