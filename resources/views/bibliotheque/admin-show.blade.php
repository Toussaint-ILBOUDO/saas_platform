@extends('panel.layouts.app')

@section('title', 'Modération — ' . $document->titre)

@section('content')

<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon"><i class="bi bi-shield-check"></i></div>
            <div>
                <h1 class="mb-0">{{ $document->titre }}</h1>
                <p class="text-muted mb-0">Par {{ $document->auteur?->prenom }} {{ $document->auteur?->nom }}</p>
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
                @endphp
                <div class="panel p-4 mb-4">
                    @if(str_starts_with($mime, 'image/'))
                        <div class="text-center rounded-3 overflow-hidden bg-light">
                            <img src="{{ route('bibliothequepub.view', $document->id) }}" class="img-fluid" alt="{{ $document->titre }}">
                        </div>
                    @elseif($mime === 'application/pdf')
                        <div class="rounded-3 overflow-hidden" style="height: 500px;">
                            <iframe src="{{ route('bibliothequepub.view', $document->id) }}" width="100%" height="100%" style="border: none;"></iframe>
                        </div>
                    @else
                        <div class="text-center p-5 bg-light rounded-3">
                            <i class="bi bi-file-earmark display-3 text-muted"></i>
                            <p class="mt-2">{{ $media->name ?? $media->file_name }}</p>
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

            @if($document->signalements->count())
                <div class="panel p-4 mb-4 border-danger">
                    <h5 class="text-danger"><i class="bi bi-flag me-2"></i>Signalements ({{ $document->signalements->count() }})</h5>
                    @foreach($document->signalements as $signalement)
                        <div class="border-bottom pb-2 mb-2">
                            <div class="d-flex justify-content-between">
                                <strong>{{ $signalement->user?->prenom }} {{ $signalement->user?->nom }}</strong>
                                <span class="badge {{ $signalement->statut === 'en_attente' ? 'bg-warning' : 'bg-success' }}">
                                    {{ $signalement->statut }}
                                </span>
                            </div>
                            <p class="mb-0"><strong>Motif :</strong> {{ $signalement->motif }}</p>
                            @if($signalement->description)
                                <p class="mb-0 text-muted">{{ $signalement->description }}</p>
                            @endif
                        </div>
                    @endforeach
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
                                <strong style="font-size: 0.85rem;">{{ $reply->user?->prenom }} {{ $reply->user?->nom }}</strong>
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
                        <span class="text-muted">Auteur</span>
                        <strong>{{ $document->auteur?->prenom }} {{ $document->auteur?->nom }}</strong>
                    </div>
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
                    <div class="d-flex justify-content-between py-2">
                        <span class="text-muted">Note</span>
                        <strong>
                            @if($document->nb_notes > 0)
                                <i class="bi bi-star-fill text-warning"></i>
                                {{ number_format($document->note_moyenne, 1) }}/5
                            @else
                                -
                            @endif
                        </strong>
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2">
                @if($document->statut === 'en_attente')
                    <form action="{{ route('admin.bibliotheque.valider', $document->id) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-check-circle me-1"></i>Valider et publier
                        </button>
                    </form>
                    <form action="{{ route('admin.bibliotheque.refuser', $document->id) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="bi bi-x-circle me-1"></i>Refuser
                        </button>
                    </form>
                @endif

                @if($document->statut !== 'archive')
                    <form action="{{ route('admin.bibliotheque.archiver', $document->id) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-outline-dark w-100">
                            <i class="bi bi-archive me-1"></i>Archiver
                        </button>
                    </form>
                @endif

                <form action="{{ route('admin.bibliotheque.destroy', $document->id) }}" method="POST"
                      onsubmit="return confirm('Supprimer définitivement ce document ?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger w-100">
                        <i class="bi bi-trash me-1"></i>Supprimer
                    </button>
                </form>

                <a href="{{ route('admin.bibliotheque.index') }}" class="btn btn-light">
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
@endpush
