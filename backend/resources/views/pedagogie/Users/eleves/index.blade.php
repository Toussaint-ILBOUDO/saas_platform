@extends('panel.layouts.app')

@section('title', 'Élèves')

@section('content')

<div class="container-fluid">

    <div class="page-heading">

        <div class="page-heading-copy">

            <div class="page-icon">
                <i class="bi bi-mortarboard-fill"></i>
            </div>

            <div>
                <h1 class="mb-0">Élèves</h1>
                <p class="text-muted mb-0">
                    Gestion des élèves
                </p>
            </div>

        </div>

        <div class="heading-actions">

            <a href="{{ route('eleves.create') }}"
               class="btn btn-primary">

                <i class="bi bi-plus-lg"></i>
                Nouvel élève

            </a>

        </div>

    </div>

    <div class="row g-3 mb-4">

        <div class="col-md-4">

            <div class="metric-card metric-primary">

                <div class="d-flex justify-content-between">

                    <div>
                        <div class="metric-label">
                            Total élèves
                        </div>

                        <div class="metric-value">
                            {{ $eleves->total() }}
                        </div>
                    </div>

                    <div class="metric-icon">
                        <i class="bi bi-mortarboard-fill"></i>
                    </div>

                </div>

            </div>

        </div>

        <div class="col-md-4">

            <div class="metric-card metric-success">

                <div class="d-flex justify-content-between">

                    <div>
                        <div class="metric-label">
                            Classes concernées
                        </div>

                        <div class="metric-value">
                            {{ $eleves->pluck('classe_id')->unique()->count() }}
                        </div>
                    </div>

                    <div class="metric-icon">
                        <i class="bi bi-building"></i>
                    </div>

                </div>

            </div>

        </div>

        <div class="col-md-4">

            <div class="metric-card metric-warning">

                <div class="d-flex justify-content-between">

                    <div>
                        <div class="metric-label">
                            Élèves enregistrés
                        </div>

                        <div class="metric-value">
                            {{ $eleves->count() }}
                        </div>
                    </div>

                    <div class="metric-icon">
                        <i class="bi bi-graph-up"></i>
                    </div>

                </div>

            </div>

        </div>

    </div>

    <div class="panel">

        <div class="panel-header">

            <div>
                <h5 class="mb-0">Liste des élèves</h5>
                <p class="text-muted mb-0">
                    Tous les élèves enregistrés
                </p>
            </div>

        </div>

        <div class="table-responsive">

            <table class="table align-middle">

                <thead>
                <tr>
                    <th>Élève</th>
                    <th>Parent</th>
                    <th>Classe</th>
                    <th>École</th>
                    <th>Créé le</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>

                <tbody>

                @forelse($eleves as $eleve)

                    <tr>

                        <td>

                            <strong>
                                {{ $eleve->user->nom }}
                                {{ $eleve->user->prenom }}
                            </strong>

                        </td>

                        <td>

                            {{ $eleve->parent?->nom }}
                            {{ $eleve->parent?->prenom }}

                        </td>

                        <td>

                            {{ $eleve->classe?->nom }}

                        </td>

                        <td>

                            {{ $eleve->ecole ?: '-' }}

                        </td>

                        <td class="text-muted">
                            {{ $eleve->created_at?->format('d/m/Y') }}
                        </td>

                        <td class="text-end">

                            <a href="{{ route('eleves.show', $eleve->id) }}"
                               class="btn btn-sm btn-light">

                                <i class="bi bi-eye"></i>

                            </a>

                            <a href="{{ route('eleves.edit', $eleve->id) }}"
                               class="btn btn-sm btn-light">

                                <i class="bi bi-pencil-square"></i>

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
                                        Aucun élève enregistré
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
            {{ $eleves->links() }}
        </div>

    </div>

</div>

@endsection