@extends('pdf.plateforme.layouts.plateforme')

@section('document_titre', 'Facture — service plateforme')
@php
    $document_sous_titre = $facture->numero_facture !== null
        ? 'Facture de service — '.($plateforme_nom ?? 'Magis Plus Center')
        : 'Brouillon de facture (numérotée à l\'émission)';
    $document_reference = $facture->numero_facture ?? '#'.$facture->getKey();

    $libelleStatut = $facture->estPayee() ? 'Payée' : ($facture->estPartiellementPayee() ? 'Partiellement payée' : ($facture->estAnnulee() ? 'Annulée' : ($facture->estBrouillon() ? 'Brouillon' : 'Émise')));
    $classeBadge = $facture->estPayee() ? '' : ($facture->estPartiellementPayee() ? 'warn' : ($facture->estAnnulee() ? 'danger' : ($facture->estBrouillon() ? 'mute' : 'info')));

    $totalDebit = (int) $facture->lignes()->where('sens', 'debit')->sum('montant');
    $totalCredit = (int) $facture->lignes()->where('sens', 'credit')->sum('montant');
@endphp

@section('document_contenu')

    <table class="infos">
        <tr>
            <td class="lbl">Cabinet</td>
            <td class="val">{{ $cabinet_nom }}</td>
            <td class="lbl">Période couverte</td>
            <td class="val">
                du {{ $facture->periode_debut?->format('d/m/Y') }}
                au {{ $facture->periode_fin?->format('d/m/Y') }}
            </td>
        </tr>
        <tr>
            <td class="lbl">Émise le</td>
            <td class="val">{{ $facture->emise_le?->format('d/m/Y H:i') ?? '— (brouillon)' }}</td>
            <td class="lbl">Échéance</td>
            <td class="val">{{ $facture->date_echeance?->format('d/m/Y') ?? '—' }}</td>
        </tr>
    </table>

    {{-- Lignes de facturation : chaque ligne auto est justifiée par la
         source (event et référentiel), chaque ligne manuelle par son motif. --}}
    <div class="section">
        <div class="section-titre">Tableau des facturations — débits et crédits</div>

        <table class="data">
            <thead>
                <tr>
                    <th style="width: 7%">Date</th>
                    <th style="width: 10%">Source</th>
                    <th style="width: 35%">Description</th>
                    <th class="num">Qté</th>
                    <th class="num">P.U.</th>
                    <th class="num" style="width: 17%">Montant (FCFA)</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($facture->lignes()->orderBy('id')->get() as $ligne)
                    <tr class="{{ $loop->odd ? 'alt' : '' }}">
                        <td>
                            {{ $ligne->evenement?->date_evenement?->format('d/m/Y') ?? ($facture->periode_fin?->format('d/m/Y')) }}
                            @if (! $ligne->fiable)<b class="pire" title="à vérifier">*</b>@endif
                        </td>
                        <td style="font-size: 9.4px; color:#64748b">
                            {{ $ligne->source === 'auto' ? 'Auto' : 'Manuelle' }}
                            @if ($ligne->reference_type)
                                <br>{{ str($ligne->reference_type)->limit(14) }} #{{ str($ligne->reference_id)->limit(18) }}
                            @endif
                        </td>
                        <td>
                            {{ $ligne->libelle }}
                            @if ($ligne->motif)
                                <div style="font-size: 9.6px; color:#64748b">motif : {{ $ligne->motif }}</div>
                            @endif
                        </td>
                        <td class="num">{{ $ligne->quantite }}</td>
                        <td class="num">{{ $ligne->prix_unitaire !== null ? number_format((float) $ligne->prix_unitaire, 0, ',', ' ') : '—' }}</td>
                        <td class="num {{ $ligne->sens === 'credit' ? 'credit' : '' }}">
                            {{ $ligne->sens === 'credit' ? '-' : '+' }}
                            {{ number_format((float) $ligne->montant, 0, ',', ' ') }}
                        </td>
                    </tr>
                @endforeach

                @if ($facture->lignes()->count() === 0)
                    <tr><td colspan="6" style="text-align:center;color:#b91c1c">Aucune ligne — la facture ne peut pas être émise.</td></tr>
                @endif
            </tbody>

            <tfoot>
                <tr>
                    <td colspan="5" class="num" style="color:#334155">
                        Total débits
                    </td>
                    <td class="num">+ {{ number_format((float) $totalDebit, 0, ',', ' ') }}</td>
                </tr>
                <tr>
                    <td colspan="5" class="num" style="color:#334155">
                        Total crédits
                    </td>
                    <td class="num credit">- {{ number_format((float) $totalCredit, 0, ',', ' ') }}</td>
                </tr>
                <tr class="total">
                    <td colspan="5" class="num">
                        NET À PAYER
                        @if ($facture->estPartiellementPayee() || $facture->estPayee())
                            —Réglé : {{ number_format((float) $facture->montant_paye, 0, ',', ' ') }} FCFA
                        @endif
                    </td>
                    <td class="num">
                        {{ number_format((float) $facture->montant_total, 0, ',', ' ') }}
                    </td>
                </tr>
            </tfoot>
        </table>

        <div class="section-note">
            + = débit (payable par le cabinet). - = crédit (à déduire du total).
            (*) : reconstitution approximative ; à vérifier avant traitement.
        </div>
    </div>

    {{-- Paiements enregistrés --}}
    @if ($facture->paiements()->count() > 0)
        <div class="section">
            <div class="section-titre">Paiements encaissés</div>
            <table class="data">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Mode</th>
                        <th>Référence</th>
                        <th class="num">Montant (FCFA)</th>
                    </tr>
                </thead>
                @foreach ($facture->paiements()->orderBy('date_paiement')->get() as $paiement)
                    <tr>
                        <td>{{ $paiement->date_paiement?->format('d/m/Y ') }}</td>
                        <td>{{ $paiement->mode }}</td>
                        <td>{{ $paiement->reference ?? '—' }}</td>
                        <td class="num">{{ number_format((float) $paiement->montant, 0, ',', ' ') }}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endif

    @if ($facture->conditions_paiement)
        <div class="section">
            <div class="section-titre">Conditions</div>
            <div class="cadre">{{ $facture->conditions_paiement }}</div>
        </div>
    @endif

    @if ($facture->estAnnulee())
        <div class="section">
            <div class="section-note">Facture annulée le {{ $facture->annulee_le?->format('d/m/Y') }} — {{ $facture->motif_annulation }}</div>
        </div>
    @endif

@endsection

@section('document_mention')
    Facture produite par {{ $plateforme_nom ?? 'Magis Plus Center' }} (édition de la
    plateforme K'EduC) — une facture ne concerne qu'un seul cabinet, seule
    la période affichée vaut.
@endsection
