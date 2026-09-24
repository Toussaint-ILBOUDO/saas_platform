{{-- ======================================================
    Page : Détail d'une actualité
    Layout : layouts/public
    Source : app/Modules/Communication (PublicActualiteController)
====================================================== --}}

@extends('publicpages.layouts.public')

@section('title', $actualite->titre . ' | K\'Educ')
@section('meta_description', Str::limit($actualite->resume ?? $actualite->contenu, 160))
@section('body_class', 'index-page')

@section('content')

<section id="actualite-detail" class="section" style="padding: 40px 0;">
    <div class="container">

        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Accueil</a></li>
                <li class="breadcrumb-item"><a href="{{ route('actualites.index') }}">Actualités</a></li>
                <li class="breadcrumb-item active">{{ Str::limit($actualite->titre, 40) }}</li>
            </ol>
        </nav>

        <div class="row g-4">

            <div class="col-lg-8">

                <article class="actu-article">

                    <header class="actu-article-header">
                        <h1 class="h2 mb-2" style="color: var(--heading-color);">
                            {{ $actualite->titre }}
                        </h1>

                        <div class="actu-article-meta text-muted">
                            <time datetime="{{ $actualite->published_at?->toIso8601String() ?? $actualite->created_at->toIso8601String() }}">
                                <i class="bi bi-calendar3 me-1"></i>
                                {{ $actualite->published_at?->format('d/m/Y à H\hi') ?? $actualite->created_at->format('d/m/Y') }}
                            </time>
                            @if($actualite->auteur)
                                <span class="mx-2">·</span>
                                <span><i class="bi bi-person me-1"></i>{{ $actualite->auteur->prenom }} {{ $actualite->auteur->nom }}</span>
                            @endif
                        </div>

                        <div class="actu-article-stats mt-3">
                            <span title="Nombre de vues"><i class="bi bi-eye me-1"></i>{{ number_format($actualite->nb_vues) }} vues</span>
                            <span title="Nombre de réactions"><i class="bi bi-emoji-smile me-1"></i>{{ number_format($actualite->nb_reactions) }} réactions</span>
                            <span title="Nombre de partages"><i class="bi bi-share me-1"></i>{{ number_format($actualite->nb_partages) }} partages</span>
                        </div>
                    </header>

                    @if($actualite->getFirstMedia('image_principale'))
                        <figure class="actu-article-figure">
                            <img src="{{ $actualite->image_original }}"
                                 alt="{{ $actualite->titre }}"
                                 class="img-fluid w-100">
                        </figure>
                    @endif

                    <div class="actu-article-body">
                        @if($actualite->resume)
                            <p class="actu-article-resume fw-semibold">{{ $actualite->resume }}</p>
                        @endif

                        <div class="actu-article-content">
                            {!! nl2br(e($actualite->contenu)) !!}
                        </div>
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
                        <div class="actu-article-links mt-4 d-flex flex-wrap gap-2">
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

                    {{-- Réactions --}}
                    <div class="actu-reactions panel p-3 mt-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h2 class="h6 fw-bold mb-0">Votre réaction</h2>
                            <span class="text-muted small" id="actu-total-reactions">
                                {{ $actualite->nb_reactions }} réaction{{ $actualite->nb_reactions > 1 ? 's' : '' }}
                            </span>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach(\App\Modules\Communication\Enums\ActualiteReaction::VALIDES as $reaction)
                                <button type="button"
                                        class="actu-reaction-btn {{ $currentReaction === $reaction ? 'active' : '' }}"
                                        data-url="{{ route('actualites.reaction', $actualite->slug) }}"
                                        data-reaction="{{ $reaction }}"
                                        title="{{ \App\Modules\Communication\Enums\ActualiteReaction::LABELS[$reaction] }}"
                                        aria-label="{{ \App\Modules\Communication\Enums\ActualiteReaction::LABELS[$reaction] }}">
                                    <span class="actu-reaction-emoji">{{ \App\Modules\Communication\Enums\ActualiteReaction::EMOJIS[$reaction] }}</span>
                                    <span class="actu-reaction-count" data-count-for="{{ $reaction }}">
                                        {{ $reactionCounts->get($reaction, 0) > 0 ? $reactionCounts->get($reaction) : '' }}
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Partage --}}
                    <div class="actu-share panel p-3 mt-3">
                        <h2 class="h6 fw-bold mb-2">Partager cette actualité</h2>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button"
                                    class="btn btn-sm btn-success actu-share-btn"
                                    data-canal="whatsapp"
                                    data-url="{{ route('actualites.partager', $actualite->slug) }}"
                                    data-share-url="https://wa.me/?text={{ urlencode($actualite->titre) }}%20{{ urlencode(route('actualites.show', $actualite->slug)) }}">
                                <i class="bi bi-whatsapp me-1"></i>WhatsApp
                            </button>
                            <button type="button"
                                    class="btn btn-sm btn-primary actu-share-btn"
                                    data-canal="facebook"
                                    data-url="{{ route('actualites.partager', $actualite->slug) }}"
                                    data-share-url="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(route('actualites.show', $actualite->slug)) }}">
                                <i class="bi bi-facebook me-1"></i>Facebook
                            </button>
                            <button type="button"
                                    class="btn btn-sm btn-secondary actu-share-btn"
                                    data-canal="linkedin"
                                    data-url="{{ route('actualites.partager', $actualite->slug) }}"
                                    data-share-url="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode(route('actualites.show', $actualite->slug)) }}">
                                <i class="bi bi-linkedin me-1"></i>LinkedIn
                            </button>
                            <button type="button"
                                    class="btn btn-sm btn-outline-secondary actu-share-btn"
                                    data-canal="copier"
                                    data-url="{{ route('actualites.partager', $actualite->slug) }}"
                                    data-copy-url="{{ route('actualites.show', $actualite->slug) }}">
                                <i class="bi bi-link-45deg me-1"></i>Copier le lien
                            </button>
                        </div>
                    </div>

                </article>
            </div>

            <div class="col-lg-4">

                @if($recentes->isNotEmpty())
                    <div class="panel p-4">
                        <h2 class="h5 mb-3"><i class="bi bi-newspaper me-2"></i>Autres actualités</h2>
                        @foreach($recentes as $autre)
                            <div class="d-flex align-items-start gap-2 mb-3">
                                @if($autre->getFirstMedia('image_principale'))
                                    <img src="{{ $autre->image_url }}" alt="{{ $autre->titre }}"
                                         class="flex-shrink-0 rounded"
                                         style="width: 56px; height: 46px; object-fit: cover;">
                                @endif
                                <div>
                                    <a href="{{ route('actualites.show', $autre->slug) }}"
                                       class="text-decoration-none fw-semibold"
                                       style="color: var(--heading-color); font-size: 0.85rem;">
                                        {{ Str::limit($autre->titre, 50) }}
                                    </a>
                                    <div class="text-muted" style="font-size: 0.75rem;">
                                        <i class="bi bi-eye me-1"></i>{{ number_format($autre->nb_vues) }}
                                        <span class="mx-1">·</span>
                                        {{ $autre->published_at?->format('d/m/Y') }}
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
<link rel="stylesheet" href="{{ asset('assetActualites/css/actualites.css') }}">
@endpush

