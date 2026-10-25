@extends('pdf.layouts.document')

@section('document_contenu')

    <div class="section">
        <div class="section-titre">Liste des séances</div>

        <table class="data">
            <thead>
                <tr>
                    <th style="width: 18%">Date</th>
                    <th style="width: 28%">Matière</th>
                    <th style="width: 34%">Enseignant</th>
                    <th class="num" style="width: 20%">Durée</th>
                </tr>
            </thead>

            <tbody>
                @forelse($cahiers as $cahier)
                    <tr class="{{ $loop->index % 2 === 1 ? 'alt' : '' }}">
                        <td>{{ $cahier->date_seance?->format('d/m/Y') ?? '—' }}</td>
                        <td>{{ $cahier->affectation?->matiere?->nom ?? '—' }}</td>
                        <td>
                            {{ $cahier->affectation?->enseignant?->user?->prenom }}
                            {{ $cahier->affectation?->enseignant?->user?->nom }}
                        </td>
                        <td class="num">{{ number_format((float) $cahier->duree_heures, 1) }} h</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="color:#94a3b8; font-style:italic;">
                            Aucune séance sur la période.
                        </td>
                    </tr>
                @endforelse

                <tr class="total">
                    <td colspan="3">Total</td>
                    <td class="num">
                        {{ number_format((float) $cahiers->sum('duree_heures'), 1) }} h
                    </td>
                </tr>
            </tbody>
        </table>
    </div>


    <div class="section">
        <div class="section-titre">Détail des séances</div>

        @forelse($cahiers as $cahier)
            <div style="margin-top: 11px; page-break-inside: avoid;">
                <div style="font-weight: bold; color:#12305e;">
                    {{ $cahier->date_seance?->format('d/m/Y') ?? '—' }}
                    &mdash;
                    {{ $cahier->affectation?->matiere?->nom ?? '—' }}
                </div>
                <div class="cadre">{!! nl2br(e($cahier->contenu_cours)) !!}</div>
            </div>
        @empty
            <div class="cadre" style="color:#94a3b8; font-style:italic;">
                Aucune séance à détailler.
            </div>
        @endforelse
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
