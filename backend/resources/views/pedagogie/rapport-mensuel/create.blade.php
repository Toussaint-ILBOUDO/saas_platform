@extends('panel.layouts.app')

@section('title', 'Créer un rapport mensuel')

@section('content')

<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon">
                <i class="bi bi-file-earmark-plus"></i>
            </div>

            <div>
                <h1 class="mb-0">Nouveau rapport mensuel</h1>

                <p class="text-muted mb-0">
                    Évaluation pédagogique de l'évolution de l'élève
                </p>
            </div>
        </div>

        <div class="heading-actions">
            <a href="{{ route('rapports-mensuels.index') }}" class="btn btn-light">
                <i class="bi bi-arrow-left"></i>
                Retour
            </a>
        </div>
    </div>

    <form action="{{ route('rapports-mensuels.store', ['contrat'=>$contrat->id]) }}" method="POST">

        @csrf

        <input type="hidden" name="contrat_cours_id" value="{{ $contrat->id }}">

        <div class="row g-4">

            {{-- ================================
                 FORMULAIRE PRINCIPAL
            ================================= --}}

            <div class="col-lg-8">

                <div class="panel">

                    <div class="panel-header">
                        <div>
                            <h5 class="mb-0">
                                Informations du rapport
                            </h5>

                            <p class="text-muted mb-0">
                                Complétez le bilan pédagogique de la période.
                            </p>
                        </div>
                    </div>

                    <div class="panel-body">

                        {{-- PERIODE COMPTABLE --}}

                        <div class="mb-4">

                            <label class="form-label">
                                Période comptable
                            </label>

                            <select
                                name="periode_id"
                                id="periodeSelect"
                                class="form-select @error('periode_id') is-invalid @enderror"
                            >

                                <option value="">
                                    Sélectionner une période ouverte
                                </option>

                                @foreach($periodes as $periode)

                                    <option
                                        value="{{ $periode->id }}"
                                        data-debut="{{ $periode->date_debut }}"
                                        data-fin="{{ $periode->date_fin }}"
                                        @selected(old('periode_id') == $periode->id)
                                    >
                                        {{ $periode->label }}
                                        —
                                        {{ $periode->date_debut->format('d/m/Y') }}
                                        au
                                        {{ $periode->date_fin->format('d/m/Y') }}
                                    </option>

                                @endforeach

                            </select>

                            @error('periode_id')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                        {{-- DONNEES AUTOMATIQUES --}}

                        <div class="row g-3 mb-4">

                            <div class="col-md-6">

                                <div class="mini-card">

                                    <div class="metric-label">
                                        Nombre de séances
                                    </div>

                                    <div class="metric-value">
                                        <span id="nombreSeances">0</span>
                                    </div>

                                </div>

                            </div>

                            <div class="col-md-6">

                                <div class="mini-card">

                                    <div class="metric-label">
                                        Volume horaire réalisé
                                    </div>

                                    <div class="metric-value">
                                        <span id="volumeHoraire">0</span> h
                                    </div>

                                </div>

                            </div>

                        </div>

                        {{-- BILAN AUTOMATIQUE --}}

                        <div class="mb-4">

                            <label class="form-label">
                                Bilan automatique des séances
                            </label>

                            <textarea
                                id="bilanActivites"
                                name="bilan_activites"
                                rows="8"
                                class="form-control"
                                readonly
                            >{{ old('bilan_activites') }}</textarea>

                            <small class="text-muted">
                                Généré automatiquement depuis les cahiers de texte.
                            </small>

                        </div>

                        {{-- NOTES MATIERES --}}

                        <div class="mb-3">

                            <label class="form-label">
                                Points / notes par matière
                            </label>

                            <textarea
                                name="point_notes_matieres"
                                rows="5"
                                class="form-control @error('point_notes_matieres') is-invalid @enderror"
                                placeholder="Évolution, résultats, progression dans la matière..."
                            >{{ old('point_notes_matieres') }}</textarea>

                            @error('point_notes_matieres')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>
                                                <div class="mb-3">
                            <label class="form-label">
                                Points / notes autres matières
                            </label>

                            <textarea
                                name="point_notes_autres_matieres"
                                rows="4"
                                class="form-control"
                            >{{ old('point_notes_autres_matieres') }}</textarea>
                        </div>


                        <div class="mb-3">
                            <label class="form-label">
                                Difficultés rencontrées
                            </label>

                            <textarea
                                name="difficultes_rencontrees"
                                rows="4"
                                class="form-control"
                            >{{ old('difficultes_rencontrees') }}</textarea>
                        </div>


                        <div class="mb-3">
                            <label class="form-label">
                                Solutions trouvées
                            </label>

                            <textarea
                                name="solutions_trouvees"
                                rows="4"
                                class="form-control"
                            >{{ old('solutions_trouvees') }}</textarea>
                        </div>


                        <div class="mb-3">
                            <label class="form-label">
                                Attentes des parents / élève
                            </label>

                            <textarea
                                name="attentes_parents_eleve"
                                rows="4"
                                class="form-control"
                            >{{ old('attentes_parents_eleve') }}</textarea>
                        </div>


                        <div class="mb-3">
                            <label class="form-label">
                                Attentes de l'administration
                            </label>

                            <textarea
                                name="attentes_administration"
                                rows="4"
                                class="form-control"
                            >{{ old('attentes_administration') }}</textarea>
                        </div>


                        <div class="mb-3">
                            <label class="form-label">
                                Appréciation de l'évolution
                            </label>

                            <textarea
                                name="appreciation_evolution"
                                rows="4"
                                class="form-control"
                            >{{ old('appreciation_evolution') }}</textarea>
                        </div>


                        <div class="mb-4">
                            <label class="form-label">
                                Observations
                            </label>

                            <textarea
                                name="observations"
                                rows="4"
                                class="form-control"
                            >{{ old('observations') }}</textarea>
                        </div>


                        <div class="d-flex justify-content-end gap-2">

                            <a href="{{ route('rapports-mensuels.index') }}"
                               class="btn btn-light">
                                Annuler
                            </a>

                            <button type="submit"
                                    class="btn btn-primary">
                                <i class="bi bi-check-circle"></i>
                                Enregistrer le rapport
                            </button>

                        </div>

                    </div>

                </div>

            </div>


            {{-- APERCU DROITE --}}

            <div class="col-lg-4">

                <div class="panel mb-4">

                    <div class="panel-header">
                        <h5 class="mb-0">
                            Aperçu
                        </h5>
                    </div>

                    <div class="panel-body">

                        <div class="info-list">

                            <div>
                                <span>
                                    Élève
                                </span>

                                <strong>
                                    {{ $contrat->eleve->user->prenom }}
                                    {{ $contrat->eleve->user->nom }}
                                </strong>
                            </div>


                            <div>
                                <span>
                                    Matière
                                </span>

                                <strong>
                                    @foreach($contrat->affectations as $affectation)
                                        {{ $affectation->matiere->nom }}<br>
                                    @endforeach
                                </strong>
                            </div>


                            <div>
                                <span>
                                    Enseignant
                                </span>

                                <strong>
                                    {{ auth()->user()->prenom }}
                                    {{ auth()->user()->nom }}
                                </strong>
                            </div>


                            <div>
                                <span>
                                    Statut
                                </span>

                                <strong>
                                    Nouveau
                                </strong>
                            </div>

                        </div>

                    </div>

                </div>

                                <div class="panel">

                    <div class="panel-header">
                        <h5 class="mb-0">
                            Conseils
                        </h5>
                    </div>

                    <div class="panel-body">

                        <ul class="list-unstyled mb-0">

                            <li class="mb-2">
                                <i class="bi bi-check-circle text-success"></i>
                                Décrire les progrès réalisés
                            </li>

                            <li class="mb-2">
                                <i class="bi bi-check-circle text-success"></i>
                                Mentionner les difficultés rencontrées
                            </li>

                            <li class="mb-2">
                                <i class="bi bi-check-circle text-success"></i>
                                Proposer des solutions adaptées
                            </li>

                            <li>
                                <i class="bi bi-check-circle text-success"></i>
                                Garder une appréciation professionnelle
                            </li>

                        </ul>

                    </div>

                </div>

            </div>

        </div>

    </form>

