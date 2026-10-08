<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * D-051 — Période comptable : porte le gel de la chaîne pédagogique et financière.
 *
 * Une période close n'accepte plus aucune écriture (séance de cahier, objectif,
 * dépôt de rapport, facture, bulletin, ajustement, versement). Le service de
 * garde est `App\Modules\Finance\Services\GardePeriodeOuverte` — il est appelé
 * par tous les services d'écriture, pas par les contrôleurs, pour qu'aucun
 * chemin (API, web, commande, job) ne puisse la contourner.
 */
class PeriodeComptable extends Model
{
    public const OUVERTE = 'ouverte';

    public const CLOTUREE = 'cloturee';

    protected $fillable = [
        'label',
        'date_debut',
        'date_fin',
        'type',
        'statut',
        'cloturee_par',
        'cloturee_at',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'cloturee_at' => 'datetime',
    ];

    public function estOuverte(): bool
    {
        return $this->statut === self::OUVERTE;
    }

    public function estCloturee(): bool
    {
        return $this->statut === self::CLOTUREE;
    }

    public function clotureur()
    {
        return $this->belongsTo(User::class, 'cloturee_par');
    }

    /**
     * La période contient-elle la date donnée ? Bornes incluses.
     */
    public function contient(?\DateTimeInterface $date): bool
    {
        if (! $date) {
            return false;
        }

        $jour = $date instanceof \DateTimeImmutable
            ? $date->format('Y-m-d')
            : $date->format('Y-m-d');

        return $this->date_debut->format('Y-m-d') <= $jour
            && $jour <= $this->date_fin->format('Y-m-d');
    }

    /**
     * Période comptable contenant une date donnée, ou null.
     */
    public static function pourDate(\DateTimeInterface|string $date): ?self
    {
        $jour = $date instanceof \DateTimeInterface
            ? $date->format('Y-m-d')
            : $date;

        return static::query()
            ->whereDate('date_debut', '<=', $jour)
            ->whereDate('date_fin', '>=', $jour)
            ->orderByDesc('date_debut')
            ->first();
    }

    public function scopeOuvertes($query)
    {
        return $query->where('statut', self::OUVERTE);
    }

    public function scopeCloturees($query)
    {
        return $query->where('statut', self::CLOTUREE);
    }
}