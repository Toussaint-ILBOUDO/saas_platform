<?php

namespace App\Modules\Finance\Services;

use App\Models\PeriodeComptable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * D-051 — Cycle de vie des périodes comptables.
 *
 * KEduc faisait `update(['statut' => 'cloturee'])` sans tracer QUI avait clos
 * ni QUAND, pouvait clôturer une période déjà close, supprimait une période
 * sans contrôle et laissait `update()` accepter n'importe quelle valeur de
 * statut (donc rouvrir une période close via un simple formulaire de
 * modification).
 *
 * La protection contre l'écriture sur une période close n'est PAS dans ce
 * service : elle est dans `GardePeriodeOuverte`, appelée par les services
 * d'écriture (facturation, bulletin, rapport, cahier de texte).
 */
class PeriodeComptableService
{
    public function create(array $data): PeriodeComptable
    {
        return DB::transaction(function () use ($data) {
            $periode = new PeriodeComptable([
                'label' => $data['label'],
                'date_debut' => $data['date_debut'],
                'date_fin' => $data['date_fin'],
                'type' => $data['type'] ?? 'mensuel',
                // Toute période naît ouverte : un statut choisi à la création
                // permettrait de créer une période close, donc non saisissable.
                'statut' => PeriodeComptable::OUVERTE,
            ]);

            // Contrôle avant écriture : `verifierBornes()` exclut la période
            // elle-même, donc elle n'existe pas encore en base.
            $this->verifierBornes($periode, $periode);

            $periode->save();

            return $periode;
        });
    }

    public function update(PeriodeComptable $periode, array $data): PeriodeComptable
    {
        return DB::transaction(function () use ($periode, $data) {
            // Une période close est gelée : on ne modifie plus ses dates ni son
            // libellé, sinon les écritures déjà rattachées pointeraient vers une
            // période qui ne couvre plus les dates concernées.
            if ($periode->estCloturee() && $this->contientModificationsStructurelles($data)) {
                throw ValidationException::withMessages([
                    'periode' => 'Une période clôturée ne peut plus être modifiée.',
                ]);
            }

            $autorisees = array_intersect_key(
                $data,
                array_flip(['label', 'date_debut', 'date_fin', 'type'])
            );

            $periode->fill($autorisees);

            // `fill()` ne touche pas la base : on contrôle les valeurs en
            // mémoire avant d'écrire.
            $this->verifierBornes($periode, $periode);

            $periode->save();

            return $periode->fresh();
        });
    }

    /**
     * Clôture : gel de la période et traçabilité de qui a clos.
     */
    public function close(PeriodeComptable $periode): PeriodeComptable
    {
        return DB::transaction(function () use ($periode) {
            if ($periode->estCloturee()) {
                throw ValidationException::withMessages([
                    'periode' => 'Cette période est déjà clôturée.',
                ]);
            }

            $periode->update([
                'statut' => PeriodeComptable::CLOTUREE,
                'cloturee_par' => Auth::id(),
                'cloturee_at' => now(),
            ]);

            return $periode->fresh();
        });
    }

    /**
     * Réouverture : réservée à une correction d'erreur, et tracée.
     *
     * KEduc ne proposait pas de réouverture, mais sans elle une clôture
     * erronée bloquait le cabinet sans issue (les factures ne se font plus).
     * On refuse en revanche de rouvrir une période qui a déjà servi à des
     * écritures financières : ces documents seraient incohérents avec les
     * heures retenues après réouverture.
     */
    public function reopen(PeriodeComptable $periode): PeriodeComptable
    {
        return DB::transaction(function () use ($periode) {
            if (! $periode->estCloturee()) {
                throw ValidationException::withMessages([
                    'periode' => 'Cette période est déjà ouverte.',
                ]);
            }

            if ($this->aDesEcrituresFinancieres($periode)) {
                throw ValidationException::withMessages([
                    'periode' => 'Impossible de rouvrir : des factures ou des bulletins existent sur cette période.',
                ]);
            }

            $periode->update([
                'statut' => PeriodeComptable::OUVERTE,
                'cloturee_par' => null,
                'cloturee_at' => null,
            ]);

            return $periode->fresh();
        });
    }

    public function delete(PeriodeComptable $periode): bool
    {
        // Supprimer une période effacerait le rattachement de tous ses
        // rapports, factures et bulletins : on l'interdit.
        throw ValidationException::withMessages([
            'periode' => 'Une période comptable ne peut pas être supprimée.',
        ]);
    }

    public function list(int $perPage = 15)
    {
        return PeriodeComptable::orderBy('date_debut', 'desc')->paginate($perPage);
    }

    /**
     * Les données modifiées touchent-elles la structure de la période ?
     */
    private function contientModificationsStructurelles(array $data): bool
    {
        return array_key_exists('date_debut', $data)
            || array_key_exists('date_fin', $data)
            || array_key_exists('type', $data);
    }

    /**
     * Une facture ou un bulletin existe-t-il sur cette période ?
     */
    private function aDesEcrituresFinancieres(PeriodeComptable $periode): bool
    {
        return \App\Models\Facture::query()
                ->where('periode_id', $periode->id)
                ->exists()
            || \App\Models\BulletinPaie::query()
                ->where('periode_id', $periode->id)
                ->exists();
    }

    /**
     * Les bornes sont ordonnées et ne se chevauchent pas avec une autre période.
     */
    private function verifierBornes(
        PeriodeComptable $periode,
        PeriodeComptable $ignoree
    ): PeriodeComptable {
        if ($periode->date_fin->lt($periode->date_debut)) {
            throw ValidationException::withMessages([
                'date_fin' => 'La date de fin doit être postérieure à la date de début.',
            ]);
        }

        $chevauchement = PeriodeComptable::query()
            ->when($ignoree->exists, fn ($q) => $q->whereKeyNot($ignoree->getKey()))
            ->whereDate('date_debut', '<=', $periode->date_fin)
            ->whereDate('date_fin', '>=', $periode->date_debut)
            ->first();

        if ($chevauchement) {
            throw ValidationException::withMessages([
                'date_debut' => sprintf(
                    'Ces dates chevauchent la période « %s » (%s → %s).',
                    $chevauchement->label,
                    $chevauchement->date_debut->format('d/m/Y'),
                    $chevauchement->date_fin->format('d/m/Y')
                ),
            ]);
        }

        return $periode;
    }
}