@extends('panel.layouts.app')

@section('title', 'Gestion des classes')

@section('content')

<div class="container-fluid">

    <!-- HEADER -->
    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon">
                <i class="bi bi-mortarboard"></i>
            </div>
            <div>
                <h1 class="mb-0">Classes</h1>
                <p class="text-muted mb-0">Gestion des classes et effectifs des élèves</p>
            </div>
        </div>

        <div class="heading-actions">
            <a href="{{ route('classes.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i>
                Nouvelle classe
            </a>
        </div>
    </div>

    <!-- STATS -->
    <div class="row g-3 mb-4">

        <div class="col-md-4">
            <div class="metric-card metric-primary">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="metric-label">Total classes</div>
                        <div class="metric-value">{{ $classes->total() }}</div>
                    </div>
                    <div class="metric-icon">
                        <i class="bi bi-grid-1x2"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="metric-card metric-success">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="metric-label">Élèves total</div>
                        <div class="metric-value">
                            {{ $classes->sum('eleves_count') }}
                        </div>
                    </div>
                    <div class="metric-icon">
                        <i class="bi bi-people"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="metric-card metric-warning">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="metric-label">Moyenne / classe</div>
                        <div class="metric-value">
                            {{ $classes->count() ? round($classes->sum('eleves_count') / $classes->count()) : 0 }}
                        </div>
                    </div>
                    <div class="metric-icon">
                        <i class="bi bi-bar-chart"></i>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- TABLE -->
    <div class="panel">

        <div class="panel-header">
            <div>
                <h5 class="mb-0">Liste des classes</h5>
                <p class="text-muted mb-0">Toutes les classes enregistrées</p>
            </div>
        </div>

        <div class="table-responsive">

            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Classe</th>
                        <th>Sigle</th>
                        <th>Élèves</th>
                        <th>Créée</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($classes as $classe)
                        <tr>
                            <td>
                                <strong>{{ $classe->nom }}</strong>
                            </td>

                            <td>
                                <span class="badge text-bg-secondary">
                                    {{ $classe->sigle }}
                                </span>
                            </td>

                            <td>
                                <span class="badge text-bg-primary">
                                    {{ $classe->eleves_count }}
                                </span>
                            </td>

                            <td class="text-muted">
                                {{ $classe->created_at?->format('d/m/Y') }}
                            </td>

                            <td class="text-end">

                                <a href="{{ route('classes.edit', $classe) }}"
                                   class="btn btn-sm btn-light">
                                    <i class="bi bi-pencil-square"></i>
                                </a>

                                <form action="{{ route('classes.destroy', $classe) }}"
                                      method="POST"
                                      class="d-inline"
                                      onsubmit="return confirm('Supprimer cette classe ?')">

                                    @csrf
                                    @method('DELETE')

                                    <button class="btn btn-sm btn-light text-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>

                                </form>

                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="blank-panel">
                                    <div class="blank-state">
                                        <i class="bi bi-inbox display-5 text-muted"></i>
                                        <p class="mt-2 text-muted">
                                            Aucune classe enregistrée
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
            {{ $classes->links() }}
        </div>

    </div>

</div>

@endsection