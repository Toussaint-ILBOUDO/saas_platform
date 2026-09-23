<?php

namespace App\Modules\Pedagogie\Services;

use App\Models\RapportMensuelEnseignant;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class RapportMensuelPdfService
{
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


        return Pdf::loadView(
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
    }



    /**
     * Préparation des données envoyées à Blade.
     */
    protected function prepareData(
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
             | Période
             */
            'periode',

        ]);



        $contrat = $rapport->contratCours;


        $affectations = $contrat
            ? $contrat->affectations
            : collect();



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
                    ->unique('id'),


            'affectations' =>
                $affectations,

        ];
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