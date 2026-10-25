@extends('pdf.layouts.document')

@section('document_contenu')

    {{-- Informations enseignant / période --}}
    <div class="section">
        <div class="section-titre">Informations</div>

        <table class="infos">
            <tr>
                <td class="lbl">Enseignant</td>
                <td class="val">
                    {{ $bulletin->enseignant?->user?->prenom }}
                    {{ $bulletin->enseignant?->user?->nom ?? '—' }}
                </td>

                <td class="lbl">Téléphone</td>
                <td class="val">{{ $bulletin->enseignant?->user?->telephone_appel ?? '—' }}</td>
            </tr>

            <tr>
                <td class="lbl">Période</td>
                <td class="val">{{ $bulletin->periode?->label ?? '—' }}</td>

                <td class="lbl">Du / au</td>
                <td class="val">
                    {{ $bulletin->periode?->date_debut?->format('d/m/Y') ?? '—' }}
                    &ndash;
                    {{ $bulletin->periode?->date_fin?->format('d/m/Y') ?? '—' }}
                </td>
            </tr>
        </table>
    </div>


    {{-- Détail des prestations --}}
    <div class="section">
        <div class="section-titre">Détail des prestations</div>

        <table class="data">
            <thead>
                <tr>
                    <th style="width: 30%">Famille / Élève</th>
                    <th style="width: 22%">Matière</th>
                    <th class="num" style="width: 14%">Heures</th>
                    <th class="num" style="width: 16%">Taux horaire</th>
                    <th class="num" style="width: 18%">Montant</th>
                </tr>
            </thead>

            <tbody>
                @forelse($bulletin->lignes as $ligne)
                    <tr class="{{ $loop->index % 2 === 1 ? 'alt' : '' }}">
                        <td>{{ $ligne->eleve?->user?->nom }} {{ $ligne->eleve?->user?->prenom }}</td>
                        <td>{{ $ligne->matiere?->nom ?? '—' }}</td>
                        <td class="num">{{ number_format((float) $ligne->nombre_heures, 2) }} h</td>
                        <td class="num">{{ number_format($ligne->taux_horaire, 0, ',', ' ') }} F</td>
                        <td class="num">
                            <strong>{{ number_format($ligne->montant, 0, ',', ' ') }} F</strong>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="color:#94a3b8; font-style:italic;">
                            Aucune prestation sur la période.
                        </td>
                    </tr>
                @endforelse

                <tr class="total">
                    <td colspan="2">Total</td>
                    <td class="num">{{ number_format((float) $bulletin->total_heures, 2) }} h</td>
                    <td></td>
                    <td class="num">{{ number_format($bulletin->montant_brut, 0, ',', ' ') }} F</td>
                </tr>
            </tbody>
        </table>
    </div>


    {{-- Ajustements (primes / retenues) --}}
    @if($bulletin->ajustements->isNotEmpty())
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
                    @foreach($bulletin->ajustements as $ajustement)
                        <tr class="{{ $loop->index % 2 === 1 ? 'alt' : '' }}">
                            <td>{{ ucfirst($ajustement->type) }}</td>
                            <td>{{ $ajustement->libelle }}</td>
                            <td class="num">
                                @if($ajustement->type === 'prime')
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
                    <td>Montant brut</td>
                    <td class="num">{{ number_format($bulletin->montant_brut, 0, ',', ' ') }} F</td>
                </tr>
                <tr class="alt">
                    <td>Frais de suivi</td>
                    <td class="num">- {{ number_format($bulletin->frais_suivi, 0, ',', ' ') }} F</td>
                </tr>
                @if($total_primes > 0)
                    <tr>
                        <td>Primes</td>
                        <td class="num">+ {{ number_format($total_primes, 0, ',', ' ') }} F</td>
                    </tr>
                @endif
                @if($total_retenues > 0)
                    <tr class="alt">
                        <td>Retenues</td>
                        <td class="num">- {{ number_format($total_retenues, 0, ',', ' ') }} F</td>
                    </tr>
                @endif
                <tr class="total">
                    <td>Net à payer</td>
                    <td class="num">{{ number_format($montant_net_final, 0, ',', ' ') }} F</td>
                </tr>
            </tbody>
        </table>
    </div>

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
        <div class="role">L'enseignant</div>
    </td>

    <td>
        <div class="trait"></div>
        <div class="role">L'administration</div>
    </td>
@endsection
