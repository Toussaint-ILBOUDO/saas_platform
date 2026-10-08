<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemandeCours extends Model
{
    /**
     * Statuts d'une demande de cours.
     *
     * `annulee` couvre le refus commercial (« nous ne donnons pas suite »). La
     * valeur existait déjà dans le commentaire de la migration de la table sans
     * être câblée nulle part : la réintroduire ici évite d'ajouter un quatrième
     * statut qui aurait exactement le même sens.
     *
     * @var list<string>
     */
    public const STATUTS = ['en_attente', 'traitee', 'annulee'];

    public const EN_ATTENTE = 'en_attente';

    public const TRAITEE = 'traitee';

    public const ANNULEE = 'annulee';

    protected $table = 'demande_cours';

    protected $fillable = [
        'nom_parent',
        'prenom_parent',
        'telephone',
        'telephone_whatsapp',
        'type_cours_id',
        'classe_id',
        'volume_horaire_estime',
        'statut',
        'message',

        // Dossier créé depuis la demande (cf. `DemandeCoursAdminService`).
        // Écrits par les services, jamais par le formulaire public : le
        // `StoreDemandeCoursRequest` ne valide pas ces clés, donc un client
        // extérieur ne peut pas s'y injecter.
        'parent_id',
        'eleve_id',
        'contrat_cours_id',
    ];

    // Relations

    public function typeCours()
    {
        return $this->belongsTo(TypeCours::class);
    }

    public function classe()
    {
        return $this->belongsTo(Classe::class);
    }

    public function matieres()
    {
        return $this->belongsToMany(
            Matiere::class,
            'demande_cours_matieres'
        );
    }

    /**
     * Parent créé depuis cette demande.
     *
     * Pointe vers `users.id` (et non `parent_profils.id`) : c'est la clé que
     * référence `eleves.parent_id`, donc celle qui permet de rattacher l'élève
     * créé ensuite. Voir `DemandeCoursAdminService::creerEleve`.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Eleve::class);
    }

    public function contratCours(): BelongsTo
    {
        return $this->belongsTo(ContratCours::class);
    }

    /**
     * Numéro à utiliser pour un contact WhatsApp.
     *
     * Le champ WhatsApp saisi prime, sinon on retombe sur le téléphone unique
     * des demandes créées avant son introduction : une demande de plus de deux
     * ans doit rester joignable.
     */
    public function numeroWhatsApp(): ?string
    {
        return $this->telephone_whatsapp ?: $this->telephone;
    }
}