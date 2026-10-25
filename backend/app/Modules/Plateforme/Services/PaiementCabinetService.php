<?php

namespace App\Modules\Plateforme\Services;

use App\Models\Cabinet;
use App\Models\FactureCabinet;
use App\Models\PaiementCabinet;
use App\Models\JournalPlateforme;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * P8 — Encaissements d'une facture de cabinet : partiel (statut
 * `partiellement_payée`) ou complet (statut `payé`), refus du
 * sur-encaissement (montant_paye ≤ montant_total, Q-A16/D-048).
 */
final class PaiementCabinetService
{
    /** Modes affichés dans le formulaire (aucun tarif codé en dur ici). */
    public const MODES = [
        'especes' => 'Espèces',
        'orange' => 'Orange Money',
        'moov' => 'Moov Money',
        'virement' => 'Virement bancaire',
        'cheque' => 'Chèque',
        'autre' => 'Autre',
    ];

    public function enregistrer(
        FactureCabinet $facture,
        int $montant,
        ?string $cle,
        ?string $reference,
        ?string $commentaire,
        ?int $superAdminId,
        ?string $datePaiement = null,
    ): FactureCabinet {
        if ($facture->estAnnulee()) {
            throw ValidationException::withMessages(['facture' => 'Facture annulée — encaissement impossible.']);
        }

        if (! $facture->estEmise() && ! $facture->estPartiellementPayee()) {
            throw ValidationException::withMessages(['facture' => 'Seule une facture émise peut être encaissée.']);
        }

        if ($montant <= 0) {
            throw ValidationException::withMessages(['montant' => 'Le montant du paiement doit être strictement positif.']);
        }

        $mode = trim((string) $cle);

        if ($mode === '') {
            throw ValidationException::withMessages(['mode' => 'Mode de paiement requis.']);
        }

        $dejaPaye = (int) $facture->paiements()->sum('montant');

        if ($dejaPaye + $montant > $facture->montant_total) {
            throw ValidationException::withMessages([
                'montant' => sprintf(
                    'Le paiement excède le reste à payer (%s FCFA).',
                    number_format($facture->montant_total - $dejaPaye, 0, ',', ' ')
                ),
            ]);
        }

        return DB::transaction(function () use ($facture, $montant, $mode, $reference, $commentaire, $superAdminId, $datePaiement) {
            $paiement = new PaiementCabinet;
            $paiement->facture_cabinet_id = $facture->getKey();
            $paiement->montant = $montant;
            $paiement->mode = $mode;
            $paiement->reference = trim((string) $reference) !== '' ? mb_substr(trim((string) $reference), 0, 200) : null;
            $paiement->commentaire = mb_substr((string) $commentaire, 0, 500) ?: null;
            $paiement->date_paiement = $datePaiement !== null
                ? Carbon::parse($datePaiement)
                : now();
            $paiement->enregistre_par_user = $superAdminId;
            $paiement->save();

            $total = (int) $facture->paiements()->sum('montant');
            $facture->montant_paye = $total;
            $facture->statut = $total >= $facture->montant_total
                ? FactureCabinet::STATUT_PAYEE
                : FactureCabinet::STATUT_PARTIELLEMENT_PAYEE;
            $facture->save();

            JournalPlateforme::ecrire('paiement.enregistre', 'info', Cabinet::find($facture->cabinet_id), [
                'facture' => $facture->numero_facture,
                'montant' => $montant,
                'mode' => $mode,
                'cumul' => $total,
                'statut' => $facture->statut,
            ]);

            return $facture->refresh();
        });
    }

    public function lister(FactureCabinet $facture)
    {
        return $facture->paiements()->orderByDesc('date_paiement')->orderByDesc('id')->get();
    }
}
