{{-- ======================================================
    Page : Témoignages (classement R1/B1/R2/B2/R3/B3 + Voir plus)
    Layout : layouts/public
    Source : app/Modules/Temoignages (PublicTemoignageController)
====================================================== --}}

@extends('publicpages.layouts.public')

@section('title', 'Témoignages | K\'Educ')
@section('meta_description', 'Découvrez les témoignages des parents, enseignants et élèves de K\'Educ : leurs expériences, leurs progrès et leur satisfaction.')
@section('body_class', 'index-page')

@section('content')

<section id="temoignages" class="section" style="padding: 40px 0;">
    <div class="container">

        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Accueil</a></li>
                <li class="breadcrumb-item active">Témoignages</li>
            </ol>
        </nav>

        <header class="mb-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h1 class="h2 mb-1" style="color: var(--heading-color);">Témoignages</h1>
                <p class="text-muted mb-0">
                    Parents, enseignants et élèves partagent leur expérience chez K'Educ.
                    @if($total > 0)
                        <span class="badge bg-primary-subtle text-primary ms-1">{{ $total }} témoignage{{ $total > 1 ? 's' : '' }}</span>
                    @endif
                </p>
            </div>
            @can('create', \App\Models\Temoignage::class)
                <a href="{{ route('temoignages.create') }}" class="btn btn-primary">
                    <i class="bi bi-pen me-1"></i>Partager mon expérience
                </a>
            @else
                @guest
                    <a href="{{ route('login') }}" class="btn btn-outline-primary">
                        <i class="bi bi-pen me-1"></i>Connectez-vous pour témoigner
                    </a>
                @endguest
            @endcan
        </header>

        <div id="temoignage-list">
            @if($total > 0)
                @include('temoignages._cartes', ['temoignages' => $temoignages->items()])
            @else
                <div class="text-center text-muted py-5">
                    <i class="bi bi-chat-quote display-4"></i>
                    <p class="mt-3 mb-0 fw-semibold">Aucun témoignage pour le moment.</p>
                    <p>Soyez le premier à partager votre expérience.</p>
                </div>
            @endif
        </div>

        @if($temoignages->hasMorePages())
            <div class="text-center mt-4">
                <button type="button" class="btn btn-outline-primary temo-load-more" id="temoignage-voir-plus"
                        data-url="{{ route('temoignages.index') }}"
                        data-page="{{ $temoignages->currentPage() + 1 }}">
                    <i class="bi bi-plus-circle me-1"></i>Voir plus
                </button>
            </div>
        @endif

    </div>
</section>

@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assetTemoignages/css/temoignages.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('assetTemoignages/js/temoignages.js') }}"></script>
@endpush
