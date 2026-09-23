@extends('panel.layouts.app')

@section('title', 'Signalements — Témoignages')

@section('content')

<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon"><i class="bi bi-flag"></i></div>
            <div>
                <h1 class="mb-0">Signalements de témoignages</h1>
                <p class="text-muted mb-0">Traitez les signalements reçus sur les témoignages</p>
            </div>
        </div>
        <div class="heading-actions">
            <a href="{{ route('admin.temoignages.index') }}" class="btn btn-sm btn-light">
                <i class="bi bi-arrow-left me-1"></i>Retour
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="panel mb-3">
        <div class="panel-body">
            <form method="GET" action="{{ route('admin.temoignages.signalements') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label for="statut" class="form-label small text-muted mb-1">Statut</label>
                    <select name="statut" id="statut" class="form-select form-select-sm">
                        <option value="">Tous les statuts</option>
                        <option value="en_attente" @selected($filtreStatut === 'en_attente')>En attente</option>
                        <option value="traite" @selected($filtreStatut === 'traite')>Traité</option>
                        <option value="rejete" @selected($filtreStatut === 'rejete')>Rejeté</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-search me-1"></i>Filtrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header">
            <div>
                <h5 class="mb-0">Liste des signalements</h5>
                <p class="text-muted mb-0">« Traiter » masque automatiquement le témoignage concerné</p>
            </div>
        </div>
        <div class="p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Motif</th>
                            <th>Description</th>
                            <th>Signalé par</th>
                            <th>Témoignage</th>
                            <th class="text-center">Statut</th>
                            <th class="text-center">Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($signalements as $signalement)
                            <tr>
                                <td>{{ $signalement->motif }}</td>
                                <td style="max-width: 260px;">
                                    <span class="d-block text-truncate">{{ $signalement->description ?? '—' }}</span>
                                </td>
                                <td>{{ $signalement->user?->prenom }} {{ $signalement->user?->nom }}</td>
                                <td style="max-width: 240px;">
                                    <span class="d-block text-truncate">{{ $signalement->temoignage?->contenu }}</span>
                                    <div class="text-muted" style="font-size: 0.78rem;">
                                        @if($signalement->temoignage?->est_publie)
                                            <a href="{{ route('temoignages.show', $signalement->temoignage->slug) }}" target="_blank">
                                                Voir en ligne
                                            </a>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning">Masqué</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-center">
                                    @if($signalement->statut === 'en_attente')
                                        <span class="badge bg-danger">En attente</span>
                                    @elseif($signalement->statut === 'traite')
                                        <span class="badge bg-success">Traité</span>
                                    @else
                                        <span class="badge bg-light text-muted">Rejeté</span>
                                    @endif
                                </td>
                                <td class="text-center text-muted">{{ $signalement->created_at->format('d/m/Y') }}</td>
                                <td class="text-end">
                                    @if($signalement->statut === 'en_attente')
                                        <div class="btn-group">
                                            <form action="{{ route('admin.temoignages.signalements.traiter', $signalement) }}" method="POST" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="action" value="traite">
                                                <button type="submit" class="btn btn-sm btn-danger"
                                                        onclick="return confirm('Traiter ce signalement (masquer le témoignage) ?');">
                                                    <i class="bi bi-check-lg me-1"></i>Traiter
                                                </button>
                                            </form>
                                            <form action="{{ route('admin.temoignages.signalements.traiter', $signalement) }}" method="POST" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="action" value="rejete">
                                                <button type="submit" class="btn btn-sm btn-light">Rejeter</button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="blank-state">
                                        <i class="bi bi-flag display-4 text-muted"></i>
                                        <p class="text-muted mt-2">Aucun signalement.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($signalements->hasPages())
            <div class="panel-footer">
                {{ $signalements->links() }}
            </div>
        @endif
    </div>

</div>

@endsection
