<?php

namespace App\Modules\Finance\Services;

use App\Models\PeriodeComptable;
use Illuminate\Validation\ValidationException;

/**
 * D-051 — Garde-fou « période ouverte ».
 *
 * KEduc ne contrôlait le statut d'une période que dans les formulaires web
 * (`factures/create`, `bulletins/preview`, `rapports/create` filtrent sur
 * `statut = ouverte`). Les services eux-mêmes ne vérifiaient rien : l'API, un
 * appel direct au service ou un job pouvaient donc écrire sur une période
 * clôturée — et le Zahl fallback de la date de dépôt n'existait pas.
 *
 * Ce garde est appelé par les **services** d'écriture, jamais par les
 * contrôleurs : il est donc impossible de le contourner par un autre chemin.
 */
class GardePeriodeOuverte
{
    /**
     * @throws ValidationException si la période est clôturée
     */
    public function exigerOuverte(PeriodeComptable $periode, string $contexte = ''): PeriodeComptable
    {
        if ($periode->estCloturee()) {
            throw ValidationException::withMessages([
                'periode' => $contexte
                    ? sprintf(
                        'La période « %s » est clôturée : %s impossible.',
                        $periode->label,
                        $contexte
                    )
                    : sprintf(
                        'La période « %s » est clôturée : cette opération est impossible.',
                        $periode->label
                    ),
            ]);
        }

        return $periode;
    }

    /**
     * Vérifie qu'une date de saisie tombe bien dans une période ouverte.
     *
     * Utilisé par le cahier de texte (une date de séance) et les objectifs : ces
     * écritures ne portent pas de `periode_id`, il faut donc retrouver la
     * période qui contient la date. Si aucune période ne contient la date, la
     * saisie est refusée : sans période, aucune heure ne peut être rattachée à un
     * rapport mensuel, donc aucune facture ni aucun bulletin.
     *
     * @throws ValidationException
     */
    public function exigerPeriodeOuvertePourDate(
        \DateTimeInterface|string $date,
        string $contexte = ''
    ): PeriodeComptable {
        $periode = PeriodeComptable::pourDate($date);

        if (! $periode) {
            throw ValidationException::withMessages([
                'date' => $contexte
                    ? sprintf(
                        'Aucune période comptable ne couvre cette date : %s impossible.',
                        $contexte
                    )
                    : 'Aucune période comptable ne couvre cette date.',
            ]);
        }

        return $this->exigerOuverte($periode, $contexte);
    }

    /**
     * Variante non bloquante : `true` si la date est dans une période ouverte.
     */
    public function estDateOuverte(\DateTimeInterface|string $date): bool
    {
        $periode = PeriodeComptable::pourDate($date);

        return $periode !== null && $periode->estOuverte();
    }
}