@extends('panel.layouts.app')

@section('title', 'Mes cours')

@section('content')

<div class="container-fluid">

<div class="page-heading">

    <div class="page-heading-copy">

        <div class="page-icon">
            <i class="bi bi-easel"></i>
        </div>

        <div>

            <h1 class="mb-0">
                Mes cours
            </h1>

            <p class="text-muted mb-0">
                Les contrats de cours auxquels vous êtes affecté
            </p>

        </div>

    </div>

</div>

@php
    $total = $contrats->total();

    $actifs = $contrats->getCollection()
        ->where('statut', 'actif')
        ->count();

    $termines = $contrats->getCollection()
        ->where('statut', 'termine')
        ->count();
@endphp

<div class="row g-3 mb-4">

    <div class="col-md-6">

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
                    <i class="bi bi-easel"></i>
                </div>

            </div>

        </div>

    </div>

    <div class="col-md-6">

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

</div>

<div class="panel">

    <div class="panel-header">

        <div>

            <h5 class="mb-0">
                Mes contrats de cours
            </h5>

            <p class="text-muted mb-0">
                Affectations dont vous êtes responsable
            </p>

        </div>

    </div>

    <div class="table-responsive">

        <table class="table align-middle">

            <thead>

                <tr>

                    <th>#</th>
                    <th>Élève</th>
                    <th>Matière</th>
                    <th>Type cours</th>
                    <th>Date début</th>
                    <th>Date fin</th>
                    <th>Statut</th>
                    <th class="text-end">Actions</th>

                </tr>

            </thead>

            <tbody>

            @forelse($contrats as $contrat)

                @php
                    $affectations = $contrat->affectations ?? collect();
                @endphp

                <tr>

                    <td>
                        <strong>#{{ $contrat->id }}</strong>
                    </td>

                    <td>

                        {{ $contrat->eleve?->user?->nom }}
                        {{ $contrat->eleve?->user?->prenom }}

                    </td>

                    <td>

                        @forelse($affectations as $affectation)

                            <span class="badge text-bg-light border me-1">
                                {{ $affectation->matiere?->nom }}
                            </span>

                        @empty

                            <span class="text-muted">-</span>

                        @endforelse

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

                                <i class="bi bi-easel display-4 text-muted"></i>

                                <p class="mt-3 text-muted">
                                    Aucun cours ne vous est actuellement affecté.
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