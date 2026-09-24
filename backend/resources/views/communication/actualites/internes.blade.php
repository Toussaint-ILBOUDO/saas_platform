@extends('panel.layouts.app')

@section('title', 'Actualités internes')

@section('content')

<div class="container-fluid px-3 px-lg-4 py-4">

    <div class="page-heading">

        <div class="page-heading-copy">

            <div class="page-icon">
                <i class="bi bi-megaphone"></i>
            </div>

            <div>

                <h1 class="mb-0">
                    Actualités internes
                </h1>

                <p class="text-muted mb-0">
                    Les dernières nouvelles destinées à la communauté de l'école.
                </p>

            </div>

        </div>

        <div class="page-heading-actions">
            <a href="{{ route('actualites.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-globe me-1"></i>
                Actualités publiques
            </a>
        </div>

    </div>

    @if($actualites->isEmpty())

        <div class="panel">
            <div class="notif-empty">
                <div class="notif-empty-icon">
                    <i class="bi bi-megaphone"></i>
                </div>
                <h5 class="notif-empty-title">
                    Aucune actualité pour le moment
                </h5>
                <p class="notif-empty-text text-muted mb-0">
                    Les nouvelles vous concernant apparaîtront ici dès leur publication.
                </p>
            </div>
        </div>

    @else

        <div class="row g-3">

            @foreach($actualites as $actualite)

                <div class="col-md-6 col-lg-4">

                    <div class="card h-100 shadow-sm">

                        <a href="{{ route('actualites.internes.show', $actualite->slug) }}" class="d-block">
                            <img
                                src="{{ $actualite->image_url }}"
                                alt="{{ $actualite->titre }}"
                                class="card-img-top"
                                style="height: 180px; width: 100%; object-fit: cover;"
                                loading="lazy"
                            >
                        </a>

                        <div class="card-body d-flex flex-column">

                            <div class="d-flex align-items-center gap-2 mb-2">

                                <span class="text-muted small">
                                    <i class="bi bi-calendar3 me-1"></i>
                                    {{ $actualite->published_at?->format('d/m/Y') ?? $actualite->created_at->format('d/m/Y') }}
                                </span>

                            </div>

                            <div class="mb-2">

                                @if($actualite->est_globale)

                                    <span class="badge text-bg-secondary">
                                        <i class="bi bi-globe me-1"></i>
                                        Toute la communauté
                                    </span>

                                @else

                                    @foreach($actualite->destinataires_labels as $badge)

                                        <span class="badge text-bg-light border mb-1">
                                            <i class="bi {{ $badge['icon'] }} me-1"></i>
                                            {{ $badge['label'] }}
                                        </span>

                                    @endforeach

                                @endif

                            </div>

                            <h5 class="card-title mb-2">
                                <a href="{{ route('actualites.internes.show', $actualite->slug) }}" class="text-decoration-none">
                                    {{ $actualite->titre }}
                                </a>
                            </h5>

                            <p class="card-text text-muted mb-3 flex-grow-1">
                                {{ Str::limit($actualite->resume ?? $actualite->contenu, 120) }}
                            </p>

                        </div>

                        <div class="card-footer bg-transparent d-flex align-items-center justify-content-between">

                            <div class="small text-muted">
                                <span title="Vues" class="me-2">
                                    <i class="bi bi-eye me-1"></i>{{ number_format($actualite->nb_vues) }}
                                </span>
                                <span title="Réactions" class="me-2">
                                    <i class="bi bi-emoji-smile me-1"></i>{{ number_format($actualite->nb_reactions) }}
                                </span>
                                <span title="Partages">
                                    <i class="bi bi-share me-1"></i>{{ number_format($actualite->nb_partages) }}
                                </span>
                            </div>

                            <a
                                href="{{ route('actualites.internes.show', $actualite->slug) }}"
                                class="btn btn-sm btn-primary"
                            >
                                Lire la suite
                            </a>

                        </div>

                    </div>

                </div>

            @endforeach

        </div>

        @if($actualites->hasPages())

            <div class="mt-4">
                {{ $actualites->links() }}
            </div>

        @endif

    @endif

</div>

@endsection