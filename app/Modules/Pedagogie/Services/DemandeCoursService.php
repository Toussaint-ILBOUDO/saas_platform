<?php

namespace App\Modules\Pedagogie\Services;

use App\Models\DemandeCours;
use Illuminate\Support\Facades\DB;
use App\Modules\Systeme\Services\NotificationDispatcher;

class DemandeCoursService
{
    public function create(array $data): DemandeCours
    {
        return DB::transaction(function () use ($data) {

            $notifier = app(NotificationDispatcher::class);

            $demande = DemandeCours::create([
                'nom_parent' => $data['nom_parent'],
                'prenom_parent' => $data['prenom_parent'],
                'telephone' => $data['telephone'],
                'type_cours_id' => $data['type_cours_id'],
                'classe_id' => $data['classe_id'],
                'volume_horaire_estime' => $data['volume_horaire_estime'],
                'statut' => 'en_attente',
                'message' => $data['message'] ?? null,
            ]);
            $notifier->courseRequestCreated($demande);

            $demande->matieres()->attach($data['matieres'] ?? []);

            return $demande;
        });
    }
}