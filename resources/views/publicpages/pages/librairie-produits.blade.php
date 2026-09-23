@extends('publicpages.layouts.public')

@section('title')
    Tous les Produits — Librairie Scolaire — K'Educ
@endsection
@section('meta_description')
    Parcourez tous les produits de notre librairie scolaire.
@endsection
@section('body_class', 'index-page')

@section('content')

<section id="lib-produits" class="section" style="padding: 40px 0;">
    <div class="container">
        <div class="section-title" data-aos="fade-up">
            <h1><i class="bi bi-box-seam me-2"></i>Nos Produits</h1>
            <p>Retrouvez toute notre sélection de produits scolaires</p>
        </div>

        <div class="row">
            <div class="col-lg-3 mb-4" data-aos="fade-right">
                <form action="{{ route('librairie.produits') }}" method="GET" class="lib-filter-sidebar">
                    <h6 class="fw-bold mb-3"><i class="bi bi-funnel me-1"></i> Filtres</h6>

                    <div class="mb-3">
                        <label for="recherche-produit" class="form-label small fw-semibold">Recherche</label>
                        <input type="text" name="search" id="recherche-produit" class="form-control form-control-sm" placeholder="Nom du produit..." value="{{ $filters['search'] ?? '' }}">
                    </div>

                    <div class="mb-3">
                        <label for="filtre-categorie-produit" class="form-label small fw-semibold">Catégorie</label>
                        <select name="categorie_id" id="filtre-categorie-produit" class="form-select form-select-sm">
                            <option value="">Toutes les catégories</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ ($filters['categorie_id'] ?? '') == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->nom }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="filtre-tri-produit" class="form-label small fw-semibold">Tri</label>
                        <select name="sort" id="filtre-tri-produit" class="form-select form-select-sm">
                            <option value="recent" {{ ($filters['sort'] ?? '') === 'recent' ? 'selected' : '' }}>Plus récents</option>
                            <option value="prix_asc" {{ ($filters['sort'] ?? '') === 'prix_asc' ? 'selected' : '' }}>Prix croissant</option>
                            <option value="prix_desc" {{ ($filters['sort'] ?? '') === 'prix_desc' ? 'selected' : '' }}>Prix décroissant</option>
                            <option value="nom" {{ ($filters['sort'] ?? '') === 'nom' ? 'selected' : '' }}>Nom A-Z</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-search me-1"></i> Appliquer
                    </button>
                    @if(!empty($filters))
                        <a href="{{ route('librairie.produits') }}" class="btn btn-outline-secondary btn-sm w-100 mt-2">
                            <i class="bi bi-x-circle me-1"></i>Réinitialiser
                        </a>
                    @endif
                </form>
            </div>

            <div class="col-lg-9" data-aos="fade-up" data-aos-delay="100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="mb-0">
                        @if(!empty($filters['search']))
                            Résultats pour « {{ $filters['search'] }} »
                        @else
                            Tous les produits
                        @endif
                        <span class="text-muted ms-2" style="font-size: 0.85rem;">
                            ({{ $produits->total() }} résultat{{ $produits->total() > 1 ? 's' : '' }})
                        </span>
                    </h4>
                </div>

                <div class="row g-4">
                    @forelse($produits as $produit)
                        <div class="col-sm-6 col-lg-4">
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
                            <p class="text-muted mt-3">Aucun produit trouvé pour cette recherche.</p>
                            <a href="{{ route('librairie.produits') }}" class="btn btn-primary mt-2">
                                <i class="bi bi-arrow-left me-1"></i>Voir tous les produits
                            </a>
                        </div>
                    @endforelse
                </div>

                @if($produits->hasPages())
                    <div class="d-flex justify-content-center mt-5">
                        {{ $produits->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'CollectionPage',
    'name' => 'Tous les Produits — Librairie Scolaire — K\'Educ',
    'description' => 'Parcourez tous les produits de notre librairie scolaire.',
    'url' => url()->current(),
    'mainEntity' => [
        '@type' => 'ItemList',
        'itemListElement' => $produits->map(fn($p, $i) => [
            '@type' => 'ListItem',
            'position' => $i + 1,
            'name' => $p->nom,
            'url' => route('librairie.produit', $p->slug),
        ])->toArray(),
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endpush
