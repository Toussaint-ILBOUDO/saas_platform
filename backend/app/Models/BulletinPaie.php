<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BulletinPaie extends Model
{
    protected $table = 'bulletins_paie';

    /**
     * D-052 — Catégories de contestation (liste fermée).
     *
     * Un commentaire libre de 1 000 caractères ne permet ni de trier les
     * contestations, ni de voir d'où viennent les litiges récurrents (taux
     * faux, séance manquante, retenue contestée…). La catégorie est contrôlée
     * par l'application ; le détail reste libre.
     *
     * @var array<string, string>
     */
    public const MOTIFS_CONTESTATION = [
        'heures' => 'Heures retenues incorrectes',
        'taux' => 'Taux horaire incorrect',
        'seance_manquante' => 'Séance non enregistrée',
        'seance_en_double' => 'Séance comptée deux fois',
        'ajustement' => 'Prime ou retenue contestée',
        'periode' => 'Mauvaise période de rattachement',
        'autre' => 'Autre motif',
    ];

    protected $fillable = [
        'numero',
        'enseignant_id',
        'periode_id',
        'total_heures',
        'montant_brut',
        'frais_suivi',
        'montant_net',
        'statut',
        'commentaire_enseignant',
        'motif_contestation',
        'date_consultation',
        'date_validation',
        'date_paiement',
        'mode_paiement',
        'reference_paiement',
        'date_reception',
        'recu_par',
    ];

    protected $casts = [
        'total_heures'      => 'decimal:2',
        'montant_brut'      => 'integer',
        'frais_suivi'       => 'integer',
        'montant_net'       => 'integer',
        'date_consultation' => 'datetime',
        'date_validation'   => 'datetime',
        'date_paiement'     => 'date',
        'date_reception'    => 'datetime',
    ];

    // ======================
    // RELATIONS
    // ======================

    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(
            EnseignantProfil::class,
            'enseignant_id'
        );
    }

    public function periode(): BelongsTo
    {
        return $this->belongsTo(
            PeriodeComptable::class,
            'periode_id'
        );
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(
            BulletinPaieLigne::class,
            'bulletin_paie_id'
        );
    }

    public function ajustements(): HasMany
    {
        return $this->hasMany(
            BulletinPaieAjustement::class,
            'bulletin_paie_id'
        );
    }

    /**
     * L'enseignant qui a confirmé avoir reçu le paiement (D-052).
     */
    public function recaperePar(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'recu_par'
        );
    }

    // ======================
    // HELPERS
    // ======================

    public function estBrouillon(): bool
    {
        return $this->statut === 'brouillon';
    }

    public function estGenere(): bool
    {
        return $this->statut === 'genere';
    }

    public function estConsulte(): bool
    {
        return $this->statut === 'consulte';
    }

    public function estValide(): bool
    {
        return $this->statut === 'valide';
    }

    public function estVerse(): bool
    {
        return $this->statut === 'verse';
    }

    public function estConteste(): bool
    {
        return $this->statut === 'conteste';
    }

    public function estCorrige(): bool
    {
        return $this->statut === 'corrige';
    }

    /**
     * D-052 — Le paiement a-t-il été réceptionné par l'enseignant ?
     *
     * Un bulletin versé n'est pas « terminé » : tant que l'enseignant n'a pas
     * confirmé la réception, l'administration doit pouvoir retrouver les
     * versements en attente (espèces versées mais non remises, virement non
     * parvenu…).
     */
    public function estRecu(): bool
    {
        return $this->statut === 'verse'
            && $this->date_reception !== null;
    }

    /**
     * Versé, mais réception non confirmée : à relancer.
     */
    public function enAttenteReception(): bool
    {
        return $this->statut === 'verse'
            && $this->date_reception === null;
    }

    /**
     * Libellé lisible de la catégorie de contestation.
     */
    public function getLibelleMotifContestationAttribute(): ?string
    {
        return $this->motif_contestation
            ? (self::MOTIFS_CONTESTATION[$this->motif_contestation] ?? 'Autre motif')
            : null;
    }

    public function getTotalPrimesAttribute(): int
    {
        return $this->ajustements
            ->where('type', 'prime')
            ->sum('montant');
    }

    public function getTotalRetenuesAttribute(): int
    {
        return $this->ajustements
            ->where('type', 'retenue')
            ->sum('montant');
    }

    public function getMontantNetFinalAttribute(): int
    {
        return $this->montant_brut
            - $this->frais_suivi
            + $this->total_primes
            - $this->total_retenues;
    }
}
