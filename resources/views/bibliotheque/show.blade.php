@extends('panel.layouts.app')

@section('title', $document->titre . ' — Bibliothèque')

@section('content')

<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon"><i class="bi bi-file-earmark-text"></i></div>
            <div>
                <h1 class="mb-0">{{ $document->titre }}</h1>
                <p class="text-muted mb-0">
                    {{ $document->auteur?->prenom }} {{ $document->auteur?->nom }}
                    · {{ $document->created_at->format('d/m/Y') }}
                </p>
            </div>
        </div>
        <div class="heading-actions">
            @switch($document->statut)
                @case('brouillon')
                    <span class="badge bg-secondary fs-6">Brouillon</span>
                    @break
                @case('en_attente')
                    <span class="badge bg-warning fs-6">En attente</span>
                    @break
                @case('publie')
                    <span class="badge bg-success fs-6">Publié</span>
                    @break
                @case('refuse')
                    <span class="badge bg-danger fs-6">Refusé</span>
                    @break
                @case('archive')
                    <span class="badge bg-dark fs-6">Archivé</span>
                    @break
            @endswitch
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            @if($document->getFirstMedia('document'))
                @php
                    $media = $document->getFirstMedia('document');
                    $mime = $media->mime_type ?? '';
                    $viewUrl = route('bibliothequepub.view', $document->id);
                @endphp
                <div class="panel p-4 mb-4">
                    @if(str_starts_with($mime, 'image/'))
                        <div class="text-center rounded-3 overflow-hidden bg-light">
                            <img src="{{ $viewUrl }}" alt="{{ $document->titre }}"
                                 class="img-fluid">
                        </div>
                    @elseif($mime === 'application/pdf')
                        <div class="rounded-3 overflow-hidden" style="height: 600px;">
                            <iframe src="{{ $viewUrl }}" width="100%" height="100%"
                                    style="border: none;"></iframe>
                        </div>
                    @else
                        <div class="text-center p-5 bg-light rounded-3">
                            <i class="bi bi-file-earmark display-3 text-muted"></i>
                            <p class="mt-2">{{ $media->name ?? $media->file_name }}</p>
                            <a href="{{ route('bibliothequepub.download', $document->id) }}" class="btn btn-success">
                                <i class="bi bi-download me-1"></i>Télécharger
                            </a>
                        </div>
                    @endif
                </div>
            @endif

            @if($document->description)
                <div class="panel p-4 mb-4">
                    <h5>Description</h5>
                    <p>{{ $document->description }}</p>
                </div>
            @endif

            @if($document->resume)
                <div class="panel p-4 mb-4">
                    <h5>Résumé</h5>
                    <p>{{ $document->resume }}</p>
                </div>
            @endif

            <div class="panel p-4 mb-4">
                <h5 class="mb-3">Commentaires ({{ $document->commentaires->count() }})</h5>
                @forelse($document->commentaires->whereNull('parent_id') as $commentaire)
                    <div class="border-bottom pb-3 mb-3">
                        <div class="d-flex justify-content-between">
                            <strong>{{ $commentaire->user?->prenom }} {{ $commentaire->user?->nom }}</strong>
                            <small class="text-muted">{{ $commentaire->created_at->diffForHumans() }}</small>
                        </div>
                        <p class="mb-1 mt-1">{{ $commentaire->contenu }}</p>
                        @foreach($commentaire->replies as $reply)
                            <div class="ms-4 mt-2 p-2 bg-light rounded">
                                <div class="d-flex justify-content-between">
                                    <strong style="font-size: 0.85rem;">{{ $reply->user?->prenom }} {{ $reply->user?->nom }}</strong>
                                    <small class="text-muted">{{ $reply->created_at->diffForHumans() }}</small>
                                </div>
                                <p class="mb-0 mt-1" style="font-size: 0.9rem;">{{ $reply->contenu }}</p>
                            </div>
                        @endforeach
                    </div>
                @empty
                    <p class="text-muted">Aucun commentaire.</p>
                @endforelse
            </div>
        </div>

        <div class="col-lg-4">
            <div class="panel p-4 mb-4">
                <h5>Détails</h5>
                <div class="info-list">
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Type</span>
                        <strong>{{ $document->typeDocument?->nom ?? '-' }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Classe</span>
                        <strong>{{ $document->classe?->nom ?? '-' }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Matière</span>
                        <strong>{{ $document->matiere?->nom ?? '-' }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Période</span>
                        <strong>{{ $document->periodeRef?->nom ?? '-' }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Visibilité</span>
                        <span class="badge {{ $document->is_public ? 'bg-success' : 'bg-secondary' }}">
                            {{ $document->is_public ? 'Public' : 'Privé' }}
                        </span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Vues</span>
                        <strong>{{ number_format($document->nb_vues) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Téléchargements</span>
                        <strong>{{ number_format($document->nb_telechargements) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted"><i class="bi bi-heart-fill text-danger me-1"></i>Favoris</span>
                        <strong>{{ number_format($document->nombre_favoris) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <span class="text-muted">Note moyenne</span>
                        <strong>
                            @if($document->nb_notes > 0)
                                <i class="bi bi-star-fill text-warning"></i>
                                {{ number_format($document->note_moyenne, 1) }}/5
                                ({{ $document->nb_notes }})
                            @else
                                -
                            @endif
                        </strong>
                    </div>
                </div>
            </div>

            @if($document->tags->count())
                <div class="panel p-4 mb-4">
                    <h5>Tags</h5>
                    @foreach($document->tags as $tag)
                        <span class="badge bg-light text-dark me-1 mb-1">#{{ $tag->nom }}</span>
                    @endforeach
                </div>
            @endif

            <div class="d-grid gap-2">
                @if($document->getFirstMedia('document'))
                    <a href="{{ route('bibliothequepub.view', $document->id) }}"
                       target="_blank" class="btn btn-primary">
                        <i class="bi bi-eye me-1"></i>Aperçu
                    </a>
                    <a href="{{ route('bibliothequepub.download', $document->id) }}" class="btn btn-success">
                        <i class="bi bi-download me-1"></i>Télécharger
                    </a>
                @endif
                <button type="button"
                        class="btn {{ $isFavori ? 'btn-danger' : 'btn-outline-danger' }} favori-toggle"
                        data-doc-id="{{ $document->id }}"
                        data-url="{{ route('bibliotheque.toggle-favori-ajax', $document->id) }}">
                    <i class="bi {{ $isFavori ? 'bi-heart-fill' : 'bi-heart' }} me-1"></i>
                    {{ $isFavori ? 'Retirer des favoris' : 'Ajouter aux favoris' }}
                </button>
                @can('update', $document)
                    <a href="{{ route('bibliotheque.edit', $document->id) }}" class="btn btn-warning">
                        <i class="bi bi-pencil me-1"></i>Modifier
                    </a>
                @endcan
                @can('delete', $document)
                    <form action="{{ route('bibliotheque.destroy', $document->id) }}" method="POST"
                          onsubmit="return confirm('Supprimer ce document ?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="bi bi-trash me-1"></i>Supprimer
                        </button>
                    </form>
                @endcan
                <a href="{{ route('bibliotheque.index') }}" class="btn btn-light">
                    <i class="bi bi-arrow-left me-1"></i>Retour
                </a>
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
                icon.classList.add('bi-heart-fill');
                this.classList.remove('btn-outline-danger');
                this.classList.add('btn-danger');
                this.innerHTML = '<i class="bi bi-heart-fill me-1"></i>Retirer des favoris';
            } else {
                icon.classList.remove('bi-heart-fill');
                icon.classList.add('bi-heart');
                this.classList.remove('btn-danger');
                this.classList.add('btn-outline-danger');
                this.innerHTML = '<i class="bi bi-heart me-1"></i>Ajouter aux favoris';
            }
        } catch (err) {
            console.error(err);
        }
    });
});
</script>
@endpush
