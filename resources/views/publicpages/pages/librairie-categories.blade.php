@extends('publicpages.layouts.public')

@section('title')
    Nos Catégories — Librairie Scolaire — K'Educ
@endsection
@section('meta_description', 'Découvrez toutes les catégories de notre librairie scolaire.')
@section('body_class', 'index-page')

@section('content')

<section id="lib-categories" class="section" style="padding: 40px 0;">
    <div class="container">
        <div class="section-title" data-aos="fade-up">
            <h1><i class="bi bi-collection me-2"></i>Nos Catégories</h1>
            <p>Parcourez notre sélection de catégories</p>
        </div>

        <div class="row g-4" data-aos="fade-up" data-aos-delay="100">
            @forelse($categories as $categorie)
                <div class="col-sm-6 col-lg-4">
                    <a href="{{ route('librairie.categorie', $categorie->slug) }}" class="text-decoration-none">
                        <div class="panel p-4 h-100" style="transition: all 0.3s ease; cursor: pointer;">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center" style="width: 64px; height: 64px; flex-shrink: 0;">
                                    <i class="bi bi-folder2 text-primary fs-3"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-1" style="color: var(--heading-color);">{{ $categorie->nom }}</h5>
                                    <span class="text-muted">{{ $categorie->produits_actifs_count }} produit{{ $categorie->produits_actifs_count > 1 ? 's' : '' }}</span>
                                </div>
                            </div>
                            @if($categorie->description)
                                <p class="text-muted mb-0">{{ Str::limit($categorie->description, 120) }}</p>
                            @endif
                        </div>
                    </a>
                </div>
            @empty
                <div class="col-12 text-center py-5">
                    <i class="bi bi-folder2 display-1 text-muted"></i>
                    <p class="text-muted mt-3">Aucune catégorie disponible.</p>
                </div>
            @endforelse
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'CollectionPage',
    'name' => 'Nos Catégories — Librairie Scolaire — K\'Educ',
    'description' => 'Découvrez toutes les catégories de notre librairie scolaire.',
    'url' => url()->current(),
    'mainEntity' => [
        '@type' => 'ItemList',
        'itemListElement' => $categories->map(fn($cat, $i) => [
            '@type' => 'ListItem',
            'position' => $i + 1,
            'name' => $cat->nom,
            'description' => $cat->description ?? '',
            'url' => route('librairie.categorie', $cat->slug),
        ])->toArray(),
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endpush
