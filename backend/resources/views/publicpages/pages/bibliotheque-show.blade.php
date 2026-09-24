@extends('publicpages.layouts.public')

@section('title', $document->titre . ' | K\'Educ')
@section('meta_description', Str::limit($document->description ?? $document->titre, 160))
@section('body_class', 'index-page')

@section('content')

<section id="doc-detail" class="section" style="padding: 40px 0;">
    <div class="container">
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('bibliothequepub.index') }}">Bibliothèque</a></li>
                @if($document->typeDocument)
                    <li class="breadcrumb-item">
                        <a href="{{ route('bibliothequepub.index', ['type_document_id' => $document->type_document_id]) }}">
                            {{ $document->typeDocument->nom }}
                        </a>
                    </li>
                @endif
                <li class="breadcrumb-item active">{{ Str::limit($document->titre, 40) }}</li>
            </ol>
        </nav>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="panel p-4">
                    <div class="d-flex align-items-start justify-content-between mb-3">
                        <div>
                            <h1 class="h3 mb-1" style="color: var(--heading-color);">
                                {{ $document->titre }}
                            </h1>
                            <p class="text-muted mb-0">
                                <i class="bi bi-person me-1"></i>
                                {{ $document->auteur?->prenom }} {{ $document->auteur?->nom }}
                                <span class="mx-2">·</span>
                                {{ $document->created_at->format('d/m/Y') }}
                                @if($document->periodeRef)
                                    <span class="mx-2">·</span>
                                    {{ $document->periodeRef->nom }}
                                @endif
                            </p>
                        </div>
                        <div class="d-flex gap-2">
                            @if($document->getFirstMedia('document'))
                                <a href="{{ route('bibliothequepub.view', $document->id) }}"
                                   target="_blank"
                                   class="btn btn-primary btn-sm">
                                    <i class="bi bi-eye me-1"></i>Aperçu
                                </a>
                                <a href="{{ route('bibliothequepub.download', $document->id) }}"
                                   class="btn btn-success btn-sm">
                                    <i class="bi bi-download me-1"></i>Télécharger
                                </a>
                            @endif
                            @auth
                                <button type="button"
                                        class="btn btn-outline-danger btn-sm favori-toggle"
                                        data-doc-id="{{ $document->id }}"
                                        data-url="{{ route('bibliotheque.toggle-favori-ajax', $document->id) }}"
                                        aria-label="{{ $isFavori ? 'Retirer des favoris' : 'Ajouter aux favoris' }}">
                                    <i class="bi {{ $isFavori ? 'bi-heart-fill' : 'bi-heart' }}"></i>
                                </button>
                            @endauth
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mb-4">
                        @if($document->typeDocument)
                            <span class="badge" style="background: #2563eb; color: #fff;">{{ $document->typeDocument->nom }}</span>
                        @endif
                        @if($document->classe)
                            <span class="badge bg-primary-subtle text-primary">
                                <i class="bi bi-mortarboard me-1"></i>{{ $document->classe->nom }}
                            </span>
                        @endif
                        @if($document->matiere)
                            <span class="badge bg-info-subtle text-info">
                                <i class="bi bi-book me-1"></i>{{ $document->matiere->nom }}
                            </span>
                        @endif
                        @forelse($document->tags as $tag)
                            <a href="{{ route('bibliothequepub.index', ['tag' => $tag->slug]) }}"
                               class="badge bg-light text-dark text-decoration-none">
                                #{{ $tag->nom }}
                            </a>
                        @empty
                            <span class="text-muted small">Aucun tag associé</span>
                        @endforelse
                    </div>

                    @if($document->description)
                        <div class="mb-4">
                            <h5>Description</h5>
                            <p>{{ $document->description }}</p>
                        </div>
                    @endif

                    @if($document->resume)
                        <div class="mb-4">
                            <h5>Résumé</h5>
                            <p>{{ $document->resume }}</p>
                        </div>
                    @endif

                    @if($document->getFirstMedia('document'))
                        <div class="mb-4">
                            <h5>Aperçu du document</h5>
                            @php
                                $media = $document->getFirstMedia('document');
                                $mime = $media->mime_type ?? '';
                                $viewUrl = route('bibliothequepub.view', $document->id);
                            @endphp

                            @if(str_starts_with($mime, 'image/'))
                                <div class="text-center border rounded-3 overflow-hidden bg-light">
                                    <img src="{{ $viewUrl }}" alt="{{ $document->titre }}"
                                         class="img-fluid" style="max-height: 600px;">
                                </div>
                            @elseif($mime === 'application/pdf')
                                <div class="border rounded-3 overflow-hidden" style="height: 600px;">
                                    <iframe src="{{ $viewUrl }}" width="100%" height="100%"
                                            style="border: none;" title="{{ $document->titre }}"></iframe>
                                </div>
                            @else
                                <div class="text-center p-5 bg-light rounded-3">
                                    <i class="bi bi-file-earmark display-3 text-muted"></i>
                                    <p class="mt-2 text-muted">{{ $media->name ?? $media->file_name }}</p>
                                    <a href="{{ route('bibliothequepub.download', $document->id) }}" class="btn btn-success">
                                        <i class="bi bi-download me-1"></i>Télécharger le fichier
                                    </a>
                                </div>
                            @endif
                        </div>
                    @endif

                    <div class="d-flex flex-wrap gap-3 text-muted" style="font-size: 0.85rem;">
                        <span><i class="bi bi-eye me-1"></i>{{ number_format($document->nb_vues) }} vues</span>
                        <span><i class="bi bi-download me-1"></i>{{ number_format($document->nb_telechargements) }} téléchargements</span>
                        @if($document->nombre_favoris > 0)
                            <span><i class="bi bi-heart-fill text-danger me-1"></i>{{ number_format($document->nombre_favoris) }} favoris</span>
                        @endif
                        @if($document->nb_notes > 0)
                            <span>
                                <i class="bi bi-star-fill text-warning me-1"></i>
                                {{ number_format($document->note_moyenne, 1) }}/5
                                ({{ $document->nb_notes }} note{{ $document->nb_notes > 1 ? 's' : '' }})
                            </span>
                        @endif
                    </div>
                </div>

                <div class="panel p-4 mt-4">
                    <h5 class="mb-3"><i class="bi bi-star me-2"></i>Noter ce document</h5>
                    @auth
                        <div class="d-flex align-items-center gap-3">
                            <div class="doc-card-rating" data-document-id="{{ $document->id }}" data-current="{{ $userNote ?? 0 }}">
                                @for($i = 1; $i <= 5; $i++)
                                    <span class="star {{ $i <= ($userNote ?? 0) ? 'text-warning' : 'text-muted' }}"
                                          data-note="{{ $i }}"
                                          style="cursor: pointer; font-size: 1.5rem;">
                                        <i class="bi bi-star-fill"></i>
                                    </span>
                                @endfor
                            </div>
                            <span class="text-muted" style="font-size: 0.85rem;">
                                @if($userNote)
                                    Votre note : {{ $userNote }}/5
                                @else
                                    Cliquez pour noter
                                @endif
                            </span>
                        </div>
                    @else
                        <p class="text-muted mb-0">
                            <a href="{{ route('login') }}">Connectez-vous</a> pour noter ce document.
                        </p>
                    @endauth
                </div>

                <div class="panel p-4 mt-4">
                    <h5 class="mb-3"><i class="bi bi-chat-dots me-2"></i>Commentaires ({{ $document->commentaires->count() }})</h5>

                    @auth
                        <form action="{{ route('bibliotheque.comment', $document->id) }}" method="POST" class="mb-4">
                            @csrf
                            <div class="mb-3">
                                <label for="commentaire-contenu" class="form-label visually-hidden">Votre commentaire</label>
                                <textarea name="contenu" id="commentaire-contenu" rows="3" class="form-control"
                                          placeholder="Votre commentaire..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-success btn-sm">
                                <i class="bi bi-send me-1"></i>Publier
                            </button>
                        </form>
                    @else
                        <p class="text-muted mb-4">
                            <a href="{{ route('login') }}">Connectez-vous</a> pour laisser un commentaire.
                        </p>
                    @endauth

                    @forelse($document->commentaires->whereNull('parent_id') as $commentaire)
                        <div class="border-bottom pb-3 mb-3">
                            <div class="d-flex justify-content-between">
                                <strong>{{ $commentaire->user?->prenom }} {{ $commentaire->user?->nom }}</strong>
                                <small class="text-muted">{{ $commentaire->created_at->diffForHumans() }}</small>
                            </div>
                            <p class="mb-1 mt-1">{{ $commentaire->contenu }}</p>

                            @forelse($commentaire->replies as $reply)
                                <div class="ms-4 mt-2 p-2 bg-light rounded">
                                    <div class="d-flex justify-content-between">
                                        <strong style="font-size: 0.85rem;">{{ $reply->user?->prenom }} {{ $reply->user?->nom }}</strong>
                                        <small class="text-muted">{{ $reply->created_at->diffForHumans() }}</small>
                                    </div>
                                    <p class="mb-0 mt-1" style="font-size: 0.9rem;">{{ $reply->contenu }}</p>
                                </div>
                            @empty
                                <p class="text-muted small ms-4 mt-2 mb-0">Aucune réponse pour l'instant.</p>
                            @endforelse
                        </div>
                    @empty
                        <p class="text-muted text-center">Aucun commentaire pour l'instant.</p>
                    @endforelse
                </div>

                @auth
                    @can('report', $document)
                        <div class="panel p-4 mt-4">
                            <h5 class="mb-3"><i class="bi bi-flag me-2"></i>Signaler ce document</h5>
                            <form action="{{ route('bibliotheque.report', $document->id) }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label for="motif-report" class="form-label">Motif du signalement</label>
                                    <select name="motif" id="motif-report" class="form-select" required>
                                        <option value="">Choisir un motif...</option>
                                        <option value="Contenu inapproprié">Contenu inapproprié</option>
                                        <option value="Violation de droits d'auteur">Violation de droits d'auteur</option>
                                        <option value="Spam">Spam</option>
                                        <option value="Information erronée">Information erronée</option>
                                        <option value="Autre">Autre</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <textarea name="description" rows="2" class="form-control"
                                              placeholder="Description (optionnel)"></textarea>
                                </div>
                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                    <i class="bi bi-flag me-1"></i>Signaler
                                </button>
                            </form>
                        </div>
                    @endcan
                @endauth
            </div>

            <div class="col-lg-4">
                <div class="panel p-4 mb-4">
                    <h5 class="mb-3"><i class="bi bi-info-circle me-2"></i>Détails</h5>
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
                        <div class="d-flex justify-content-between py-2">
                            <span class="text-muted">Publié le</span>
                            <strong>{{ $document->created_at->format('d/m/Y') }}</strong>
                        </div>
                    </div>
                </div>

                @if($document->getFirstMedia('document'))
                    <div class="panel p-4 mb-4">
                        <a href="{{ route('bibliothequepub.download', $document->id) }}"
                           class="btn btn-success w-100">
                            <i class="bi bi-download me-1"></i>Télécharger
                        </a>
                    </div>
                @endif

                @if($documentsSimilaires->count())
                    <div class="panel p-4">
                        <h5 class="mb-3"><i class="bi bi-collection me-2"></i>Documents similaires</h5>
                        @foreach($documentsSimilaires as $sim)
                            <div class="d-flex align-items-start gap-2 mb-3">
                                <div class="doc-card-icon flex-shrink-0" style="background: #2563eb; width: 36px; height: 36px; font-size: 0.9rem;">
                                    <i class="bi bi-file-earmark-text"></i>
                                </div>
                                <div>
                                    <a href="{{ route('bibliothequepub.show', $sim->slug) }}"
                                       class="text-decoration-none fw-semibold"
                                       style="color: var(--heading-color); font-size: 0.85rem;">
                                        {{ Str::limit($sim->titre, 40) }}
                                    </a>
                                    <div class="text-muted" style="font-size: 0.75rem;">
                                        {{ $sim->matiere?->nom ?? '' }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assetBiblio/css/bibliotheque.css') }}">
@endpush

@push('scripts')
<script>window.BIBLIO_NOTE_URL = '{{ route("bibliothequepub.note", "__ID__") }}';</script>
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
            } else {
                icon.classList.remove('bi-heart-fill');
                icon.classList.add('bi-heart');
                this.classList.remove('btn-danger');
                this.classList.add('btn-outline-danger');
            }
        } catch (err) {
            console.error(err);
        }
    });
});
</script>
@endpush
