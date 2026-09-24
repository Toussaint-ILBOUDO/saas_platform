@extends('panel.layouts.app')

@section('title', 'Modération — Bibliothèque')

@section('content')

<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon"><i class="bi bi-shield-check"></i></div>
            <div>
                <h1 class="mb-0">Modération Bibliothèque</h1>
                <p class="text-muted mb-0">Validation et modération des documents</p>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="metric-card metric-primary">
                <div class="metric-icon"><i class="bi bi-file-earmark-text"></i></div>
                <div class="metric-label">Total</div>
                <div class="metric-value">{{ $stats['total'] }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="metric-card metric-warning">
                <div class="metric-icon"><i class="bi bi-clock"></i></div>
                <div class="metric-label">En attente</div>
                <div class="metric-value">{{ $stats['en_attente'] }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="metric-card metric-success">
                <div class="metric-icon"><i class="bi bi-check-circle"></i></div>
                <div class="metric-label">Publiés</div>
                <div class="metric-value">{{ $stats['publies'] }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="metric-card metric-danger">
                <div class="metric-icon"><i class="bi bi-flag"></i></div>
                <div class="metric-label">Signalements</div>
                <div class="metric-value">{{ $stats['signalements_en_attente'] }}</div>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header">
            <form action="{{ route('admin.bibliotheque.index') }}" method="GET" class="d-flex gap-2 flex-wrap">
                <input type="text" name="search" class="form-control table-search"
                       placeholder="Rechercher..." value="{{ request('search') }}">
                <select name="statut" class="form-select" style="max-width: 180px;" onchange="this.form.submit()">
                    <option value="">Tous les statuts</option>
                    <option value="en_attente" {{ request('statut') === 'en_attente' ? 'selected' : '' }}>En attente</option>
                    <option value="publie" {{ request('statut') === 'publie' ? 'selected' : '' }}>Publié</option>
                    <option value="refuse" {{ request('statut') === 'refuse' ? 'selected' : '' }}>Refusé</option>
                    <option value="brouillon" {{ request('statut') === 'brouillon' ? 'selected' : '' }}>Brouillon</option>
                    <option value="archive" {{ request('statut') === 'archive' ? 'selected' : '' }}>Archivé</option>
                </select>
                <button type="submit" class="btn btn-sm btn-light">
                    <i class="bi bi-search"></i>
                </button>
            </form>
            <a href="{{ route('admin.bibliotheque.signalements') }}" class="btn btn-outline-danger btn-sm">
                <i class="bi bi-flag me-1"></i>Signalements
            </a>
        </div>
        <div class="p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="min-width: 250px;">Document</th>
                            <th>Auteur</th>
                            <th>Type</th>
                            <th>Statut</th>
                            <th class="text-center">Vues</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($documents as $doc)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.bibliotheque.show', $doc->id) }}"
                                       class="fw-semibold text-decoration-none" style="color: var(--heading-color);">
                                        {{ Str::limit($doc->titre, 50) }}
                                    </a>
                                    <div class="text-muted" style="font-size: 0.8rem;">
                                        {{ $doc->created_at->format('d/m/Y') }}
                                        @if($doc->is_public)
                                            <span class="badge bg-success-subtle text-success ms-1">Public</span>
                                        @endif
                                    </div>
                                </td>
                                <td>{{ $doc->auteur?->prenom }} {{ $doc->auteur?->nom }}</td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary">
                                        {{ $doc->typeDocument?->sigle ?? '-' }}
                                    </span>
                                </td>
                                <td>
                                    @switch($doc->statut)
                                        @case('brouillon')
                                            <span class="badge bg-secondary">Brouillon</span>
                                            @break
                                        @case('en_attente')
                                            <span class="badge bg-warning">En attente</span>
                                            @break
                                        @case('publie')
                                            <span class="badge bg-success">Publié</span>
                                            @break
                                        @case('refuse')
                                            <span class="badge bg-danger">Refusé</span>
                                            @break
                                        @case('archive')
                                            <span class="badge bg-dark">Archivé</span>
                                            @break
                                    @endswitch
                                </td>
                                <td class="text-center">{{ number_format($doc->nb_vues) }}</td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <a href="{{ route('admin.bibliotheque.show', $doc->id) }}"
                                           class="btn btn-sm btn-light" title="Voir">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @if($doc->statut === 'en_attente')
                                            <form action="{{ route('admin.bibliotheque.valider', $doc->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-success" title="Valider">
                                                    <i class="bi bi-check-lg"></i>
                                                </button>
                                            </form>
                                            <form action="{{ route('admin.bibliotheque.refuser', $doc->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-danger" title="Refuser">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="blank-state">
                                        <i class="bi bi-inbox display-4 text-muted"></i>
                                        <p class="text-muted mt-2">Aucun document.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($documents->hasPages())
            <div class="panel-footer">
                {{ $documents->links() }}
            </div>
        @endif
    </div>

</div>

@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assetBiblio/css/bibliotheque.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('assetBiblio/js/bibliotheque.js') }}"></script>
@endpush
