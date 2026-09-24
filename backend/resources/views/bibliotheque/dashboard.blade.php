@extends('panel.layouts.app')

@section('title', 'Ma bibliothèque — Dashboard')

@section('content')

<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon"><i class="bi bi-speedometer2"></i></div>
            <div>
                <h1 class="mb-0">Ma Bibliothèque</h1>
                <p class="text-muted mb-0">Vue d'ensemble de vos contributions</p>
            </div>
        </div>
        <div class="heading-actions">
            <a href="{{ route('bibliotheque.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Nouveau document
            </a>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="metric-card metric-primary">
                <div class="metric-icon"><i class="bi bi-file-earmark-text"></i></div>
                <div class="metric-label">Total documents</div>
                <div class="metric-value">{{ $stats['total'] }}</div>
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
            <div class="metric-card metric-warning">
                <div class="metric-icon"><i class="bi bi-eye"></i></div>
                <div class="metric-label">Total vues</div>
                <div class="metric-value">{{ number_format($stats['total_vues']) }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="metric-card metric-danger">
                <div class="metric-icon"><i class="bi bi-download"></i></div>
                <div class="metric-label">Téléchargements</div>
                <div class="metric-value">{{ number_format($stats['total_telechargements']) }}</div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-2">
            <div class="panel p-3 text-center">
                <div class="fw-bold fs-4 text-secondary">{{ $stats['brouillons'] }}</div>
                <small class="text-muted">Brouillons</small>
            </div>
        </div>
        <div class="col-sm-6 col-xl-2">
            <div class="panel p-3 text-center">
                <div class="fw-bold fs-4 text-warning">{{ $stats['en_attente'] }}</div>
                <small class="text-muted">En attente</small>
            </div>
        </div>
        <div class="col-sm-6 col-xl-2">
            <div class="panel p-3 text-center">
                <div class="fw-bold fs-4 text-danger">{{ $stats['refuses'] }}</div>
                <small class="text-muted">Refusés</small>
            </div>
        </div>
        <div class="col-sm-6 col-xl-2">
            <div class="panel p-3 text-center">
                <div class="fw-bold fs-4 text-muted">{{ $stats['archives'] }}</div>
                <small class="text-muted">Archivés</small>
            </div>
        </div>
        <div class="col-sm-6 col-xl-2">
            <div class="panel p-3 text-center">
                <div class="fw-bold fs-4 text-danger"><i class="bi bi-heart-fill"></i> {{ number_format($stats['total_favoris']) }}</div>
                <small class="text-muted">Favoris</small>
            </div>
        </div>
        <div class="col-sm-6 col-xl-2">
            <div class="panel p-3 text-center">
                <div class="fw-bold fs-4 text-warning"><i class="bi bi-star-fill"></i> {{ number_format($stats['note_moyenne'], 1) }}</div>
                <small class="text-muted">Note moy.</small>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="panel">
                <div class="panel-header">
                    <h5 class="mb-0">Liens rapides</h5>
                </div>
                <div class="p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <a href="{{ route('bibliotheque.index') }}" class="btn btn-outline-primary w-100 py-3">
                                <i class="bi bi-folder2Open d-block mb-1" style="font-size: 1.5rem;"></i>
                                Mes documents
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="{{ route('bibliotheque.create') }}" class="btn btn-outline-success w-100 py-3">
                                <i class="bi bi-plus-circle d-block mb-1" style="font-size: 1.5rem;"></i>
                                Nouveau document
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="{{ route('bibliotheque.favoris') }}" class="btn btn-outline-danger w-100 py-3">
                                <i class="bi bi-heart d-block mb-1" style="font-size: 1.5rem;"></i>
                                Mes favoris
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="panel">
                <div class="panel-header">
                    <h5 class="mb-0">Derniers commentaires</h5>
                </div>
                <div class="p-3">
                    @forelse($stats['derniers_commentaires'] as $commentaire)
                        <div class="d-flex align-items-start gap-2 mb-3">
                            <div class="flex-shrink-0">
                                <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center"
                                     style="width: 32px; height: 32px; font-size: 0.75rem;">
                                    {{ strtoupper(substr($commentaire->user?->prenom ?? '?', 0, 1)) }}
                                </div>
                            </div>
                            <div>
                                <div class="fw-semibold" style="font-size: 0.85rem;">
                                    {{ $commentaire->user?->prenom }} {{ $commentaire->user?->nom }}
                                </div>
                                <div class="text-muted" style="font-size: 0.8rem;">
                                    sur « {{ Str::limit($commentaire->document?->titre ?? '', 30) }} »
                                </div>
                                <div style="font-size: 0.82rem;">{{ Str::limit($commentaire->contenu, 80) }}</div>
                                <small class="text-muted">{{ $commentaire->created_at->diffForHumans() }}</small>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted text-center mb-0">Aucun commentaire récent.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

</div>

@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assetBiblio/css/bibliotheque.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('assetBiblio/js/bibliotheque.js') }}"></script>
@endpush
