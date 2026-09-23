@extends('panel.layouts.app')

@section('title', 'Mes favoris — Bibliothèque')

@section('content')

<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon"><i class="bi bi-heart"></i></div>
            <div>
                <h1 class="mb-0">Mes favoris</h1>
                <p class="text-muted mb-0">Documents que vous avez ajoutés à vos favoris</p>
            </div>
        </div>
        <div class="heading-actions">
            <a href="{{ route('bibliotheque.index') }}" class="btn btn-outline-primary">
                <i class="bi bi-folder2-open me-1"></i>Mes documents
            </a>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header">
            <form action="{{ route('bibliotheque.favoris') }}" method="GET" class="d-flex gap-2 flex-wrap">
                <input type="text" name="search" class="form-control table-search"
                       placeholder="Rechercher dans mes favoris..." value="{{ request('search') }}">
                <select name="type_document_id" class="form-select" style="max-width: 180px;" onchange="this.form.submit()">
                    <option value="">Tous les types</option>
                    @foreach($typesDocument as $type)
                        <option value="{{ $type->id }}" {{ request('type_document_id') == $type->id ? 'selected' : '' }}>
                            {{ $type->nom }}
                        </option>
                    @endforeach
                </select>
                <select name="classe_id" class="form-select" style="max-width: 180px;" onchange="this.form.submit()">
                    <option value="">Toutes les classes</option>
                    @foreach($classes as $classe)
                        <option value="{{ $classe->id }}" {{ request('classe_id') == $classe->id ? 'selected' : '' }}>
                            {{ $classe->nom }}
                        </option>
                    @endforeach
                </select>
                <select name="matiere_id" class="form-select" style="max-width: 180px;" onchange="this.form.submit()">
                    <option value="">Toutes les matières</option>
                    @foreach($matieres as $matiere)
                        <option value="{{ $matiere->id }}" {{ request('matiere_id') == $matiere->id ? 'selected' : '' }}>
                            {{ $matiere->nom }}
                        </option>
                    @endforeach
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
                                                title="Retirer des favoris">
                                            <i class="bi bi-heart-fill text-danger"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="blank-state">
                                        <i class="bi bi-heart display-4 text-muted"></i>
                                        <p class="text-muted mt-2">Aucun document en favoris.</p>
                                        <a href="{{ route('bibliothequepub.index') }}" class="btn btn-primary btn-sm mt-2">
                                            <i class="bi bi-search me-1"></i>Parcourir la bibliothèque
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
        const row = this.closest('tr');

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
            if (!data.is_favori) {
                row.style.transition = 'opacity 0.3s';
                row.style.opacity = '0';
                setTimeout(() => row.remove(), 300);
            }
        } catch (err) {
            console.error(err);
        }
    });
});
</script>
@endpush
