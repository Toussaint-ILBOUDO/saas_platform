@extends('pdf.layouts.document')

@section('document_contenu')

    {{-- Informations de la facture --}}
    <div class="section">
        <div class="section-titre">Informations de la facture</div>

        <table class="infos">
            <tr>
                <td class="lbl">Élève</td>
                <td class="val">
                    {{ $eleve?->user?->prenom }} {{ $eleve?->user?->nom ?? '—' }}
                </td>

                <td class="lbl">Classe</td>
                <td class="val">{{ $eleve?->classe?->sigle ?? '—' }}</td>
            </tr>

            <tr>
                <td class="lbl">Parent</td>
                <td class="val">
                    {{ $parent?->prenom }} {{ $parent?->nom ?? '—' }}
                </td>

                <td class="lbl">Type de cours</td>
                <td class="val">{{ $facture->contrat?->typeCours?->libelle ?? '—' }}</td>
            </tr>

            <tr>
                <td class="lbl">Période</td>
                <td class="val">{{ $periode?->label ?? '—' }}</td>

                <td class="lbl">Date limite</td>
                <td class="val">
                    {{ $facture->date_limite_paiement?->format('d/m/Y') ?? 'Non définie' }}
                </td>
            </tr>
        </table>
    </div>


    {{-- Détail des cours --}}
    <div class="section">
        <div class="section-titre">Détail des cours</div>

        <table class="data">
            <thead>
                <tr>
                    <th style="width: 30%">Enseignant</th>
                    <th style="width: 22%">Matière</th>
                    <th class="num" style="width: 14%">Heures</th>
                    <th class="num" style="width: 16%">Taux/h</th>
                    <th class="num" style="width: 18%">Montant</th>
                </tr>
            </thead>

            <tbody>
                @forelse($lignes as $ligne)
                    <tr class="{{ $loop->index % 2 === 1 ? 'alt' : '' }}">
                        <td>
                            {{ $ligne->affectation?->enseignant?->user?->prenom }}
                            {{ $ligne->affectation?->enseignant?->user?->nom }}
                        </td>
                        <td>{{ $ligne->affectation?->matiere?->nom ?? '—' }}</td>
                        <td class="num">{{ number_format((float) $ligne->nombre_heures, 1) }} h</td>
                        <td class="num">{{ number_format($ligne->taux_horaire, 0, ',', ' ') }} F</td>
                        <td class="num">
                            <strong>{{ number_format($ligne->montant, 0, ',', ' ') }} F</strong>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="color:#94a3b8; font-style:italic;">
                            Aucun cours facturé.
                        </td>
                    </tr>
                @endforelse

                <tr class="total">
                    <td colspan="2">Total</td>
                    <td class="num">{{ number_format((float) $facture->volume_horaire_total, 1) }} h</td>
                    <td></td>
                    <td class="num">{{ number_format($montantCours, 0, ',', ' ') }} F</td>
                </tr>
            </tbody>
        </table>
    </div>


    {{-- Ajustements (bonus / débits) --}}
    @if($ajustements->isNotEmpty())
        <div class="section">
            <div class="section-titre">Ajustements</div>

            <table class="data">
                <thead>
                    <tr>
                        <th style="width: 30%">Type</th>
                        <th>Libellé</th>
                        <th class="num" style="width: 22%">Montant</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($ajustements as $ajustement)
                        <tr class="{{ $loop->index % 2 === 1 ? 'alt' : '' }}">
                            <td>{{ $ajustement->typeAjustement?->libelle ?? ucfirst($ajustement->type) }}</td>
                            <td>{{ $ajustement->libelle }}</td>
                            <td class="num">
                                @if($ajustement->estCredit())
                                    + {{ number_format($ajustement->montant, 0, ',', ' ') }} F
                                @else
                                    - {{ number_format($ajustement->montant, 0, ',', ' ') }} F
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif


    {{-- Récapitulatif --}}
    <div class="section">
        <div class="section-titre">Récapitulatif</div>

        <table class="data" style="width: 58%; margin-left: 42%;">
            <tbody>
                <tr>
                    <td>Cours ({{ number_format((float) $facture->volume_horaire_total, 1) }} h)</td>
                    <td class="num">{{ number_format($montantCours, 0, ',', ' ') }} F</td>
                </tr>

                @if($facture->frais_suivi > 0)
                    <tr class="alt">
                        <td>Frais de suivi</td>
                        <td class="num">{{ number_format($facture->frais_suivi, 0, ',', ' ') }} F</td>
                    </tr>
                @endif

                @if($facture->autres_frais > 0)
                    <tr class="{{ $facture->frais_suivi > 0 ? '' : 'alt' }}">
                        <td>Autres frais</td>
                        <td class="num">{{ number_format($facture->autres_frais, 0, ',', ' ') }} F</td>
                    </tr>
                @endif

                @if($facture->remise > 0)
                    <tr class="alt">
                        <td>Remise</td>
                        <td class="num">- {{ number_format($facture->remise, 0, ',', ' ') }} F</td>
                    </tr>
                @endif

                @if($total_credits > 0)
                    <tr>
                        <td>Crédits (bonus)</td>
                        <td class="num">+ {{ number_format($total_credits, 0, ',', ' ') }} F</td>
                    </tr>
                @endif

                @if($total_debits > 0)
                    <tr class="alt">
                        <td>Débits</td>
                        <td class="num">- {{ number_format($total_debits, 0, ',', ' ') }} F</td>
                    </tr>
                @endif

                <tr class="total">
                    <td>Total à payer</td>
                    <td class="num">{{ number_format($facture->montant_total, 0, ',', ' ') }} F</td>
                </tr>
            </tbody>
        </table>
    </div>


    {{-- Détail du paiement --}}
    @if($facture->estPayee())
        <div class="section">
            <div class="section-titre">Paiement reçu</div>

            <table class="infos">
                <tr>
                    <td class="lbl">Statut</td>
                    <td class="val">Payée</td>
                    <td class="lbl">Date</td>
                    <td class="val">{{ $facture->date_paiement?->format('d/m/Y') ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="lbl">Mode</td>
                    <td class="val">
                        {{ ucfirst(str_replace('_', ' ', $facture->mode_paiement ?? '—')) }}
                    </td>
                    <td class="lbl">Référence</td>
                    <td class="val">{{ $facture->reference_paiement ?? '—' }}</td>
                </tr>
            </table>
        </div>
    @endif

@endsection


@section('document_mention')
    @if (! empty($cabinet['adresse'] ?? null))
        Fait à {{ $cabinet['adresse'] }}, le
    @else
        Fait le
    @endif
    {{ ($date_edition ?? now())->format('d/m/Y') }}
@endsection


@section('document_signatures')
    <td>
        <div class="trait"></div>
        <div class="role">Le parent</div>
    </td>

    <td>
        <div class="trait"></div>
        <div class="role">L'administration</div>
    </td>
@endsection
