@extends('panel.layouts.app')

@section('title', 'Types d\'ajustements')

@section('content')
<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon"><i class="bi bi-tags"></i></div>
            <div>
                <h1 class="mb-0">Types d'ajustements</h1>
                <p class="text-muted mb-0">Gestion des types de primes et retenues</p>
            </div>
        </div>

        <div class="heading-actions">
            <a href="{{ route('finance.bulletins-paie.index') }}" class="btn btn-light">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
            <a href="{{ route('finance.type-ajustements.create') }}" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Ajouter
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-3 mb-4">
        @php
            $totalCredits = $types->where('direction', 'credit')->count();
            $totalDebits = $types->where('direction', 'debit')->count();
        @endphp

        <div class="col-lg-3 col-md-6">
            <div class="metric-card metric-primary">
                <div class="metric-label">Total types</div>
                <div class="metric-value">{{ $types->count() }}</div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="metric-card metric-success">
                <div class="metric-label">Types crédit (ajout)</div>
                <div class="metric-value">{{ $totalCredits }}</div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="metric-card metric-warning">
                <div class="metric-label">Type débit (retrait)</div>
                <div class="metric-value">{{ $totalDebits }}</div>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header">
            <div>
                <h5 class="mb-0">Liste des types</h5>
                <p class="text-muted mb-0">Les types inactifs n'apparaissent plus dans le formulaire d'ajustement</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Libellé</th>
                        <th>Sens</th>
                        <th>Statut</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($types as $type)
                    <tr>
                        <td><strong>{{ $type->libelle }}</strong></td>
                        <td>
                            @if($type->direction === 'credit')
                                <span class="badge text-bg-success">Crédit (+)</span>
                            @else
                                <span class="badge text-bg-danger">Débit (-)</span>
                            @endif
                        </td>
                        <td>
                            @if($type->is_active)
                                <span class="badge text-bg-success">Actif</span>
                            @else
                                <span class="badge text-bg-secondary">Inactif</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="btn-group">
                                <a href="{{ route('finance.type-ajustements.edit', $type) }}"
                                   class="btn btn-sm btn-light" title="Modifier">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST"
                                      action="{{ route('finance.type-ajustements.destroy', $type) }}"
                                      class="d-inline"
                                      onsubmit="return confirm('Supprimer ce type ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="Supprimer">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-muted text-center">
                            Aucun type d'ajustement configuré.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
