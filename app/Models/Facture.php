<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Facture extends Model
{
    protected $fillable = [
        'contrat_cours_id',
        'parent_id',
        'eleve_id',
        'periode_id',
        'numero_facture',
        'volume_horaire_total',
        'frais_suivi',
        'autres_frais',
        'remise',
        'montant_total',
        'commentaire',
        'statut_paiement',
        'date_paiement',
        'mode_paiement',
        'date_limite_paiement',
        'reference_paiement',
        'statut_paiement_enseignants',
    ];

    protected $casts = [
        'volume_horaire_total' => 'decimal:2',
        'frais_suivi'          => 'integer',
        'autres_frais'         => 'integer',
        'remise'               => 'integer',
        'montant_total'        => 'integer',
        'date_paiement'        => 'date',
        'date_limite_paiement' => 'date',
    ];

    // ======================
    // RELATIONS
    // ======================

    public function contrat(): BelongsTo
    {
        return $this->belongsTo(ContratCours::class, 'contrat_cours_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Eleve::class);
    }

    public function periode(): BelongsTo
    {
        return $this->belongsTo(PeriodeComptable::class, 'periode_id');
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(LigneFacture::class);
    }

    // ======================
    // HELPERS
    // ======================

    public function estPayee(): bool
    {
        return $this->statut_paiement === 'payee';
    }

    public function estEnAttente(): bool
    {
        return $this->statut_paiement === 'en_attente';
    }

    public function estPartiellementPayee(): bool
    {
        return $this->statut_paiement === 'partiel';
    }

    public function tousEnseignantsPayes(): bool
    {
        return $this->statut_paiement_enseignants === 'paye';
    }

    public function getMontantSuiviAttribute(): int
    {
        return $this->frais_suivi + $this->autres_frais - $this->remise;
    }
}
