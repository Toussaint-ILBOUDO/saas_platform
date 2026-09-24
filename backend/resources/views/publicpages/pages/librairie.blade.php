@extends('publicpages.layouts.public')

@section('title')
    Librairie Scolaire — K'Educ
@endsection
@section('meta_description')
    Découvrez notre librairie scolaire : manuels, fournitures et articles éducatifs pour tous les niveaux au Burkina Faso.
@endsection
@section('body_class', 'index-page')

@section('content')

<section id="lib-hero" class="lib-hero section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8" data-aos="fade-up">
                <h1 style="color: #fff; font-weight: 700; margin-bottom: 16px;">
                    <i class="bi bi-shop me-2"></i>Librairie Scolaire
                </h1>
                <p class="lead" style="color: rgba(255,255,255,0.85); margin-bottom: 32px;">
                    Manuels, fournitures et tout le nécessaire pour la réussite scolaire de vos enfants.
                </p>

                <form action="{{ route('librairie.recherche') }}" method="GET" class="lib-search-bar">
                    <div class="input-group input-group-lg shadow-lg" style="border-radius: 12px; overflow: hidden;">
                        <span class="input-group-text bg-white border-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" name="q" class="form-control border-0 py-3"
                               placeholder="Rechercher un produit..."
                               value="{{ request('q') }}">
                        <button type="submit" class="btn btn-light px-4 px-lg-5 fw-semibold">
                            Rechercher
                        </button>
                    </div>
                </form>

                <div class="d-flex gap-3 mt-4 flex-wrap">
                    @foreach($categories->take(4) as $categorie)
                        <a href="{{ route('librairie.categorie', $categorie->slug) }}"
                           class="badge bg-light text-dark px-3 py-2 text-decoration-none" style="border-radius: 20px; font-size: 0.85rem;">
                            {{ $categorie->nom }}
                        </a>
                    @endforeach
                </div>
            </div>
            <div class="col-lg-4 text-center mt-4 mt-lg-0" data-aos="fade-left" data-aos-delay="200">
                <div class="lib-hero-stats">
                    <div class="lib-stat-card">
                        <div class="lib-stat-icon"><i class="bi bi-box-seam"></i></div>
                        <div class="lib-stat-number">{{ number_format($stats['total_produits']) }}</div>
                        <div class="lib-stat-label">Produits</div>
                    </div>
                    <div class="lib-stat-card">
                        <div class="lib-stat-icon"><i class="bi bi-collection"></i></div>
                        <div class="lib-stat-number">{{ number_format($stats['total_categories']) }}</div>
                        <div class="lib-stat-label">Catégories</div>
                    </div>
                    <div class="lib-stat-card">
                        <div class="lib-stat-icon"><i class="bi bi-truck"></i></div>
                        <div class="lib-stat-number"><i class="bi bi-check-lg"></i></div>
                        <div class="lib-stat-label">Livraison</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="lib-categories" class="section" style="padding: 40px 0;">
    <div class="container">
        <div class="section-title" data-aos="fade-up">
            <h2><i class="bi bi-collection me-2"></i>Nos Catégories</h2>
            <p>Parcourez notre sélection par rubrique</p>
        </div>
        <div class="row g-4" data-aos="fade-up" data-aos-delay="100">
            @forelse($categories as $categorie)
                <div class="col-sm-6 col-lg-4">
                    <a href="{{ route('librairie.categorie', $categorie->slug) }}" class="text-decoration-none">
                        <div class="panel p-4 h-100" style="transition: all 0.3s ease; cursor: pointer;">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px; flex-shrink: 0;">
                                    <i class="bi bi-folder2 text-primary fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-1" style="color: var(--heading-color);">{{ $categorie->nom }}</h6>
                                    <span class="text-muted" style="font-size: 0.85rem;">
                                        {{ $categorie->produits_actifs_count }} produit{{ $categorie->produits_actifs_count > 1 ? 's' : '' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            @empty
                <div class="col-12 text-center py-4">
                    <p class="text-muted">Aucune catégorie disponible pour le moment.</p>
                </div>
            @endforelse
        </div>
    </div>
</section>

<section id="lib-produits" class="section light-background" style="padding: 40px 0;">
    <div class="container">
        <div class="section-title" data-aos="fade-up">
            <h2><i class="bi bi-box-seam me-2"></i>Derniers Produits</h2>
            <p>Les derniers articles ajoutés à notre catalogue</p>
        </div>
        <div class="row g-4" data-aos="fade-up" data-aos-delay="100">
            @forelse($produits as $produit)
                <div class="col-sm-6 col-lg-3">
                    <div class="lib-product-card h-100">
                        <a href="{{ route('librairie.produit', $produit->slug) }}" class="text-decoration-none">
                            <img src="{{ $produit->image_url }}" alt="{{ $produit->nom }}" class="lib-produit-img card-img-top" loading="lazy">
                            <div class="card-body">
                                <span class="badge bg-primary-subtle text-primary mb-2" style="font-size: 0.75rem;">{{ $produit->categorie?->nom }}</span>
                                <h6 class="fw-bold mb-2" style="color: var(--heading-color);">{{ Str::limit($produit->nom, 40) }}</h6>
                                <div class="product-price">
                                    <p class="fw-bold lib-price mb-0">{{ number_format((float) $produit->prix, 0, ',', ' ') }} FCFA</p>
                                </div>
                            </div>
                        </a>
                        <button type="button" class="lib-quick-add" onclick="event.stopPropagation(); LibrairieCart.add({{ $produit->id }}, '{{ addslashes($produit->nom) }}', {{ $produit->prix }}, '{{ $produit->image_url ?? '' }}');" title="Ajouter au panier">
                            <i class="bi bi-cart-plus"></i>
                        </button>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center py-5">
                    <i class="bi bi-box-seam display-1 text-muted"></i>
                    <p class="text-muted mt-3">Aucun produit disponible pour le moment.</p>
                    <a href="{{ route('librairie.index') }}" class="btn btn-primary mt-2">
                        <i class="bi bi-arrow-left me-1"></i>Retour à l'accueil
                    </a>
                </div>
            @endforelse
        </div>

        @if($produits->hasPages())
            <div class="d-flex justify-content-center mt-5" data-aos="fade-up">
                {{ $produits->links() }}
            </div>
        @endif
    </div>
</section>

<section class="section" style="padding: 40px 0;">
    <div class="container">
        <div class="panel p-4 text-center" data-aos="fade-up">
            <div class="row align-items-center">
                <div class="col-lg-8 text-lg-start">
                    <h4 class="fw-bold mb-1" style="color: var(--heading-color);">
                        <i class="bi bi-truck me-2"></i>Livraison disponible
                    </h4>
                    <p class="text-muted mb-0">Nous livrons partout à Ouagadougou et dans les environs.</p>
                </div>
                <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                    <a href="{{ route('librairie.produits') }}" class="btn btn-primary">
                        <i class="bi bi-bag me-2"></i>Voir le catalogue
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Store',
    'name' => "Librairie Scolaire — K'Educ",
    'description' => 'Manuels, fournitures et tout le nécessaire pour la réussite scolaire au Burkina Faso.',
    'url' => url('/librairie'),
    'address' => [
        '@type' => 'PostalAddress',
        'addressLocality' => 'Ouagadougou',
        'addressCountry' => 'BF',
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endpush

