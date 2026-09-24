@extends('panel.layouts.app')

@section('title', 'Mes documents — Bibliothèque')

@section('content')

<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon"><i class="bi bi-folder2Open"></i></div>
            <div>
                <h1 class="mb-0">Mes documents</h1>
                <p class="text-muted mb-0">Gérez vos ressources pédagogiques</p>
            </div>
        </div>
        <div class="heading-actions">
            <a href="{{ route('bibliotheque.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Nouveau document
            </a>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header">
            <form action="{{ route('bibliotheque.index') }}" method="GET" class="d-flex gap-2 flex-wrap">
                <input type="text" name="search" class="form-control table-search"
                       placeholder="Rechercher..." value="{{ request('search') }}">
                <select name="statut" class="form-select" style="max-width: 180px;" onchange="this.form.submit()">
                    <option value="">Tous les statuts</option>
                    <option value="brouillon" {{ request('statut') === 'brouillon' ? 'selected' : '' }}>Brouillon</option>
                    <option value="en_attente" {{ request('statut') === 'en_attente' ? 'selected' : '' }}>En attente</option>
                    <option value="publie" {{ request('statut') === 'publie' ? 'selected' : '' }}>Publié</option>
                    <option value="refuse" {{ request('statut') === 'refuse' ? 'selected' : '' }}>Refusé</option>
                    <option value="archive" {{ request('statut') === 'archive' ? 'selected' : '' }}>Archivé</option>
                </select>
                <select name="is_public" class="form-select" style="max-width: 150px;" onchange="this.form.submit()">
                    <option value="">Toute visibilité</option>
                    <option value="1" {{ request('is_public') === '1' ? 'selected' : '' }}>Public</option>
                    <option value="0" {{ request('is_public') === '0' ? 'selected' : '' }}>Privé</option>
                </select>
                <button type="submit" class="btn btn-sm btn-light">
                    <i class="bi bi-search"></i>
                </button>
            </form>
        </div>
        <div class="p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="min-width: 250px;">Document</th>
                            <th>Type</th>
                            <th>Matière</th>
                            <th>Statut</th>
                            <th class="text-center">Vues</th>
                            <th class="text-center">Note</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($documents as $doc)
                            <tr>
                                <td>
                                    <a href="{{ route('bibliotheque.show', $doc->id) }}" class="fw-semibold text-decoration-none" style="color: var(--heading-color);">
                                        {{ Str::limit($doc->titre, 50) }}
                                    </a>
                                    <div class="text-muted" style="font-size: 0.8rem;">
                                        {{ $doc->created_at->format('d/m/Y') }}
                                        @if($doc->is_public)
                                            <span class="badge bg-success-subtle text-success ms-1">Public</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary ms-1">Privé</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary">
                                        {{ $doc->typeDocument?->sigle ?? '-' }}
                                    </span>
                                </td>
                                <td>{{ $doc->matiere?->nom ?? '-' }}</td>
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
                                <td class="text-center">
                                    @if($doc->nb_notes > 0)
                                        <i class="bi bi-star-fill text-warning"></i>
                                        {{ number_format($doc->note_moyenne, 1) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        @if($doc->getFirstMedia('document'))
                                            <a href="{{ route('bibliothequepub.view', $doc->id) }}"
                                               target="_blank"
                                               class="btn btn-sm btn-light" title="Aperçu">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route('bibliothequepub.download', $doc->id) }}"
                                               class="btn btn-sm btn-light" title="Télécharger">
                                                <i class="bi bi-download"></i>
                                            </a>
                                        @endif
                                        <a href="{{ route('bibliotheque.show', $doc->id) }}"
                                           class="btn btn-sm btn-light" title="Détails">
                                            <i class="bi bi-info-circle"></i>
                                        </a>
                                        <button type="button"
                                                class="btn btn-sm btn-light favori-toggle"
                                                data-doc-id="{{ $doc->id }}"
                                                data-url="{{ route('bibliotheque.toggle-favori-ajax', $doc->id) }}"
                                                title="{{ $doc->is_favori ? 'Retirer des favoris' : 'Ajouter aux favoris' }}">
                                            <i class="bi {{ $doc->is_favori ? 'bi-heart-fill text-danger' : 'bi-heart' }}"></i>
                                        </button>
                                        @can('update', $doc)
                                            <a href="{{ route('bibliotheque.edit', $doc->id) }}"
                                               class="btn btn-sm btn-light" title="Modifier">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        @endcan
                                        @can('delete', $doc)
                                            <form action="{{ route('bibliotheque.destroy', $doc->id) }}" method="POST"
                                                  onsubmit="return confirm('Supprimer ce document ?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-light text-danger" title="Supprimer">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="blank-state">
                                        <i class="bi bi-inbox display-4 text-muted"></i>
                                        <p class="text-muted mt-2">Aucun document.</p>
                                        <a href="{{ route('bibliotheque.create') }}" class="btn btn-primary btn-sm mt-2">
                                            <i class="bi bi-plus me-1"></i>Créer un document
                                        </a>
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
<script>
document.querySelectorAll('.favori-toggle').forEach(btn => {
    btn.addEventListener('click', async function(e) {
        e.preventDefault();
        const url = this.dataset.url;
        const icon = this.querySelector('i');

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const data = await response.json();
            if (data.is_favori) {
                icon.classList.remove('bi-heart');
                icon.classList.add('bi-heart-fill', 'text-danger');
                this.setAttribute('title', 'Retirer des favoris');
            } else {
                icon.classList.remove('bi-heart-fill', 'text-danger');
                icon.classList.add('bi-heart');
                this.setAttribute('title', 'Ajouter aux favoris');
            }
        } catch (err) {
            console.error(err);
        }
    });
});
</script>
@endpush
