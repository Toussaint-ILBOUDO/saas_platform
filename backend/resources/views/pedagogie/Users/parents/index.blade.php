@extends('panel.layouts.app')

@section('title', 'Parents')

@section('content')

<div class="container-fluid">

    <div class="page-heading">

        <div class="page-heading-copy">

            <div class="page-icon">
                <i class="bi bi-people-fill"></i>
            </div>

            <div>
                <h1 class="mb-0">Parents</h1>
                <p class="text-muted mb-0">
                    Gestion des parents d'élèves
                </p>
            </div>

        </div>

        <div class="heading-actions">

            <a href="{{ route('parents.create') }}"
               class="btn btn-primary">

                <i class="bi bi-plus-lg"></i>
                Nouveau parent

            </a>

        </div>

    </div>

    <div class="row g-3 mb-4">

        <div class="col-md-4">

            <div class="metric-card metric-primary">

                <div class="d-flex justify-content-between">

                    <div>
                        <div class="metric-label">
                            Total parents
                        </div>

                        <div class="metric-value">
                            {{ $parents->total() }}
                        </div>
                    </div>

                    <div class="metric-icon">
                        <i class="bi bi-people-fill"></i>
                    </div>

                </div>

            </div>

        </div>

        <div class="col-md-4">

            <div class="metric-card metric-success">

                <div class="d-flex justify-content-between">

                    <div>
                        <div class="metric-label">
                            Enfants rattachés
                        </div>

                        <div class="metric-value">
                            {{ $parents->sum('enfants_count') }}
                        </div>
                    </div>

                    <div class="metric-icon">
                        <i class="bi bi-mortarboard-fill"></i>
                    </div>

                </div>

            </div>

        </div>

        <div class="col-md-4">

            <div class="metric-card metric-warning">

                <div class="d-flex justify-content-between">

                    <div>
                        <div class="metric-label">
                            Moyenne enfants
                        </div>

                        <div class="metric-value">
                            {{ $parents->count() ? round($parents->sum('enfants_count') / $parents->count(), 1) : 0 }}
                        </div>
                    </div>

                    <div class="metric-icon">
                        <i class="bi bi-bar-chart"></i>
                    </div>

                </div>

            </div>

        </div>

    </div>

    <div class="panel">

        <div class="panel-header">

            <div>
                <h5 class="mb-0">Liste des parents</h5>
                <p class="text-muted mb-0">
                    Tous les parents enregistrés
                </p>
            </div>

        </div>

        <div class="table-responsive">

            <table class="table align-middle">

                <thead>
                <tr>
                    <th>Parent</th>
                    <th>Téléphone</th>
                    <th>Email</th>
                    <th>Enfants</th>
                    <th>Créé le</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>

                <tbody>

                @forelse($parents as $parent)

                    <tr>

                        <td>
                            <strong>
                                {{ $parent->nom }}
                                {{ $parent->prenom }}
                            </strong>
                        </td>

                        <td>
                            {{ $parent->telephone_whatsapp }}
                        </td>

                        <td>
                            {{ $parent->email ?: '-' }}
                        </td>

                        <td>

                            <span class="badge text-bg-primary">
                                {{ $parent->enfants_count }}
                            </span>

                        </td>

                        <td class="text-muted">
                            {{ $parent->created_at?->format('d/m/Y') }}
                        </td>

                        <td class="text-end">

                            <a href="{{ route('parents.show', $parent) }}"
                               class="btn btn-sm btn-light">

                                <i class="bi bi-eye"></i>

                            </a>

                            <a href="{{ route('parents.edit', $parent) }}"
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
                                        Aucun parent enregistré
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
            {{ $parents->links() }}
        </div>

    </div>

</div>

@endsection