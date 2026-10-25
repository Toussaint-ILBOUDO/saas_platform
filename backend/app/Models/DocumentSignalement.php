<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentSignalement extends Model
{
    public const MOTIFS = [
        'document_incorrect' => 'Document incorrect',
        'contenu_inapproprie' => 'Contenu inapproprié',
        'fichier_illisible' => 'Fichier illisible',
        'doublon' => 'Doublon',
        'probleme_droits' => 'Problème de droits',
        'autre' => 'Autre',
    ];

    public const STATUTS = [
        'en_attente' => 'En attente',
        'en_cours' => 'En cours de traitement',
        'traite' => 'Traité',
        'rejete' => 'Rejeté',
    ];

    protected $fillable = [
        'user_id',
        'document_bibliotheque_id',
        'motif',
        'description',
        'precisions',
        'statut',
        'note_admin',
        'traite_par_user_id',
        'traite_at',
        'ip_hash',
        'email_contact',
    ];

    protected function casts(): array
    {
        return [
            'traite_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function traitePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'traite_par_user_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(DocumentBibliotheque::class, 'document_bibliotheque_id');
    }

    public static function libelleMotif(string $motif): string
    {
        return self::MOTIFS[$motif] ?? $motif;
    }

    public static function libelleStatut(string $statut): string
    {
        return self::STATUTS[$statut] ?? $statut;
    }
}
