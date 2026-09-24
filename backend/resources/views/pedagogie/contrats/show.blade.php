@extends('panel.layouts.app')

@section('title', 'Contrat #' . $contrat->id)

@section('content')

<div class="container-fluid">

<div class="page-heading">

    <div class="page-heading-copy">

        <div class="page-icon">
            <i class="bi bi-file-earmark-text"></i>
        </div>

        <div>

            <h1 class="mb-0">
                Contrat #{{ $contrat->id }}
            </h1>

            <p class="text-muted mb-0">
                Détails du contrat
            </p>

        </div>

    </div>

    <div class="heading-actions">

        @php
            $user = auth()->user();
            $isPersonnelCours = $user->hasRole('enseignant') || $user->hasRole('eleve');
            $retourRoute = $isPersonnelCours ? route('mes-cours.index') : route('contrats.index');
        @endphp

        <a href="{{ $retourRoute }}"
           class="btn btn-light">

            <i class="bi bi-arrow-left"></i>
            Retour

        </a>

    </div>

</div>

<div class="row g-4">

    <div class="col-lg-4">

        <div class="panel">

            <div class="panel-header">

                <h5 class="mb-0">
                    Informations générales
                </h5>

            </div>

            <div class="info-list">

                <div>
                    <span>Élève</span>

                    <strong>
                        {{ $contrat->eleve?->user?->nom }}
                        {{ $contrat->eleve?->user?->prenom }}
                    </strong>
                </div>

                <div>
                    <span>Type de cours</span>

                    <strong>
                        {{ $contrat->typeCours?->libelle }}
                    </strong>
                </div>

                <div>
                    <span>Date début</span>

                    <strong>
                        {{ \Carbon\Carbon::parse($contrat->date_debut)->format('d/m/Y') }}
                    </strong>
                </div>

                <div>
                    <span>Date fin</span>

                    <strong>
                        {{ $contrat->date_fin ? \Carbon\Carbon::parse($contrat->date_fin)->format('d/m/Y') : '-' }}
                    </strong>
                </div>

                <div>
                    <span>Statut</span>

                    <strong>

                        @if($contrat->statut === 'actif')

                            <span class="badge text-bg-success">
                                Actif
                            </span>

                        @elseif($contrat->statut === 'suspendu')

                            <span class="badge text-bg-warning">
                                Suspendu
                            </span>

                        @else

                            <span class="badge text-bg-danger">
                                Terminé
                            </span>

                        @endif

                    </strong>

                </div>

            </div>

        </div>

    </div>

    <div class="col-lg-8">

        <div class="panel mb-4">

            <div class="panel-header">

                <h5 class="mb-0">
                    Notes administratives
                </h5>

            </div>

            <p class="mb-0">

                {{ $contrat->notes_admin ?: 'Aucune note.' }}

            </p>

        </div>

        <div class="panel">

            <div class="panel-header">

                <div>

                    <h5 class="mb-0">
                        Affectations enseignants
                    </h5>

                    <p class="text-muted mb-0">
                        Matières attribuées dans ce contrat
                    </p>

                </div>

            </div>

            <div class="table-responsive">

                <table class="table align-middle">

                    <thead>

                        <tr>

                            <th>Matière</th>
                            <th>Enseignant</th>
                            <th>Heures</th>
                            <th>Taux horaire</th>
                            <th>Statut</th>

                        </tr>

                    </thead>

                    <tbody>

                    @forelse($contrat->affectations as $affectation)

                        <tr>

                            <td>
                                {{ $affectation->matiere?->nom }}
                            </td>

                            <td>

                                {{ $affectation->enseignant?->user?->nom }}
                                {{ $affectation->enseignant?->user?->prenom }}

                            </td>

                            <td>
                                {{ $affectation->nombre_heures_prevues }} h
                            </td>

                            <td>
                                {{ number_format($affectation->taux_horaire_enseignant, 0, ',', ' ') }} FCFA
                            </td>

                            <td>

                                @if($affectation->statut === 'actif')

                                    <span class="badge text-bg-success">
                                        Actif
                                    </span>

                                @elseif($affectation->statut === 'suspendu')

                                    <span class="badge text-bg-warning">
                                        Suspendu
                                    </span>

                                @else

                                    <span class="badge text-bg-danger">
                                        Terminé
                                    </span>

                                @endif

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="5" class="text-center text-muted">

                                Aucune affectation enregistrée

                            </td>

                        </tr>

                    @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

</div>

@endsection
