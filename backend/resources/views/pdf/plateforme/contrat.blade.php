@extends('pdf.plateforme.layouts.plateforme')

@section('document_titre', 'Contrat commercial')
@php
    $document_sous_titre = ($plateforme_nom ?? 'Magis Plus Center')
        .' — service de gestion de cabinet';
    $tarifsAvecLibelle = collect($tarifs ?? [])->mapWithKeys(
        fn ($t) => [$t['code'] => $t['libelle']],
    )->all();
@endphp

@section('document_contenu')

    {{-- Identification du cabinet et du contrat --}}
    <table class="infos">
        <tr>
            <td class="lbl">Cabinet</td>
            <td class="val">{{ $cabinet_nom }}</td>
            <td class="lbl">N° contrat</td>
            <td class="val">{{ $contrat->numero }}</td>
        </tr>
        <tr>
            <td class="lbl">Statut</td>
            <td class="val">v{{ $contrat->version }} — {{ $contrat->statut }}</td>
            <td class="lbl">Date d'effet</td>
            <td class="val">{{ $contrat->date_effet?->format('d/m/Y') ?? '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">Fin de validité</td>
            <td class="val">{{ $contrat->date_fin_validite?->format('d/m/Y') ?? '—' }}</td>
            <td class="lbl">Validé le</td>
            <td class="val">{{ $contrat->valide_le?->format('d/m/Y') ?? '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">Responsable cabinet</td>
            <td class="val">{{ $contrat->responsable_nom ?? '—' }}</td>
            <td class="lbl">Modalités</td>
            <td class="val">{{ $contrat->modalites_paiement ?? '—' }}</td>
        </tr>
    </table>

    {{-- Tarifs négociés (chaque code est opposable au cabinet) --}}
    <div class="section">
        <div class="section-titre">Tarifs négociés</div>

        <table class="data">
            <thead>
                <tr>
                    <th>Service</th>
                    <th class="num" style="width: 34%">Montant / taux</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($tarifs as $tarif)
                    <tr class="{{ $loop->odd ? 'alt' : '' }}">
                        <td>
                            {{ $tarif['libelle'] }}
                            @if ($tarif['exonere'])<b style="color:#15803d"> (non facturable)</b>@endif
                            @if (! empty($tarif['description']))
                                <span class="desc">{{ $tarif['description'] }}</span>
                            @endif
                        </td>
                        <td class="num">
                            @if ($tarif['valeur'] === null)
                                —
                            @elseif (str_starts_with((string) $tarif['code'], 'taux_'))
                                {{ number_format((float) $tarif['valeur'], 2, ',', ' ') }} %
                            @else
                                {{ number_format((float) $tarif['valeur'], 0, ',', ' ') }} FCFA
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="section-note">
            « — » = service non couvert par ce contrat : aucun montant n'est facturé.
        </div>
    </div>

        @if (is_array($contrat->exonerations) && $contrat->exonerations !== [])
            <div class="section">
                <div class="section-titre">Exonérations</div>
                <div class="cadre">
                    Services non facturés pendant ce contrat :
                    {{ implode(', ', collect($contrat->exonerations)
                        ->map(fn ($code) => $tarifsAvecLibelle[$code] ?? $code)->all()) }}.
                </div>
            </div>
        @endif

    @if ($contrat->conditions_particulieres)
        <div class="section">
            <div class="section-titre">Conditions particulières</div>
            <div class="cadre">{{ $contrat->conditions_particulieres }}</div>
        </div>
    @endif

@endsection

@section('document_signatures')
    <td>
        <div class="trait"></div>
        <div class="role">Pour la plateforme</div>
    </td>

    <td>
        <div class="trait"></div>
        <div class="role">{{ $cabinet_nom }}</div>
    </td>
@endsection
