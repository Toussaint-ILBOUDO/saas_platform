@extends('panel.layouts.app')

@section('title', 'Détail séance')

@section('content')

<div class="container-fluid">

    <div class="page-heading">

        <div class="page-heading-copy">

            <div class="page-icon">
                <i class="bi bi-journal-richtext"></i>
            </div>

            <div>

                <h1 class="mb-0">
                    {{ $cahier->affectation->matiere->nom }}
                </h1>

                <p class="text-muted mb-0">

                    {{ $cahier->date_seance->format('d/m/Y') }}

                    •

                    {{ $cahier->heure_debut }}
                    →
                    {{ $cahier->heure_fin }}

                </p>

            </div>

        </div>

        <div class="heading-actions">

            <a href="{{ route('cahiers-textes.index') }}"
               class="btn btn-light">

                <i class="bi bi-arrow-left"></i>

                Retour

            </a>

            @can('update', $cahier)

                <a href="{{ route('cahiers-textes.edit', $cahier) }}"
                   class="btn btn-primary">

                    <i class="bi bi-pencil-square"></i>

                    Modifier

                </a>

            @endcan

        </div>

    </div>

    <div class="row g-3 mb-4">

        <div class="col-md-3">

            <div class="metric-card metric-primary">

                <div class="metric-label">
                    Élève
                </div>

                <div class="metric-value">

                    {{ $cahier->affectation->contrat->eleve->user->prenom }}
                    {{ $cahier->affectation->contrat->eleve->user->nom }}

                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="metric-card metric-success">

                <div class="metric-label">
                    Matière
                </div>

                <div class="metric-value">

                    {{ $cahier->affectation->matiere->nom }}

                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="metric-card metric-warning">

                <div class="metric-label">
                    Enseignant
                </div>

                <div class="metric-value">

                    {{ $cahier->affectation->enseignant->user->prenom }}
                    {{ $cahier->affectation->enseignant->user->nom }}

                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="metric-card">

                <div class="metric-label">
                    Durée
                </div>

                <div class="metric-value">

                    {{ number_format($cahier->duree_heures, 1) }} h

                </div>

            </div>

        </div>

    </div>

    <div class="panel mb-4">

        <div class="panel-header">

            <h5 class="mb-0">
                Contenu du cours
            </h5>

        </div>

        <div class="panel-body">

            {!! nl2br(e($cahier->contenu_cours)) !!}

        </div>

    </div>

    @if($cahier->objectifs_atteints)

        <div class="panel mb-4">

            <div class="panel-header">

                <h5 class="mb-0">
                    Objectifs atteints
                </h5>

            </div>

            <div class="panel-body">

                {!! nl2br(e($cahier->objectifs_atteints)) !!}

            </div>

        </div>

    @endif

    @if($cahier->observations)

        <div class="panel">

            <div class="panel-header">

                <h5 class="mb-0">
                    Observations
                </h5>

            </div>

            <div class="panel-body">

                {!! nl2br(e($cahier->observations)) !!}

            </div>
    
        </div>

    @endif

    

</div>

@endsection