</div>

@endsection


@push('scripts')

<script>
document.addEventListener('DOMContentLoaded', function () {

    const periodeSelect = document.getElementById('periodeSelect');
    const volumeHoraire = document.getElementById('volumeHoraire');
    const nombreSeances = document.getElementById('nombreSeances');
    const bilanActivites = document.getElementById('bilanActivites');


    periodeSelect.addEventListener('change', function () {

        const periodeId = this.value;


        if (!periodeId) {

            volumeHoraire.textContent = '0';
            nombreSeances.textContent = '0';
            bilanActivites.value = '';

            return;
        }


        volumeHoraire.textContent = '...';
        nombreSeances.textContent = '...';
        bilanActivites.value = 'Chargement du bilan...';



        fetch("{{ route('rapports-mensuels.preview') }}", {

            method: "POST",

            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },

            body: JSON.stringify({

                contrat_cours_id: "{{ $contrat->id }}",

                periode_id: periodeId

            })

        })


        .then(response => {

            if (!response.ok) {

                throw new Error(
                    "Erreur lors du calcul du rapport."
                );

            }

            return response.json();

        })


        .then(data => {


            volumeHoraire.textContent =
                Number(data.volume_horaire)
                    .toFixed(2);


            nombreSeances.textContent =
                data.nombre_seances;


            bilanActivites.value =
                data.bilan ?? "Aucune séance trouvée.";


        })


        .catch(error => {

            console.error(error);


            volumeHoraire.textContent = '0';

            nombreSeances.textContent = '0';

            bilanActivites.value =
                "Impossible de récupérer les séances pour cette période.";

        });


    });

});
</script>

@endpush