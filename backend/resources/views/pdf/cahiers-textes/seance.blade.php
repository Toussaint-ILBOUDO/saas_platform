@extends('pdf.layouts.document')

@section('document_contenu')

    {{-- Fiche de la séance --}}
    <div class="section">
        <div class="section-titre">Fiche de la séance</div>

        <table class="infos">
            <tr>
                <td class="lbl">Date</td>
                <td class="val">{{ $cahier->date_seance?->format('d/m/Y') ?? '—' }}</td>

                <td class="lbl">Horaire</td>
                <td class="val">
                    {{ substr((string) $cahier->heure_debut, 0, 5) }}
                    &ndash;
                    {{ substr((string) $cahier->heure_fin, 0, 5) }}
                </td>
            </tr>

            <tr>
                <td class="lbl">Élève</td>
                <td class="val">
                    {{ $cahier->affectation?->contrat?->eleve?->user?->prenom }}
                    {{ $cahier->affectation?->contrat?->eleve?->user?->nom ?? '—' }}
                </td>

                <td class="lbl">Durée</td>
                <td class="val">{{ number_format((float) $cahier->duree_heures, 1) }} h</td>
            </tr>

            <tr>
                <td class="lbl">Matière</td>
                <td class="val">{{ $cahier->affectation?->matiere?->nom ?? '—' }}</td>

                <td class="lbl">Enseignant</td>
                <td class="val">
                    {{ $cahier->affectation?->enseignant?->user?->prenom }}
                    {{ $cahier->affectation?->enseignant?->user?->nom ?? '—' }}
                </td>
            </tr>
        </table>
    </div>


    <div class="section">
        <div class="section-titre">Contenu du cours</div>
        <div class="cadre">{!! nl2br(e($cahier->contenu_cours)) !!}</div>
    </div>


    <div class="section">
        <div class="section-titre">Objectifs atteints</div>
        <div class="cadre">
            @if (! empty($cahier->objectifs_atteints))
                {!! nl2br(e($cahier->objectifs_atteints)) !!}
            @else
                <span style="color:#94a3b8; font-style:italic;">Non renseigné.</span>
            @endif
        </div>
    </div>


    <div class="section">
        <div class="section-titre">Observations</div>
        <div class="cadre">
            @if (! empty($cahier->observations))
                {!! nl2br(e($cahier->observations)) !!}
            @else
                <span style="color:#94a3b8; font-style:italic;">Non renseigné.</span>
            @endif
        </div>
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
