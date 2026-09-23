@extends('panel.layouts.app')

@section('title', $actualite->titre . ' | Actualités internes')

@section('content')

<div class="container-fluid px-3 px-lg-4 py-4">

    <div class="page-heading">

        <div class="page-heading-copy">

            <div class="page-icon">
                <i class="bi bi-megaphone"></i>
            </div>

            <div>

                <h1 class="mb-0">
                    {{ $actualite->titre }}
                </h1>

                <p class="text-muted mb-0">
                    <a href="{{ route('actualites.internes') }}" class="text-decoration-none">
                        <i class="bi bi-arrow-left me-1"></i>
                        Retour aux actualités internes
                    </a>
                </p>

            </div>

        </div>

        <div class="page-heading-actions">

            <span class="text-muted small">
                <i class="bi bi-calendar3 me-1"></i>
                {{ $actualite->published_at?->format('d/m/Y à H\hi') ?? $actualite->created_at->format('d/m/Y') }}
            </span>

        </div>

    </div>

    <article class="panel p-4">

        @if($actualite->getFirstMedia('image_principale'))

            <figure class="mb-4">
                <img
                    src="{{ $actualite->image_original }}"
                    alt="{{ $actualite->titre }}"
                    class="img-fluid rounded w-100"
                    style="max-height: 420px; object-fit: cover;"
                >
            </figure>

        @endif

        <div class="d-flex flex-wrap gap-2 mb-3">

            @if($actualite->est_globale)

                <span class="badge text-bg-secondary">
                    <i class="bi bi-globe me-1"></i>
                    Toute la communauté
                </span>

            @else

                @foreach($actualite->destinataires_labels as $badge)

                    <span class="badge text-bg-light border">
                        <i class="bi {{ $badge['icon'] }} me-1"></i>
                        {{ $badge['label'] }}
                    </span>

                @endforeach

            @endif

        </div>

        <div class="d-flex align-items-center text-muted mb-4" style="font-size: 0.85rem;">

            @if($actualite->auteur)

                <span class="me-3">
                    <i class="bi bi-person me-1"></i>
                    {{ $actualite->auteur->prenom }} {{ $actualite->auteur->nom }}
                </span>

            @endif

            <span class="me-3" title="Vues">
                <i class="bi bi-eye me-1"></i>{{ number_format($actualite->nb_vues) }} vues
            </span>
            <span class="me-3" title="Réactions">
                <i class="bi bi-emoji-smile me-1"></i>{{ number_format($actualite->nb_reactions) }} réactions
            </span>
            <span title="Partages">
                <i class="bi bi-share me-1"></i>{{ number_format($actualite->nb_partages) }} partages
            </span>

        </div>

        @if($actualite->resume)

            <p class="lead">{{ $actualite->resume }}</p>

        @endif

        <div class="actu-article-content" style="line-height: 1.8;">
            {!! nl2br(e($actualite->contenu)) !!}
        </div>

        @if($actualite->video_embed_url)

            <figure class="mt-4">
                <div class="ratio ratio-16x9">
                    @if(str_ends_with($actualite->video_embed_url, '.mp4'))
                        <video controls preload="metadata" poster="{{ $actualite->image_original }}">
                            <source src="{{ $actualite->video_embed_url }}" type="video/mp4">
                            Votre navigateur ne supporte pas la lecture vidéo.
                        </video>
                    @else
                        <iframe src="{{ $actualite->video_embed_url }}"
                                title="Vidéo de l'actualité : {{ $actualite->titre }}"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen></iframe>
                    @endif
                </div>
            </figure>

        @endif

        @if($actualite->galerie->isNotEmpty())

            <figure class="mt-4">
                <figcaption class="h5 mb-3">Galerie</figcaption>
                <div class="row g-2">
                    @foreach($actualite->galerie as $media)
                        <div class="col-6 col-md-3">
                            <a href="{{ $media->getUrl() }}" target="_blank" rel="noopener">
                                <img src="{{ $media->getUrl() }}" alt="{{ $actualite->titre }} — photo"
                                     class="img-fluid rounded" loading="lazy"
                                     style="object-fit: cover; height: 120px; width: 100%;">
                            </a>
                        </div>
                    @endforeach
                </div>
            </figure>

        @endif

        @if($actualite->document_url || $actualite->lien_externe)

            <div class="mt-4 d-flex flex-wrap gap-2">

                @if($actualite->document_url)

                    <a href="{{ $actualite->document_url }}" target="_blank" rel="noopener"
                       class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-file-earmark-pdf me-1"></i>Télécharger le document
                    </a>

                @endif

                @if($actualite->lien_externe)

                    <a href="{{ $actualite->lien_externe }}" target="_blank" rel="noopener nofollow"
                       class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-box-arrow-up-right me-1"></i>Lien externe
                    </a>

                @endif

            </div>

        @endif

    </article>

</div>

@endsection