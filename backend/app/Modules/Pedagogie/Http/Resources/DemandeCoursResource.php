<?php

namespace App\Modules\Pedagogie\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DemandeCoursResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom_parent' => $this->nom_parent,
            'prenom_parent' => $this->prenom_parent,
            'telephone' => $this->telephone,
            'telephone_whatsapp' => $this->telephone_whatsapp,
            // Numéro réellement contactable + lien WhatsApp direct : le backoffice
            // appelle le parent en un clic, sans ressaisir le numéro.
            'numero_whatsapp' => $this->numeroWhatsApp(),
            'lien_whatsapp' => $this->when(
                (bool) $this->numeroWhatsApp(),
                fn () => 'https://wa.me/'.self::chiffresWhatsapp($this->numeroWhatsApp())
            ),
            'volume_horaire_estime' => (int) $this->volume_horaire_estime,
            'statut' => $this->statut,
            'message' => $this->message,

            'classe' => $this->whenLoaded('classe', fn () => $this->classe ? [
                'id' => $this->classe->id,
                'nom' => $this->classe->nom,
                'sigle' => $this->classe->sigle,
            ] : null),

            'type_cours' => $this->whenLoaded('typeCours', fn () => $this->typeCours ? [
                'id' => $this->typeCours->id,
                'code' => $this->typeCours->code,
                'libelle' => $this->typeCours->libelle,
            ] : null),

            'matieres' => MatiereResource::collection($this->whenLoaded('matieres')),

            // Dossier créé depuis la demande. Présents dès qu'une action a été
            // jouée : la fiche affiche alors « déjà créé » plutôt que de
            // reproposer un bouton qui créerait un doublon.
            'parent_cree' => $this->whenLoaded('parent', fn () => $this->parent ? [
                'id' => $this->parent->id,
                'nom' => $this->parent->nom,
                'prenom' => $this->parent->prenom,
                'email' => $this->parent->email,
            ] : null),

            'eleve_cree' => $this->whenLoaded('eleve', fn () => $this->eleve ? [
                'id' => $this->eleve->id,
                'user_id' => $this->eleve->user_id,
                'classe' => $this->eleve->relationLoaded('classe') && $this->eleve->classe ? [
                    'id' => $this->eleve->classe->id,
                    'nom' => $this->eleve->classe->nom,
                ] : null,
            ] : null),

            'contrat_cree' => $this->whenLoaded('contratCours', fn () => $this->contratCours ? [
                'id' => $this->contratCours->id,
                'statut' => $this->contratCours->statut,
                'date_debut' => $this->contratCours->date_debut?->toDateString(),
            ] : null),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Normalise un numéro vers le format international attendu par `wa.me`.
     *
     * WhatsApp n'accepte que des chiffres, sans `+`, espaces ni tirets. Les
     * saisies locales sont préfixées du pays (`+226 70 12 34 56`) : sans lui,
     * `wa.me/70123456` est interprété comme un numéro étranger et la
     * conversation ne s'ouvre pas.
     */
    private static function chiffresWhatsapp(?string $numero): string
    {
        $brut = preg_replace('/\D+/', '', (string) $numero) ?? '';

        if ($brut === '') {
            return '';
        }

        // Préfixe international saisi en « 00 226 … ».
        if (str_starts_with($brut, '00')) {
            $brut = substr($brut, 2);
        }

        if (str_starts_with($brut, '226')) {
            return $brut;
        }

        // Numéro national : les saisies locales commencent souvent par 0
        // (« 06 70 80 91 0 »), à retirer avant de compter les 8 chiffres.
        $national = ltrim($brut, '0');

        return strlen($national) === 8 ? '226'.$national : $brut;
    }
}