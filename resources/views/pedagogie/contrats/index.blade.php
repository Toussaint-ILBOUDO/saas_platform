@extends('panel.layouts.app')

@section('title', 'Contrats')

@section('content')

<div class="container-fluid">

<div class="page-heading">

    <div class="page-heading-copy">

        <div class="page-icon">
            <i class="bi bi-file-earmark-text"></i>
        </div>

        <div>

            <h1 class="mb-0">
                Contrats
            </h1>

            <p class="text-muted mb-0">
                Gestion des contrats de cours
            </p>

        </div>

    </div>

    <div class="heading-actions">

        <a href="{{ route('contrats.create') }}"
           class="btn btn-primary">

            <i class="bi bi-plus-circle"></i>
            Nouveau contrat

        </a>

    </div>

</div>

@php
    $total = $contrats->total();

    $actifs = $contrats->getCollection()
        ->where('statut', 'actif')
        ->count();

    $suspendus = $contrats->getCollection()
        ->where('statut', 'suspendu')
        ->count();

    $termines = $contrats->getCollection()
        ->where('statut', 'termine')
        ->count();
@endphp

<div class="row g-3 mb-4">

    <div class="col-md-3">

        <div class="metric-card metric-primary">

            <div class="d-flex justify-content-between">

                <div>

                    <div class="metric-label">
                        Total
                    </div>

                    <div class="metric-value">
                        {{ $total }}
                    </div>

                </div>

                <div class="metric-icon">
                    <i class="bi bi-file-earmark-text"></i>
                </div>

            </div>

        </div>

    </div>

    <div class="col-md-3">

        <div class="metric-card metric-success">

            <div class="d-flex justify-content-between">

                <div>

                    <div class="metric-label">
                        Actifs
                    </div>

                    <div class="metric-value">
                        {{ $actifs }}
                    </div>

                </div>

                <div class="metric-icon">
                    <i class="bi bi-check-circle"></i>
                </div>

            </div>

        </div>

    </div>

    <div class="col-md-3">

        <div class="metric-card metric-warning">

            <div class="d-flex justify-content-between">

                <div>

                    <div class="metric-label">
                        Suspendus
                    </div>

                    <div class="metric-value">
                        {{ $suspendus }}
                    </div>

                </div>

                <div class="metric-icon">
                    <i class="bi bi-pause-circle"></i>
                </div>

            </div>

        </div>

    </div>

    <div class="col-md-3">

        <div class="metric-card metric-danger">

            <div class="d-flex justify-content-between">

                <div>

                    <div class="metric-label">
                        Terminés
                    </div>

                    <div class="metric-value">
                        {{ $termines }}
                    </div>

                </div>

                <div class="metric-icon">
                    <i class="bi bi-x-circle"></i>
                </div>

            </div>

        </div>

    </div>

</div>

<div class="panel">

    <div class="panel-header">

        <div>

            <h5 class="mb-0">
                Liste des contrats
            </h5>

            <p class="text-muted mb-0">
                Tous les contrats enregistrés
            </p>

        </div>

    </div>

    <div class="table-responsive">

        <table class="table align-middle">

            <thead>

                <tr>

                    <th>#</th>
                    <th>Élève</th>
                    <th>Type cours</th>
                    <th>Date début</th>
                    <th>Date fin</th>
                    <th>Affectations</th>
                    <th>Statut</th>
                    <th class="text-end">Actions</th>

                </tr>

            </thead>

            <tbody>

            @forelse($contrats as $contrat)

                <tr>

                    <td>
                        <strong>#{{ $contrat->id }}</strong>
                    </td>

                    <td>

                        {{ $contrat->eleve?->user?->nom }}
                        {{ $contrat->eleve?->user?->prenom }}

                    </td>

                    <td>
                        {{ $contrat->typeCours?->libelle }}
                    </td>

                    <td>
                        {{ \Carbon\Carbon::parse($contrat->date_debut)->format('d/m/Y') }}
                    </td>

                    <td>
                        {{ $contrat->date_fin ? \Carbon\Carbon::parse($contrat->date_fin)->format('d/m/Y') : '-' }}
                    </td>

                    <td>

                        <span class="badge text-bg-info">
                            {{ $contrat->affectations->count() }}
                        </span>

                    </td>

                    <td>

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

                    </td>

                    <td class="text-end">

                        <a href="{{ route('contrats.show', $contrat) }}"
                           class="btn btn-sm btn-light">

                            <i class="bi bi-eye"></i>

                        </a>

                    </td>

                </tr>

            @empty

                <tr>

                    <td colspan="8">

                        <div class="blank-panel">

                            <div class="blank-state">

                                <i class="bi bi-file-earmark-x display-4 text-muted"></i>

                                <p class="mt-3 text-muted">
                                    Aucun contrat enregistré
                                </p>

                            </div>

                        </div>

                    </td>

                </tr>

            @endforelse

            </tbody>

        </table>

    </div>

    <div class="mt-3">
        {{ $contrats->links() }}
    </div>

</div>

</div>

@endsection
