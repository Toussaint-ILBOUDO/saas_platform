{{-- ======================================================
    Page : Détail d'un témoignage (réactions, commentaires, signalement)
    Layout : layouts/public
    Source : app/Modules/Temoignages (PublicTemoignageController)
====================================================== --}}

@extends('publicpages.layouts.public')

@section('title', ($temoignage->anonyme ? 'Témoignage anonyme' : $temoignage->auteur_display) . ' | K\'Educ')
@section('meta_description', Str::limit($temoignage->contenu, 160))
@section('body_class', 'index-page')

@section('content')

<section id="temoignage-detail" class="section" style="padding: 40px 0;">
    <div class="container">

        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Accueil</a></li>
                <li class="breadcrumb-item"><a href="{{ route('temoignages.index') }}">Témoignages</a></li>
                <li class="breadcrumb-item active">Témoignage</li>
            </ol>
        </nav>

        <div class="row g-4">

            <div class="col-lg-8">

                <article class="temo-article panel p-4">

                    <div class="temo-article-head d-flex align-items-start gap-3 flex-wrap">

                        <span class="temo-avatar temo-avatar-lg">
                            <i class="bi bi-quote"></i>
                        </span>

                        <div class="flex-grow-1">
                            <h1 class="h4 mb-1" style="color: var(--heading-color);">
                                {{ $temoignage->auteur_display }}
                            </h1>
                            <div class="text-muted small">
                                @if(! $temoignage->anonyme)
                                    <span class="badge bg-light text-dark me-2">{{ $temoignage->role_label }}</span>
                                @endif
                                <time datetime="{{ $temoignage->published_at?->toIso8601String() }}">
                                    <i class="bi bi-calendar3 me-1"></i>{{ $temoignage->published_at?->format('d/m/Y à H\hi') }}
                                </time>
                            </div>
                        </div>

                        <span class="temo-score-chip">
                            <i class="bi bi-stars me-1"></i>Score : <strong data-score>{{ $temoignage->score }}</strong>
                        </span>

                    </div>

                    <blockquote class="temo-quote">
                        <i class="bi bi-quote quote-icon-left"></i>
                        {{ $temoignage->contenu }}
                        <i class="bi bi-quote quote-icon-right"></i>
                    </blockquote>

                    {{-- Réactions --}}
                    <div class="temo-reactions mt-4" id="temo-reactions"
                         data-url="{{ route('temoignages.reaction', $temoignage->slug) }}">

                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h2 class="h6 fw-bold mb-0">Votre réaction</h2>
                            <span class="text-muted small" id="temo-total-reactions">
                                {{ $temoignage->nb_reactions }} réaction{{ $temoignage->nb_reactions > 1 ? 's' : '' }}
                            </span>
                        </div>

                        <div class="temo-reactions-row">
                            @foreach(\App\Modules\Temoignages\Enums\TemoignageReactionType::VALIDES as $reaction)
                                <button type="button"
                                        class="temo-reaction-btn {{ $currentReaction === $reaction ? 'is-active' : '' }}"
                                        data-reaction="{{ $reaction }}"
                                        title="{{ \App\Modules\Temoignages\Enums\TemoignageReactionType::LABELS[$reaction] }}"
                                        aria-label="{{ \App\Modules\Temoignages\Enums\TemoignageReactionType::LABELS[$reaction] }}">
                                    <span class="temo-reaction-emoji">{{ \App\Modules\Temoignages\Enums\TemoignageReactionType::EMOJIS[$reaction] }}</span>
                                    <span class="temo-reaction-count" data-count-for="{{ $reaction }}">
                                        {{ $reactionCounts->get($reaction, 0) > 0 ? $reactionCounts->get($reaction) : '' }}
                                    </span>
                                </button>
                            @endforeach
                        </div>

                        <p class="temo-reactions-hint text-muted small mb-0 mt-2">
                            Les réactions ❤️ (3 pts), 👏 (2 pts) et 👍 (1 pt) déterminent le classement « Meilleurs témoignages ».
                        </p>

                    </div>

                    {{-- Commentaires --}}
                    <section class="temo-comments mt-5">

                        <h2 class="h6 fw-bold mb-3">
                            <i class="bi bi-chat-dots me-2"></i>Commentaires ({{ $temoignage->nb_commentaires }})
                        </h2>

                        @auth
                            <form method="POST" action="{{ route('temoignages.commentaire', $temoignage->slug) }}" class="temo-comment-form mb-4">
                                @csrf
                                <label for="commentaire-temoignage" class="visually-hidden">Votre commentaire</label>
                                <textarea name="contenu" id="commentaire-temoignage" rows="3" class="form-control mb-2"
                                          placeholder="Partagez votre avis sur ce témoignage..." required maxlength="2000">{{ old('contenu') }}</textarea>
                                @error('contenu')
                                    <p class="text-danger small mb-2">{{ $message }}</p>
                                @enderror
                                <button type="submit" class="btn btn-sm btn-primary">
                                    <i class="bi bi-send me-1"></i>Commenter
                                </button>
                            </form>
                        @else
                            <p class="text-muted small mb-4">
                                <a href="{{ route('login') }}">Connectez-vous</a> pour commenter ce témoignage.
                            </p>
                        @endauth

                        <div class="temo-comments-list">
                            @forelse($commentaires as $commentaire)
                                <div class="temo-comment">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="temo-comment-avatar">
                                            <i class="bi bi-person"></i>
                                        </span>
                                        <strong class="small">{{ $commentaire->user?->prenom }} {{ $commentaire->user?->nom }}</strong>
                                        <span class="text-muted small">·</span>
                                        <time class="text-muted small">{{ $commentaire->created_at->diffForHumans() }}</time>
                                    </div>
                                    <p class="mb-0 mt-1">{!! nl2br(e($commentaire->contenu)) !!}</p>
                                </div>
                            @empty
                                <p class="text-muted small">Aucun commentaire pour le moment.</p>
                            @endforelse
                        </div>

                    </section>

                    {{-- Signalement --}}
                    <div class="mt-4">
                        @auth
                            <button type="button" class="btn btn-sm btn-link text-muted p-0" data-bs-toggle="modal" data-bs-target="#temo-signalement-modal">
                                <i class="bi bi-flag me-1"></i>Signaler ce témoignage
                            </button>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-sm btn-link text-muted p-0">
                                <i class="bi bi-flag me-1"></i>Signaler ce témoignage
                            </a>
                        @endauth
                    </div>

                </article>

            </div>

            <div class="col-lg-4">

                @if($autres->isNotEmpty())
                    <div class="panel p-4">
                        <h2 class="h5 mb-3"><i class="bi bi-chat-quote me-2"></i>Autres témoignages</h2>
                        @foreach($autres as $autre)
                            <div class="d-flex align-items-start gap-2 mb-3">
                                <span class="temo-avatar temo-avatar-sm flex-shrink-0">
                                    <i class="bi bi-quote"></i>
                                </span>
                                <div>
                                    <a href="{{ route('temoignages.show', $autre->slug) }}"
                                       class="text-decoration-none fw-semibold"
                                       style="color: var(--heading-color); font-size: 0.85rem;">
                                        {{ Str::limit($autre->contenu, 70) }}
                                    </a>
                                    <div class="text-muted" style="font-size: 0.75rem;">
                                        <i class="bi bi-stars me-1"></i>{{ $autre->score }}
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