@push('meta')
<meta property="og:type" content="article">
<meta property="og:site_name" content="{{ config('app.name') }}">
<meta property="og:locale" content="fr_FR">
<meta property="og:title" content="{{ $actualite->titre }}">
<meta property="og:description" content="{{ Str::limit($actualite->resume ?? $actualite->contenu, 160) }}">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:image" content="{{ $actualite->image_original }}">
<meta property="article:published_time" content="{{ $actualite->published_at?->toIso8601String() ?? $actualite->created_at->toIso8601String() }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $actualite->titre }}">
<meta name="twitter:description" content="{{ Str::limit($actualite->resume ?? $actualite->contenu, 160) }}">
<meta name="twitter:image" content="{{ $actualite->image_original }}">
@endpush

@push('scripts')
@php
    $actualiteJsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'NewsArticle',
        'headline' => $actualite->titre,
        'description' => $actualite->resume ?? Str::limit($actualite->contenu, 200),
        'datePublished' => $actualite->published_at?->toIso8601String() ?? $actualite->created_at->toIso8601String(),
        'dateModified' => $actualite->updated_at->toIso8601String(),
        'mainEntityOfPage' => url()->current(),
        'url' => url()->current(),
        'image' => $actualite->image_original,
        'author' => [
            '@type' => 'Person',
            'name' => $actualite->auteur
                ? trim($actualite->auteur->prenom.' '.$actualite->auteur->nom)
                : \App\Support\CabinetInfo::get('nom', "K'Educ"),
        ],
        'publisher' => [
            '@type' => 'Organization',
            'name' => \App\Support\CabinetInfo::get('nom', "K'Educ"),
        ],
    ];
@endphp
@if(!empty($actualiteJsonLd['headline']))
<script type="application/ld+json">
{!! json_encode($actualiteJsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endif
<script src="{{ asset('assetActualites/js/actualites.js') }}"></script>
@endpush
