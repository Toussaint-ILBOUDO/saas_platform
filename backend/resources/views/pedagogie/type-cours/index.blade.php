@extends('panel.layouts.app')

@section('title', 'Types de cours')

@section('content')

<div class="container-fluid">

    <!-- HEADER -->
    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon">
                <i class="bi bi-journal-bookmark"></i>
            </div>
            <div>
                <h1 class="mb-0">Types de cours</h1>
                <p class="text-muted mb-0">Gestion des types de cours disponibles</p>
            </div>
        </div>

        <div class="heading-actions">
            <a href="{{ route('type-cours.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i>
                Nouveau type
            </a>
        </div>
    </div>

    <!-- STATS -->
    <div class="row g-3 mb-4">

        <div class="col-md-4">
            <div class="metric-card metric-primary">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="metric-label">Total</div>
                        <div class="metric-value">{{ $typeCours->total() }}</div>
                    </div>
                    <div class="metric-icon">
                        <i class="bi bi-list"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="metric-card metric-success">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="metric-label">Actifs</div>
                        <div class="metric-value">
                            {{ $typeCours->where('actif', true)->count() }}
                        </div>
                    </div>
                    <div class="metric-icon">
                        <i class="bi bi-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="metric-card metric-warning">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="metric-label">Inactifs</div>
                        <div class="metric-value">
                            {{ $typeCours->where('actif', false)->count() }}
                        </div>
                    </div>
                    <div class="metric-icon">
                        <i class="bi bi-pause-circle"></i>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- TABLE -->
    <div class="panel">

        <div class="panel-header">
            <div>
                <h5 class="mb-0">Liste des types de cours</h5>
                <p class="text-muted mb-0">Tous les types configurés</p>
            </div>
        </div>

        <div class="table-responsive">

            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Libellé</th>
                        <th>Code</th>
                        <th>Statut</th>
                        <th>Créé</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>

                <tbody>
                @forelse($typeCours as $typeCour)
                    <tr>
                        <td>
                            <strong>{{ $typeCour->libelle }}</strong>
                        </td>

                        <td>
                            <span class="badge text-bg-secondary">
                                {{ $typeCour->code ?? '-' }}
                            </span>
                        </td>

                        <td>
                            @if($typeCour->actif)
                                <span class="badge text-bg-success">Actif</span>
                            @else
                                <span class="badge text-bg-danger">Inactif</span>
                            @endif
                        </td>

                        <td class="text-muted">
                            {{ $typeCour->created_at?->format('d/m/Y') }}
                        </td>

                        <td class="text-end">

                            @if($typeCour->actif)
                                <form action="{{ route('type-cours.deactivate', $typeCour) }}"
                                      method="POST"
                                      class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button class="btn btn-sm btn-light">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                </form>
                            @else
                                <form action="{{ route('type-cours.activate', $typeCour) }}"
                                      method="POST"
                                      class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button class="btn btn-sm btn-light text-success">
                                        <i class="bi bi-check-circle"></i>
                                    </button>
                                </form>
                            @endif

                            <a href="{{ route('type-cours.edit', $typeCour) }}"
                               class="btn btn-sm btn-light">
                                <i class="bi bi-pencil-square"></i>
                            </a>

                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <div class="blank-panel">
                                <div class="blank-state">
                                    <i class="bi bi-inbox display-5 text-muted"></i>
                                    <p class="mt-2 text-muted">Aucun type de cours</p>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>

            </table>

        </div>

        <div class="mt-3">
            {{ $typeCours->links() }}
        </div>

    </div>

</div>

@endsection