{{-- Modal signalement --}}
@auth
<div class="modal fade" id="temo-signalement-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('temoignages.signalement', $temoignage->slug) }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Signaler ce témoignage</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="motif" class="form-label">Motif</label>
                    <select name="motif" id="motif" class="form-select" required>
                        <option value="">Choisir un motif...</option>
                        <option value="contenu_inapproprie">Contenu inapproprié</option>
                        <option value="information_inexacte">Information inexacte</option>
                        <option value="abus_publicitaire">Abus publicitaire</option>
                        <option value="impersonation">Usurpation d'identité</option>
                        <option value="autre">Autre</option>
                    </select>
                </div>
                <div class="mb-0">
                    <label for="description" class="form-label">Précisions (optionnel)</label>
                    <textarea name="description" id="description" rows="3" class="form-control" maxlength="2000"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-sm btn-danger">
                    <i class="bi bi-flag me-1"></i>Envoyer le signalement
                </button>
            </div>
        </form>
    </div>
</div>
@endauth

@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assetTemoignages/css/temoignages.css') }}">
@endpush

@push('meta')
<meta property="og:type" content="article">
<meta property="og:site_name" content="{{ config('app.name') }}">
<meta property="og:locale" content="fr_FR">
<meta property="og:title" content="Témoignage | {{ config('app.name') }}">
<meta property="og:description" content="{{ Str::limit($temoignage->contenu, 160) }}">
<meta property="og:url" content="{{ url()->current() }}">
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="Témoignage | {{ config('app.name') }}">
<meta name="twitter:description" content="{{ Str::limit($temoignage->contenu, 160) }}">
@endpush

@push('scripts')
@php
    $temoignageJsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'Review',
        'itemReviewed' => [
            '@type' => 'Organization',
            'name' => config('keduc.cabinet.nom', 'K\'Educ'),
        ],
        'reviewBody' => $temoignage->contenu,
        'datePublished' => $temoignage->published_at?->toIso8601String(),
        'url' => url()->current(),
        'author' => [
            '@type' => 'Person',
            'name' => $temoignage->anonyme ? 'Témoignage anonyme' : $temoignage->auteur_display,
        ],
    ];
@endphp
<script type="application/ld+json">
{!! json_encode($temoignageJsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
<script src="{{ asset('assetTemoignages/js/temoignages.js') }}"></script>
@endpush
