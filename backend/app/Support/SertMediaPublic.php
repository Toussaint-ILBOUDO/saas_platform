<?php

namespace App\Support;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;

/**
 * Service d'un média stocké sur disque tenant via Spatie.
 *
 * En multi-tenant, l'URL `/storage/…` générée par `getFirstMediaUrl` ne
 * résout pas : le disque public du cabinet est réécrit vers
 * `storage/cabinet<id>/app/public/` alors que `public/storage` ne pointe
 * nulle part. Comme pour le logo (`/api/public/logo`), le binaire passe
 * par une route contrôlée.
 *
 * Retour typé `Symfony\Component\HttpFoundation\Response` : la classe
 * Laravel `Illuminate\Http\Response` n'est PAS le parent de
 * `BinaryFileResponse` (retourné par `response()->file()`), d'où un
 * TypeError sinon.
 */
trait SertMediaPublic
{
    protected function repondreMedia(?Media $media, ?string $conversion = null): Response
    {
        abort_unless($media !== null, 404, 'Aucune image disponible.');

        $chemin = $media->getPath();

        if ($conversion !== null && $media->hasGeneratedConversion($conversion)) {
            $cheminConversion = $media->getPath($conversion);
            if ($cheminConversion !== null && file_exists($cheminConversion)) {
                $chemin = $cheminConversion;
            }
        }

        abort_unless(file_exists($chemin), 404, 'Aucune image disponible.');

        return response()->file($chemin, [
            'Content-Type' => $media->mime_type ?: 'image/jpeg',
            'Cache-Control' => 'public, max-age=604800',
            'Content-Disposition' => 'inline',
        ]);
    }
}
