{{-- ======================================================
    Page : Actualités (liste)
    Layout : layouts/public
    Source : app/Modules/Communication (PublicActualiteController)
====================================================== --}}

@extends('publicpages.layouts.public')

@section('title', 'Actualités | K\'Educ')
@section('meta_description', 'Suivez les actualités de K\'Educ : inscriptions, examens, bibliothèque numérique, librairie scolaire et toutes les nouveautés du cabinet.')
@section('body_class', 'index-page')

@section('content')

<section id="actualites" class="section" style="padding: 40px 0;">
    <div class="container">

        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Accueil</a></li>
                <li class="breadcrumb-item active">Actualités</li>
            </ol>
        </nav>

        <header class="mb-4">
            <h1 class="h2 mb-1" style="color: var(--heading-color);">Actualités</h1>
            <p class="text-muted mb-0">
                Toute l'actualité de K'Educ : inscriptions, examens, événements et nouveautés.
            </p>
        </header>

        <div class="row g-4">

            @forelse($actualites as $actualite)

                <article class="col-md-6 col-lg-4">
                    <div class="actu-card panel h-100 d-flex flex-column">

                        <a href="{{ route('actualites.show', $actualite->slug) }}" class="actu-card-img-wrap">
                            <img
                                src="{{ $actualite->image_url }}"
                                alt="{{ $actualite->titre }}"
                                class="actu-card-img"
                                loading="lazy"
                            >
                        </a>

                        <div class="p-3 d-flex flex-column flex-grow-1">

                            <time datetime="{{ $actualite->published_at?->toIso8601String() }}"
                                  class="actu-card-date">
                                <i class="bi bi-calendar3 me-1"></i>
                                {{ $actualite->published_at?->format('d/m/Y') ?? $actualite->created_at->format('d/m/Y') }}
                            </time>

                            <h2 class="h6 fw-bold mb-2">
                                <a href="{{ route('actualites.show', $actualite->slug) }}"
                                   class="text-decoration-none" style="color: var(--heading-color);">
                                    {{ $actualite->titre }}
                                </a>
                            </h2>

                            @if($actualite->resume)
                                <p class="text-muted flex-grow-1 mb-2" style="font-size: 0.9rem;">
                                    {{ Str::limit($actualite->resume, 110) }}
                                </p>
                            @endif

                            <div class="actu-card-stats text-muted">
                                <span><i class="bi bi-eye me-1"></i>{{ number_format($actualite->nb_vues) }}</span>
                                <span><i class="bi bi-emoji-smile me-1"></i>{{ number_format($actualite->nb_reactions) }}</span>
                                <span><i class="bi bi-share me-1"></i>{{ number_format($actualite->nb_partages) }}</span>
                            </div>

                        </div>

                    </div>
                </article>

            @empty

                <div class="col-12">
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-newspaper display-4"></i>
                        <p class="mt-2 mb-0">Aucune actualité publiée pour le moment.</p>
                        <p>Revenez bientôt pour suivre les nouveautés de K'Educ.</p>
                    </div>
                </div>

            @endforelse

        </div>

        @if($actualites->hasPages())
            <div class="mt-4">
                {{ $actualites->links() }}
            </div>
        @endif

    </div>
</section>

@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assetActualites/css/actualites.css') }}">
@endpush
