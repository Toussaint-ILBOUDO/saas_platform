@extends('panel.layouts.app')

@section('title', 'Actualités — Administration')

@section('content')

<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon"><i class="bi bi-megaphone"></i></div>
            <div>
                <h1 class="mb-0">Actualités</h1>
                <p class="text-muted mb-0">Gestion des actualités du site public</p>
            </div>
        </div>
        <div class="heading-actions">
            <a href="{{ route('admin.actualites.create') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Nouvelle actualité
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
            <form method="GET" action="{{ route('admin.actualites.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label for="search" class="form-label small text-muted mb-1">Recherche</label>
                    <input type="text" name="search" id="search" value="{{ $recherche }}"
                           class="form-control form-control-sm" placeholder="Titre ou résumé...">
                </div>
                <div class="col-md-3">
                    <label for="statut" class="form-label small text-muted mb-1">Statut</label>
                    <select name="statut" id="statut" class="form-select form-select-sm">
                        <option value="">Tous les statuts</option>
                        <option value="brouillon" @selected($filtreStatut === 'brouillon')>Brouillon</option>
                        <option value="publie" @selected($filtreStatut === 'publie')>Publiée</option>
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
                <h5 class="mb-0">Liste des actualités</h5>
                <p class="text-muted mb-0">Rédigez, publiez et notifiez les visiteurs</p>
            </div>
        </div>
        <div class="p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Actualité</th>
                            <th>Auteur</th>
                            <th class="text-center">Statut</th>
                            <th class="text-center">Publiée le</th>
                            <th class="text-center">Vues</th>
                            <th class="text-center">Réactions</th>
                            <th class="text-center">Partages</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($actualites as $actualite)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @if($actualite->getFirstMedia('image_principale'))
                                            <img src="{{ $actualite->image_url }}" alt=""
                                                 style="width: 44px; height: 36px; object-fit: cover; border-radius: 6px;">
                                        @else
                                            <div style="width: 44px; height: 36px; border-radius: 6px;"
                                                 class="bg-light d-flex align-items-center justify-content-center">
                                                <i class="bi bi-newspaper text-muted"></i>
                                            </div>
                                        @endif
                                        <div>
                                            <span class="fw-semibold">{{ $actualite->titre }}</span>
                                            <div class="text-muted" style="font-size: 0.8rem;">/{{ $actualite->slug }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    {{ $actualite->auteur?->prenom }} {{ $actualite->auteur?->nom }}
                                </td>
                                <td class="text-center">
                                    @if($actualite->est_publiee)
                                        <span class="badge bg-success">Publiée</span>
                                    @else
                                        <span class="badge bg-secondary">Brouillon</span>
                                    @endif
                                    <div class="mt-1">
                                        @if($actualite->is_active)
                                            <span class="badge bg-primary-subtle text-primary">Active</span>
                                        @else
                                            <span class="badge bg-light text-muted">Inactive</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-center text-muted">
                                    {{ $actualite->published_at?->format('d/m/Y') ?? '—' }}
                                </td>
                                <td class="text-center">{{ number_format($actualite->nb_vues) }}</td>
                                <td class="text-center">{{ number_format($actualite->nb_reactions) }}</td>
                                <td class="text-center">{{ number_format($actualite->nb_partages) }}</td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        @if($actualite->est_publiee)
                                            <a href="{{ route('actualites.show', $actualite->slug) }}" target="_blank"
                                               class="btn btn-sm btn-light" title="Voir sur le site">
                                                <i class="bi bi-box-arrow-up-right"></i>
                                            </a>
                                            <form action="{{ route('admin.actualites.depublier', $actualite) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-light" title="Dépublier">
                                                    <i class="bi bi-arrow-return-left"></i>
                                                </button>
                                            </form>
                                        @else
                                            <a href="{{ route('admin.actualites.publier.form', $actualite) }}"
                                               class="btn btn-sm btn-light" title="Publier et notifier">
                                                <i class="bi bi-send"></i>
                                            </a>
                                        @endif
                                        <a href="{{ route('admin.actualites.edit', $actualite) }}"
                                           class="btn btn-sm btn-light" title="Modifier">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('admin.actualites.toggle', $actualite) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-light" title="{{ $actualite->is_active ? 'Désactiver' : 'Activer' }}">
                                                <i class="bi {{ $actualite->is_active ? 'bi-eye-slash' : 'bi-eye' }}"></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.actualites.destroy', $actualite) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Supprimer définitivement cette actualité ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light text-danger" title="Supprimer">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="blank-state">
                                        <i class="bi bi-megaphone display-4 text-muted"></i>
                                        <p class="text-muted mt-2">Aucune actualité.</p>
                                        <a href="{{ route('admin.actualites.create') }}" class="btn btn-primary btn-sm">
                                            Créer une actualité
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($actualites->hasPages())
            <div class="panel-footer">
                {{ $actualites->links() }}
            </div>
        @endif
    </div>

</div>

@endsection
