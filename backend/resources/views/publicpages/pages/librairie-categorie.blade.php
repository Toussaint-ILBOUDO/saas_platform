@extends('publicpages.layouts.public')

@section('title')
    {{ $categorie->nom }} — Librairie Scolaire — K'Educ
@endsection
@section('meta_description', $categorie->description ?? 'Découvrez les produits de la catégorie ' . $categorie->nom)
@section('body_class', 'index-page')

@section('content')

<section id="lib-categorie" class="section" style="padding: 40px 0;">
    <div class="container">
        <nav aria-label="breadcrumb" class="mb-4" data-aos="fade-up">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('librairie.index') }}">Accueil</a></li>
                <li class="breadcrumb-item"><a href="{{ route('librairie.categories') }}">Catégories</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $categorie->nom }}</li>
            </ol>
        </nav>

        <div class="section-title" data-aos="fade-up">
            <h1><i class="bi bi-collection me-2"></i>{{ $categorie->nom }}</h1>
            @if($categorie->description)
                <p>{{ $categorie->description }}</p>
            @endif
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4" data-aos="fade-up" data-aos-delay="100">
            <span class="text-muted">{{ $produits->total() }} produit{{ $produits->total() > 1 ? 's' : '' }}</span>
            <form action="{{ route('librairie.categorie', $categorie->slug) }}" method="GET" class="d-flex gap-2">
                <label for="tri-categorie" class="visually-hidden">Trier les produits</label>
                <select name="sort" id="tri-categorie" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                    <option value="recent" {{ request('sort') === 'recent' ? 'selected' : '' }}>Plus récents</option>
                    <option value="prix_asc" {{ request('sort') === 'prix_asc' ? 'selected' : '' }}>Prix croissant</option>
                    <option value="prix_desc" {{ request('sort') === 'prix_desc' ? 'selected' : '' }}>Prix décroissant</option>
                    <option value="nom" {{ request('sort') === 'nom' ? 'selected' : '' }}>Nom A-Z</option>
                </select>
            </form>
        </div>

        <div class="row g-4" data-aos="fade-up" data-aos-delay="200">
            @forelse($produits as $produit)
                <div class="col-sm-6 col-lg-3">
                    <div class="lib-product-card h-100">
                        <a href="{{ route('librairie.produit', $produit->slug) }}" class="text-decoration-none">
                            <img src="{{ $produit->image_url }}" alt="{{ $produit->nom }}" class="lib-produit-img card-img-top" loading="lazy">
                            <div class="card-body">
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
                    <p class="text-muted mt-3">Aucun produit dans cette catégorie.</p>
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
</section>

@endsection

@push('scripts')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'CollectionPage',
    'name' => $categorie->nom . ' — Librairie Scolaire — K\'Educ',
    'description' => $categorie->description ?? 'Découvrez les produits de la catégorie ' . $categorie->nom,
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
