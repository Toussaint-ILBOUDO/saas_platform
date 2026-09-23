@extends('panel.layouts.app')

@section('title', 'Modifier la séance')

@section('content')

<div class="container-fluid">

    <div class="page-heading">

        <div class="page-heading-copy">

            <div class="page-icon">
                <i class="bi bi-journal-richtext"></i>
            </div>

            <div>
                <h1 class="mb-0">Modifier la séance</h1>

                <p class="text-muted mb-0">
                    Modifiable uniquement le jour de la séance
                </p>
            </div>

        </div>

        <div class="heading-actions">

            <a href="{{ route('cahiers-textes.show', $cahier) }}"
               class="btn btn-light">

                <i class="bi bi-arrow-left"></i>
                Retour

            </a>

        </div>

    </div>

    <form action="{{ route('cahiers-textes.update', $cahier) }}"
          method="POST">

        @csrf
        @method('PUT')

        <div class="row g-4">

            {{-- FORMULAIRE --}}

            <div class="col-lg-8">

                <div class="panel">

                    <div class="panel-header">

                        <div>
                            <h5 class="mb-0">
                                Informations de la séance
                            </h5>

                            <p class="text-muted mb-0">
                                Décrivez précisément le travail réalisé.
                            </p>
                        </div>

                    </div>

                    <div class="panel-body">

                        <div class="row">

                            <div class="col-md-4">

                                <div class="mb-3">

                                    <label class="form-label">
                                        Heure début
                                    </label>

                                    <input
                                        type="time"
                                        id="heure_debut"
                                        name="heure_debut"
                                        value="{{ old('heure_debut', $cahier->heure_debut) }}"
                                        class="form-control @error('heure_debut') is-invalid @enderror">

                                    @error('heure_debut')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                    @enderror

                                </div>

                            </div>

                            <div class="col-md-4">

                                <div class="mb-3">

                                    <label class="form-label">
                                        Heure fin
                                    </label>

                                    <input
                                        type="time"
                                        id="heure_fin"
                                        name="heure_fin"
                                        value="{{ old('heure_fin', $cahier->heure_fin) }}"
                                        class="form-control @error('heure_fin') is-invalid @enderror">

                                    @error('heure_fin')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                    @enderror

                                </div>

                            </div>

                        </div>

                        <div class="mb-4">

                            <div class="mini-card">

                                <div class="metric-label">
                                    Durée estimée
                                </div>

                                <div class="metric-value"
                                     id="dureePreview">

                                    --
                                </div>

                            </div>

                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                Contenu du cours
                            </label>

                            <textarea
                                name="contenu_cours"
                                rows="8"
                                class="form-control @error('contenu_cours') is-invalid @enderror"
                                placeholder="Décrivez le contenu réellement traité durant la séance...">{{ old('contenu_cours', $cahier->contenu_cours) }}</textarea>

                            @error('contenu_cours')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror

                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                Objectifs atteints
                            </label>

                            <textarea
                                name="objectifs_atteints"
                                rows="4"
                                class="form-control">{{ old('objectifs_atteints', $cahier->objectifs_atteints) }}</textarea>

                        </div>

                        <div class="mb-4">

                            <label class="form-label">
                                Observations
                            </label>

                            <textarea
                                name="observations"
                                rows="4"
                                class="form-control">{{ old('observations', $cahier->observations) }}</textarea>

                        </div>

                        <div class="d-flex justify-content-end gap-2">

                            <a href="{{ route('cahiers-textes.show', $cahier) }}"
                               class="btn btn-light">

                                Annuler

                            </a>

                            <button
                                type="submit"
                                class="btn btn-primary">

                                <i class="bi bi-check-circle"></i>

                                Enregistrer les modifications

                            </button>

                        </div>

                    </div>

                </div>

            </div>

            {{-- SIDEBAR INFO --}}

            <div class="col-lg-4">

                <div class="panel">

                    <div class="panel-header">

                        <h5 class="mb-0">
                            Aperçu
                        </h5>

                    </div>

                    <div class="panel-body">

                        <ul class="info-list">

                            <li>
                                <span>Matière</span>
                                <strong>{{ $cahier->affectation->matiere->nom }}</strong>
                            </li>

                            <li>
                                <span>Élève</span>
                                <strong>
                                    {{ $cahier->affectation->contrat->eleve->user->prenom }}
                                    {{ $cahier->affectation->contrat->eleve->user->nom }}
                                </strong>
                            </li>

                            <li>
                                <span>Date</span>
                                <strong>{{ $cahier->date_seance->format('d/m/Y') }}</strong>
                            </li>

                            <li>
                                <span>Type</span>
                                <strong>Séance pédagogique</strong>
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

    function calculerDuree() {

        const debut =
            document.getElementById('heure_debut').value;

        const fin =
            document.getElementById('heure_fin').value;

        if (!debut || !fin) return;

        const d1 = new Date('2000-01-01 ' + debut);
        const d2 = new Date('2000-01-01 ' + fin);

        const diff =
            (d2 - d1) / 1000 / 60;

        if (diff <= 0) return;

        const heures =
            Math.floor(diff / 60);

        const minutes =
            diff % 60;

        document.getElementById('dureePreview')
            .innerText =
            heures + 'h ' + minutes + 'min';
    }

    calculerDuree();

    document.getElementById('heure_debut')
        .addEventListener('change', calculerDuree);

    document.getElementById('heure_fin')
        .addEventListener('change', calculerDuree);

</script>

@endpush
