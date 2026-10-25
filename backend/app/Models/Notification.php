<?php

namespace App\Models;

use App\Support\RoutesAngular;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

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
     * Route Angular de la ressource, à utiliser par l'espace (front).
     *
     * L'ancien accesseur `url` produisait des URL **Blade** (`/factures/12`)
     * pour le backoffice historique : elles sortaient de l'application
     * Angular et visaient le domaine central au lieu du domaine du cabinet.
     * Il a été retiré avec le web KEduc (T7A.11), dont les routes ne sont
     * plus servies.
     *
     * La résolution est déléguée à `RoutesAngular` (T7A.10) : le canal mail
     * du `NotificationDispatcher` retrouve les mêmes chemins pour le même
     * destinataire — une seule source, deux canaux.
     *
     * Retourne null quand aucun écran n'existe encore pour ce type — l'écran
     * notifications affiche alors la notification sans lien, ce qui est
     * préférable à un lien mort.
     */
    public function getRouteAngularAttribute(): ?string
    {
        return RoutesAngular::pour(Auth::user(), $this->type, $this->data ?? []);
    }

    /**
     * Libellé de l'action proposée, adapté au contexte.
     */
    public function getActionLabelAttribute(): ?string
    {
        return match ($this->type) {
            'facture'                 => 'Voir la facture',
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
