<?php

namespace App\Modules\Plateforme\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContratCabinet;
use App\Models\FactureCabinet;
use App\Modules\Plateforme\Services\ContratCabinetService;
use App\Modules\Plateforme\Services\FactureCabinetPdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * P8 — API admin cabinet : consultation lecture seule du contrat
 * commercial du cabinet et de ses factures plateforme (§7.8, consignes).
 * Aucune écriture : le contrat et les factures appartiennent au
 * super-admin de la plateforme.
 */
final class CabinetContratApiController extends Controller
{
    /** GET /api/admin/mon-contrat */
    public function index(): JsonResponse
    {
        $cabinetId = (string) tenancy()->tenant->getKey();
        $contrat = ContratCabinet::chaineValidee($cabinetId);

        if ($contrat === null) {
            return response()->json([
                'message' => 'Aucun contrat validé pour ce cabinet — contactez la plateforme.',
            ], 404);
        }

        return response()->json([
            'data' => [
                'contrat' => [
                    'numero' => $contrat->numero,
                    'version' => $contrat->version,
                    'statut' => $contrat->statut,
                    'date_effet' => $contrat->date_effet?->format('Y-m-d'),
                    'date_fin_validite' => $contrat->date_fin_validite?->format('Y-m-d'),
                    'responsable_nom' => $contrat->responsable_nom,
                    'modalites_paiement' => $contrat->modalites_paiement,
                    'conditions_particulieres' => $contrat->conditions_particulieres,
                    'exonerations_codes' => (array) $contrat->exonerations,
                    // Q-A17 : montant null = non négocié ≠ 0 = gratuit négocié.
                    'tarifs' => [
                        'frais_creation_contrat' => $contrat->frais_creation_contrat,
                        'frais_contrat_actif' => $contrat->frais_contrat_actif,
                        'abonnement_mensuel_enseignant' => $contrat->abonnement_mensuel_enseignant,
                        'abonnement_mensuel_parent' => $contrat->abonnement_mensuel_parent,
                        'abonnement_mensuel_eleve' => $contrat->abonnement_mensuel_eleve,
                        'frais_rapport_mensuel' => $contrat->frais_rapport_mensuel,
                        'abonnement_annuel_admin' => $contrat->abonnement_annuel_admin,
                        'maintenance_annuelle_site' => $contrat->maintenance_annuelle_site,
                        'taux_commission_librairie' => $contrat->taux_commission_librairie,
                        'taux_commission_autres' => $contrat->taux_commission_autres,
                    ],
                    // Catalogue éditable : libellé + explication par service
                    // (même mise en forme tarifs → description en dur).
                    'services' => collect(app(ContratCabinetService::class)->grilleServices())
                        ->map(fn ($s) => [
                            'libelle' => (string) $s['libelle'],
                            'description' => $s['description'],
                            'categorie' => (string) $s['categorie'],
                        ])->all(),
                    'categories' => \App\Models\ServicePlateforme::CATEGORIES,
                    'valide_le' => $contrat->valide_le?->format('Y-m-d H:i'),
                ],
            ],
        ]);
    }

    /** GET /api/admin/mes-factures-plateforme */
    public function factures(): JsonResponse
    {
        $cabinetId = (string) tenancy()->tenant->getKey();

        return response()->json([
            'data' => FactureCabinet::query()
                ->where('cabinet_id', $cabinetId)
                ->where('statut', '!=', FactureCabinet::STATUT_ANNULEE)
                ->orderByDesc('periode_debut')
                ->orderByDesc('id')
                ->get(['id', 'numero_facture', 'periode_debut', 'periode_fin', 'statut', 'montant_total', 'montant_paye', 'emise_le'])
                ->map(fn ($facture) => [
                    'id' => $facture->id,
                    'numero' => $facture->numero_facture,
                    'periode_debut' => $facture->periode_debut->format('Y-m-d'),
                    'periode_fin' => $facture->periode_fin->format('Y-m-d'),
                    'statut' => $facture->statut,
                    'montant_total' => $facture->montant_total,
                    'montant_paye' => $facture->montant_paye,
                    'emise_le' => $facture->emise_le?->format('Y-m-d H:i'),
                ])
                ->values(),
        ]);
    }

    /** GET /api/admin/mes-factures-plateforme/{facture} */
    public function facture(int $facture): JsonResponse
    {
        $factureCabinet = FactureCabinet::query()
            ->where('cabinet_id', (string) tenancy()->tenant->getKey())
            ->where('id', $facture)
            ->with('lignes')
            ->firstOrFail();

        return response()->json([
            'data' => [
                'id' => $factureCabinet->id,
                'numero' => $factureCabinet->numero_facture,
                'periode_debut' => $factureCabinet->periode_debut->format('Y-m-d'),
                'periode_fin' => $factureCabinet->periode_fin->format('Y-m-d'),
                'statut' => $factureCabinet->statut,
                'montant_total' => $factureCabinet->montant_total,
                'montant_paye' => $factureCabinet->montant_paye,
                'emise_le' => $factureCabinet->emise_le?->format('Y-m-d H:i'),
                'conditions_paiement' => $factureCabinet->conditions_paiement,
                // Détaillé par ligne : le cabinet voit exactement ce qui a été facturé.
                'lignes' => $factureCabinet->lignes->map(fn ($l) => [
                    'libelle' => $l->libelle,
                    'type' => $l->type,
                    'sens' => $l->sens,
                    'quantite' => (int) $l->quantite,
                    'prix_unitaire' => $l->prix_unitaire,
                    'montant' => (int) $l->montant,
                    'source' => $l->source,
                    'motif' => $l->motif,
                ]),
                'paiements' => $factureCabinet->paiements()->orderBy('date_paiement')->get()->map(fn ($p) => [
                    'montant' => $p->montant,
                    'mode' => $p->mode,
                    'reference' => $p->reference,
                    'date' => $p->date_paiement->format('Y-m-d'),
                ]),
            ],
        ]);
    }

    /** GET /api/admin/mes-factures-plateforme/{facture}/pdf */
    public function facturePdf(int $facture): Response
    {
        $factureCabinet = FactureCabinet::query()
            ->where('cabinet_id', (string) tenancy()->tenant->getKey())
            ->where('id', $facture)
            ->firstOrFail();

        // Brouillons non accessibles au cabinet.
        if (! $factureCabinet->estEmise()) {
            abort(404);
        }

        return new Response(
            app(FactureCabinetPdf::class)->rendre($factureCabinet),
            headers: ['Content-Type' => 'application/pdf'],
        );
    }
}
