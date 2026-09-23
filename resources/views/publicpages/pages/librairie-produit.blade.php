@extends('publicpages.layouts.public')

@section('title')
    {{ $produit->nom }} — Librairie Scolaire — K'Educ
@endsection
@section('meta_description')
    {{ Str::limit(strip_tags($produit->description ?? $produit->nom), 160) }}
@endsection
@section('body_class', 'index-page')

@section('content')

<section id="lib-produit-detail" class="section" style="padding: 40px 0;">
    <div class="container">
        <nav aria-label="breadcrumb" class="mb-4" data-aos="fade-up">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('librairie.index') }}">Accueil</a></li>
                <li class="breadcrumb-item">
                    <a href="{{ route('librairie.categorie', $produit->categorie->slug) }}">{{ $produit->categorie->nom }}</a>
                </li>
                <li class="breadcrumb-item active">{{ Str::limit($produit->nom, 40) }}</li>
            </ol>
        </nav>

        <div class="row g-4">
            <div class="col-lg-8" data-aos="fade-up">
                <div class="panel p-4">
                    <div class="d-flex align-items-start justify-content-between mb-3">
                        <div>
                            <span class="badge bg-primary-subtle text-primary mb-2">{{ $produit->categorie->nom }}</span>
                            <h1 class="h3 mb-1" style="color: var(--heading-color);">
                                {{ $produit->nom }}
                            </h1>
                        </div>
                    </div>

                    <div class="mb-4 text-center border rounded-3 overflow-hidden bg-light">
                        <img src="{{ $produit->image_original }}" alt="{{ $produit->nom }}"
                             class="lib-produit-img-detail img-fluid">
                    </div>

                    @if($produit->description)
                        <div class="mb-4">
                            <h5>Description</h5>
                            <div class="text-muted">{!! nl2br(e($produit->description)) !!}</div>
                        </div>
                    @endif

                    <div class="d-flex flex-wrap gap-3 text-muted" style="font-size: 0.85rem;">
                        <span><i class="bi bi-tag me-1"></i>{{ $produit->categorie->nom }}</span>
                        <span><i class="bi bi-calendar me-1"></i>Ajouté le {{ $produit->created_at->format('d/m/Y') }}</span>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="panel p-4 mb-4" data-aos="fade-left" data-aos-delay="100">
                    <h5 class="mb-3"><i class="bi bi-info-circle me-2"></i>Détails</h5>
                    <div class="info-list">
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">Prix</span>
                            <strong class="text-primary fs-5">{{ number_format((float) $produit->prix, 0, ',', ' ') }} FCFA</strong>
                        </div>
                        @if((float) $produit->frais_livraison > 0)
                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <span class="text-muted">Livraison</span>
                                <strong>+ {{ number_format((float) $produit->frais_livraison, 0, ',', ' ') }} FCFA</strong>
                            </div>
                        @endif
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">Catégorie</span>
                            <strong>{{ $produit->categorie->nom }}</strong>
                        </div>
                        <div class="d-flex justify-content-between py-2">
                            <span class="text-muted">Disponibilité</span>
                            <span class="badge bg-success">En stock</span>
                        </div>
                    </div>
                </div>

                <div class="panel p-4 mb-4" data-aos="fade-left" data-aos-delay="200">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <label for="quantite" class="form-label fw-semibold mb-0">Quantité</label>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="changeQty(-1)" aria-label="Diminuer la quantité">
                                <i class="bi bi-dash"></i>
                            </button>
                            <input type="number" id="quantite" class="form-control text-center" value="1" min="1" max="20" style="width: 60px;">
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="changeQty(1)" aria-label="Augmenter la quantité">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                    </div>
                    
                    <button type="button" class="btn btn-primary w-100 btn-lg" onclick="LibrairieCart.add({{ $produit->id }}, '{{ addslashes($produit->nom) }}', {{ $produit->prix }}, '{{ $produit->image_url ?? '' }}', document.getElementById('quantite').value)">
                        <i class="bi bi-cart-plus me-2"></i>Ajouter au panier
                    </button>
                </div>

                <div class="panel p-4 mb-4" data-aos="fade-left" data-aos-delay="300">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-truck text-success"></i>
                        <span>Livraison disponible à Ouagadougou</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-shield-check text-primary"></i>
                        <span>Produit de qualité garanti</span>
                    </div>
                </div>

                @if($produitsSimilaires->count())
                    <div class="panel p-4" data-aos="fade-left" data-aos-delay="400">
                        <h5 class="mb-3"><i class="bi bi-collection me-2"></i>Produits similaires</h5>
                        @foreach($produitsSimilaires as $similaire)
                            <div class="d-flex align-items-start gap-2 mb-3">
                                <img src="{{ $similaire->image_url }}" alt="{{ $similaire->nom }}" class="lib-thumb-img">
                                <div>
                                    <a href="{{ route('librairie.produit', $similaire->slug) }}"
                                       class="text-decoration-none fw-semibold"
                                       style="color: var(--heading-color); font-size: 0.85rem;">
                                        {{ Str::limit($similaire->nom, 40) }}
                                    </a>
                                    <div class="text-primary fw-bold" style="font-size: 0.8rem;">
                                        {{ number_format((float) $similaire->prix, 0, ',', ' ') }} FCFA
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

@push('scripts')
<script>
    function changeQty(delta) {
        const input = document.getElementById('quantite');
        let val = parseInt(input.value) || 1;
        val = Math.max(1, Math.min(99, val + delta));
        input.value = val;
    }
</script>
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => $produit->nom,
    'description' => strip_tags($produit->description ?? $produit->nom),
    'image' => $produit->image_original,
    'category' => $produit->categorie->nom,
    'offers' => [
        '@type' => 'Offer',
        'price' => $produit->prix,
        'priceCurrency' => 'XOF',
        'availability' => 'https://schema.org/InStock',
        'url' => url()->current(),
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush
