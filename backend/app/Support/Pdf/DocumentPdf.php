<?php

namespace App\Support\Pdf;

use App\Support\FicheCabinet;
use Dompdf\Css\Color;
use Dompdf\Dompdf;

/**
 * Éléments partagés par les documents PDF du cabinet : identité de l'en-tête
 * et pied de page paginé.
 *
 * L'identité provient de `FicheCabinet::courante()` — la même donnée que le
 * front (`parametres_publics.data`) — et JAMAIS de la config legacy
 * `keduc.cabinet`, qui mélangeait les coordonnées entre cabinets.
 */
final class DocumentPdf
{
    /**
     * Identité aplanie du cabinet courant, prête pour la vue Blade.
     */
    public static function cabinet(): array
    {
        $identite = FicheCabinet::courante();
        $fiche = $identite['fiche'];

        return [
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
    }

    /**
     * Convertit le logo en source lisible par DomPDF : data URI pour un fichier
     * local (un chemin disque est ignoré dès que `setOptions()` est utilisé),
     * URL conserve telle quelle.
     */
    public static function logo(mixed $logo): ?string
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
     * Filet + mentions de pied de page sur CHAQUE page, dans la marge basse :
     * référence du document à gauche, « Page X / Y » à droite.
     *
     * À appeler APRÈS `$pdf->render()` : dompdf ne connaît le nombre de pages
     * qu'une fois le flux posé (CSS `counter(page)` non supporté).
     */
    public static function piedDePage(Dompdf $dompdf, string $reference): void
    {
        $margeGauche = 42.0;
        $margeDroite = 553.28;
        $ligneFilet = 776.0;
        $ligneTexte = 787.0;
        $taille = 8.0;

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
}
