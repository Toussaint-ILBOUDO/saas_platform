<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'titre',
        'contenu',
        'type',
        'icone',
        'data',
        'lu',
        'date_lecture',
    ];

    protected $casts = [
        'lu' => 'boolean',
        'date_lecture' => 'datetime',
        'data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Icônes Bootstrap par type de notification.
     */
    public static function iconesParType(): array
    {
        return [
            'actualite'               => 'bi-megaphone',
            'librairie_commande'      => 'bi-cart-check',
            'contrat'                 => 'bi-file-earmark-text',
            'affectation'             => 'bi-person-check',
            'facture'                 => 'bi-receipt',
            'paiement_enseignant'     => 'bi-wallet2',
            'rapport'                 => 'bi-clipboard-data',
            'demande_cours'           => 'bi-journal-text',
            'bulletin_paie'           => 'bi-cash-stack',
            'temoignage'              => 'bi-chat-quote',
            'temoignage_moderation'   => 'bi-shield-exclamation',
            'temoignage_signalement'  => 'bi-flag',
            'temoignage_commentaire'  => 'bi-chat-dots',
        ];
    }

    /**
     * Retourne l'icône Bootstrap pour ce type de notification.
     */
    public function getIconeHtmlAttribute(): string
    {
        $classes = static::iconesParType();
        $icone = $this->icone ?? ($classes[$this->type] ?? 'bi-bell');

        return $icone;
    }

    /**
     * Couleur associée au type de notification (nom de classe Bootstrap text-*).
     */
    public function getCouleurAttribute(): string
    {
        return match ($this->type) {
            'facture'                 => 'text-warning',
            'paiement_enseignant'     => 'text-success',
            'bulletin_paie'           => 'text-success',
            'librairie_commande'      => 'text-primary',
            'rapport'                 => 'text-info',
            'demande_cours'           => 'text-info',
            'temoignage_signalement'  => 'text-danger',
            'temoignage_moderation'   => 'text-danger',
            default                   => 'text-primary',
        };
    }

    /**
     * URL de la ressource associée, basée sur le type + data.
     * Retourne null si aucune route pertinente n'existe.
     */
    public function getUrlAttribute(): ?string
    {
        $data = $this->data ?? [];

        return match ($this->type) {
            'contrat', 'affectation' => isset($data['contrat_id'])
                ? route('contrats.show', $data['contrat_id'])
                : null,

            'facture' => isset($data['facture_id'])
                ? route('finance.factures.show', $data['facture_id'])
                : null,

            'librairie_commande' => isset($data['commande_id'])
                ? route('admin.librairie.commandes.show', $data['commande_id'])
                : null,

            'actualite' => route(
                'actualites.show',
                $data['actualite_slug'] ?? $data['actualite_id'] ?? ''
            ),

            'temoignage' => isset($data['temoignage_id'])
                ? route('admin.temoignages.show', $data['temoignage_id'])
                : null,

            'temoignage_moderation' => route('temoignages.mes.index'),

            'temoignage_signalement' => route('admin.temoignages.signalements'),

            'temoignage_commentaire' => isset($data['slug'])
                ? route('temoignages.show', $data['slug'])
                : null,

            'rapport' => isset($data['rapport_id'])
                ? route('rapports-mensuels.show', $data['rapport_id'])
                : null,

            'demande_cours' => isset($data['demande_cours_id'])
                ? route('demande-cours.show', $data['demande_cours_id'])
                : null,

            'paiement_enseignant' => null,
            'bulletin_paie' => null,

            default => null,
        };
    }

    /**
     * Libellé de l'action proposée, adapté au contexte.
     */
    public function getActionLabelAttribute(): ?string
    {
        return match ($this->type) {
            'facture'                 => 'Voir la facture',
            'paiement_enseignant'     => null,
            'bulletin_paie'           => null,
            'librairie_commande'      => 'Voir la commande',
            'rapport'                 => 'Voir le rapport',
            'demande_cours'           => 'Voir la demande',
            'contrat'                 => 'Voir le contrat',
            'affectation'             => 'Voir le contrat',
            'actualite'               => 'Lire l\'actualité',
            'temoignage'              => 'Voir le témoignage',
            'temoignage_moderation'   => 'Mes témoignages',
            'temoignage_signalement'  => 'Voir les signalements',
            'temoignage_commentaire'  => 'Voir le témoignage',
            default                   => null,
        };
    }
}
