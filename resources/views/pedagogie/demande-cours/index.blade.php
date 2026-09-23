@extends('panel.layouts.app')

@section('title', 'Demandes de cours')

@section('content')

<div class="container-fluid">

    <div class="page-heading">

        <div class="page-heading-copy">

            <div class="page-icon">
                <i class="bi bi-journal-text"></i>
            </div>

            <div>
                <h1 class="mb-0">
                    Demandes de cours
                </h1>

                <p class="text-muted mb-0">
                    Gestion des demandes reçues depuis le site
                </p>
            </div>

        </div>

    </div>

    <div class="row g-3 mb-4">

        <div class="col-md-4">

            <div class="metric-card metric-primary">

                <div class="d-flex justify-content-between">

                    <div>
                        <div class="metric-label">
                            Total demandes
                        </div>

                        <div class="metric-value">
                            {{ $stats['total'] }}
                        </div>
                    </div>

                    <div class="metric-icon">
                        <i class="bi bi-envelope"></i>
                    </div>

                </div>

            </div>

        </div>

        <div class="col-md-4">

            <div class="metric-card metric-warning">

                <div class="d-flex justify-content-between">

                    <div>
                        <div class="metric-label">
                            En attente
                        </div>

                        <div class="metric-value">
                            {{ $stats['en_attente'] }}
                        </div>
                    </div>

                    <div class="metric-icon">
                        <i class="bi bi-clock-history"></i>
                    </div>

                </div>

            </div>

        </div>

        <div class="col-md-4">

            <div class="metric-card metric-success">

                <div class="d-flex justify-content-between">

                    <div>
                        <div class="metric-label">
                            Traitées
                        </div>

                        <div class="metric-value">
                            {{ $stats['traitees'] }}
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
                    Liste des demandes
                </h5>

                <p class="text-muted mb-0">
                    Toutes les demandes reçues
                </p>
            </div>

        </div>

        <div class="table-responsive">

            <table class="table align-middle">

                <thead>
                    <tr>
                        <th>Parent</th>
                        <th>Téléphone</th>
                        <th>Classe</th>
                        <th>Type</th>
                        <th>Statut</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>

                <tbody>

                @forelse($demandes as $demande)

                    <tr>

                        <td>
                            <strong>
                                {{ $demande->nom_parent }}
                                {{ $demande->prenom_parent }}
                            </strong>
                        </td>

                        <td>
                            {{ $demande->telephone }}
                        </td>

                        <td>
                            {{ $demande->classe?->nom }}
                        </td>

                        <td>
                            {{ $demande->typeCours?->libelle }}
                        </td>

                        <td>

                            @if($demande->statut === 'en_attente')

                                <span class="badge text-bg-warning">
                                    En attente
                                </span>

                            @else

                                <span class="badge text-bg-success">
                                    Traitée
                                </span>

                            @endif

                        </td>

                        <td class="text-end">

                            <a href="{{ route('demande-cours.show', $demande) }}"
                               class="btn btn-sm btn-light">

                                <i class="bi bi-eye"></i>

                            </a>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="6">

                            <div class="blank-panel">

                                <div class="blank-state">

                                    <i class="bi bi-inbox display-5 text-muted"></i>

                                    <p class="mt-2 text-muted">
                                        Aucune demande enregistrée
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
            {{ $demandes->links() }}
        </div>

    </div>

</div>

@endsection