<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class CahierTexte extends Model implements HasMedia
{
    use InteractsWithMedia;
    protected $table = 'cahier_textes';

    protected $fillable = [
        'affectation_enseignant_id',
        'date_seance',
        'heure_debut',
        'heure_fin',
        'duree_heures',
        'contenu_cours',
        'objectifs_atteints',
        'observations',
        // Colonne existante en base mais absente du `$fillable` : sans elle, un
        // client hors ligne ne pouvait pas rattacher sa reprise à une séance.
        //
        // `valide_admin` en revanche a été **supprimée** par D-051 (migration
        // `2026_09_30_000002`) : la validation d'une séance appartient au
        // rapport mensuel, pas à la ligne. La garder en `$fillable` faisait
        // échouer toute écriture de masse qui la mentionnait.
        'uuid_client',
    ];

    protected $casts = [
        'date_seance' => 'date',
    ];

    // Relations

    public function affectation(): BelongsTo
    {
        return $this->belongsTo(AffectationEnseignant::class, 'affectation_enseignant_id');
    }

    public function scopeForEleve(
        Builder $query,
        int $eleveId
    ): Builder {

        return $query->whereHas(
            'affectation.contrat',
            fn ($q) => $q->where(
                'eleve_id',
                $eleveId
            )
        );
    }

    public function scopeForTeacher(
        Builder $query,
        int $enseignantId
    ): Builder {

        return $query->whereHas(
            'affectation',
            fn ($q) => $q->where(
                'enseignant_id',
                $enseignantId
            )
        );
    }

    public function scopeBetweenDates(
        Builder $query,
        ?string $debut,
        ?string $fin
    ): Builder {

        return $query

            ->when(
                $debut,
                fn ($q) => $q->whereDate(
                    'date_seance',
                    '>=',
                    $debut
                )
            )

            ->when(
                $fin,
                fn ($q) => $q->whereDate(
                    'date_seance',
                    '<=',
                    $fin
                )
            );
    }

    // Médias

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cahier_texte_pdf')
            ->singleFile()
            ->useDisk('private_media');
    }

}
