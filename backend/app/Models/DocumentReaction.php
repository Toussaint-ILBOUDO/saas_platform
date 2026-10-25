<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Réaction « J'aime / Je n'aime pas » d'une IDENTITÉ sur un document.
 *
 * L'identité est membre connecté (`user_id`) ou visiteur anonyme
 * (`visiteur_id`, identifiant de session serveur). La colonne `identifiant`
 * regroupe les deux cas en une clé unique : une identité ne peut avoir
 * qu'une seule réaction par document.
 */
class DocumentReaction extends Model
{
    public const AIME = 'aime';
    public const N_AIME_PAS = 'n_aime_pas';

    public const VALIDES = [
        self::AIME,
        self::N_AIME_PAS,
    ];

    protected $table = 'document_reactions';

    protected $fillable = [
        'document_bibliotheque_id',
        'user_id',
        'identifiant',
        'reaction',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(DocumentBibliotheque::class, 'document_bibliotheque_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Clé d'identité d'un membre connecté. */
    public static function identifiantMembre(int $userId): string
    {
        return 'u:'.$userId;
    }

    /** Clé d'identité d'un visiteur anonyme (uuid de session). */
    public static function identifiantVisiteur(string $visiteurId): string
    {
        return 'v:'.Str::limit($visiteurId, 60, '');
    }

    /**
     * Identifiant de session du visiteur anonyme : généré une fois, conservé
     * dans la session serveur (même durée de vie que la session). C'est cet
     * identifiant qui empêche un visiteur de multiplier ses réactions sans
     * collecter aucune donnée personnelle.
     */
    public static function visiteurId(): string
    {
        $id = session()->get('bibliotheque.visiteur_id');

        if (! is_string($id) || $id === '') {
            $id = (string) Str::uuid();
            session()->put('bibliotheque.visiteur_id', $id);
        }

        return $id;
    }

    /**
     * Identité de la requête courante : membre connecté s'il y en a un,
     * sinon visiteur de session. `null` uniquement si la session est
     * indisponible (jamais en HTTP réel).
     */
    public static function identiteCourante(): ?string
    {
        if ($userId = auth()->id()) {
            return self::identifiantMembre((int) $userId);
        }

        return self::identifiantVisiteur(self::visiteurId());
    }
}
