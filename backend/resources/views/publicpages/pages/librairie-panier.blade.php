@extends('publicpages.layouts.public')

@section('title')
    Mon Panier — Librairie Scolaire — K'Educ
@endsection
@section('meta_description', 'Gérez votre panier et passez votre commande sur notre librairie scolaire.')
@section('body_class', 'index-page')

@push('meta')
<meta name="robots" content="noindex, nofollow">
@endpush

@section('content')

<section class="section" style="padding: 40px 0;">
    <div class="container">

        <div class="section-title" data-aos="fade-up">
            <h1><i class="bi bi-cart me-2"></i>Mon Panier</h1>
            <p>Récapitulez vos articles et validez votre commande</p>
        </div>

        <div class="row g-4">
            {{-- COLONNE GAUCHE : Liste des articles --}}
            <div class="col-lg-8" data-aos="fade-up">
                <div class="panel p-0">
                    <div class="panel-header">
                        <h5 class="fw-bold mb-0"><i class="bi bi-bag me-2"></i>Articles</h5>
                        <button type="button" id="btn-clear-cart" class="btn btn-sm btn-outline-danger" onclick="if(!confirm('Vider tout le panier ?')){return false;} LibrairieCart.clear(); LibrairieCart.renderCart();" style="display:none;">
                            <i class="bi bi-trash me-1"></i>Vider le panier
                        </button>
                    </div>

                    <div class="p-3">
                        <div id="cart-empty" class="text-center py-5">
                            <i class="bi bi-cart display-1 text-muted"></i>
                            <p class="text-muted mt-3 fs-5">Votre panier est vide.</p>
                            <a href="{{ route('librairie.produits') }}" class="btn btn-primary btn-lg mt-2">
                                <i class="bi bi-bag me-2"></i>Voir le catalogue
                            </a>
                        </div>

                        <div id="cart-items" style="display:none;">
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Produit</th>
                                            <th class="text-center" style="width:140px;">Quantité</th>
                                            <th class="text-end">Prix unitaire</th>
                                            <th class="text-end">Sous-total</th>
                                            <th style="width:50px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="cart-body"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- COLONNE DROITE : Résumé + Checkout --}}
            <div class="col-lg-4" data-aos="fade-left" data-aos-delay="100">
                <div class="lib-cart-summary">
                    <div class="panel p-4 mb-4">
                        <h5 class="fw-bold mb-3">Résumé</h5>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Sous-total</span>
                            <span id="cart-subtotal" class="fw-bold">0 FCFA</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="fw-bold fs-5">Total</span>
                            <span id="cart-total" class="fw-bold text-primary fs-4">0 FCFA</span>
                        </div>
                        <button type="button" id="btn-checkout" class="btn btn-primary w-100 btn-lg" style="display:none;" onclick="showCheckoutForm()">
                            <i class="bi bi-credit-card me-2"></i>Passer la commande
                        </button>
                        <a href="{{ route('librairie.produits') }}" class="btn btn-outline-secondary w-100 mt-2">
                            <i class="bi bi-arrow-left me-1"></i>Continuer les achats
                        </a>
                    </div>

                    {{-- Infos livraison --}}
                    <div class="panel p-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-truck text-success fs-5"></i>
                            <span class="fw-semibold">Livraison disponible</span>
                        </div>
                        <p class="text-muted small mb-0">Nous livrons partout à Ouagadougou et dans les environs. Les frais sont calculés selon votre zone.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- SECTION CHECKOUT --}}
        <div id="checkout-section" class="mt-5" style="display:none;" data-aos="fade-up">
            <div class="panel p-4">
                <h4 class="fw-bold mb-1"><i class="bi bi-pencil-square me-2"></i>Informations de commande</h4>
                <p class="text-muted mb-4">Renseignez vos coordonnées pour finaliser votre commande.</p>

                <form action="{{ route('librairie.commander') }}" method="POST" id="checkout-form" class="lib-checkout-form">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="nom_client" class="form-label fw-semibold">Nom complet <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('nom_client') is-invalid @enderror" id="nom_client" name="nom_client"
                                value="{{ old('nom_client', auth()->check() ? trim(auth()->user()->prenom . ' ' . auth()->user()->nom) : '') }}" required>
                            @error('nom_client')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="telephone_client" class="form-label fw-semibold">Téléphone <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control @error('telephone_client') is-invalid @enderror" id="telephone_client" name="telephone_client"
                                value="{{ old('telephone_client') }}" required placeholder="+226 XX XX XX XX">
                            @error('telephone_client')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="whatsapp" class="form-label fw-semibold">WhatsApp <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control @error('whatsapp') is-invalid @enderror" id="whatsapp" name="whatsapp"
                                value="{{ old('whatsapp', auth()->user()?->telephone_whatsapp ?? '') }}" required placeholder="+226 XX XX XX XX">
                            @error('whatsapp')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="adresse_livraison" class="form-label fw-semibold">Adresse de livraison <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('adresse_livraison') is-invalid @enderror" id="adresse_livraison" name="adresse_livraison"
                                value="{{ old('adresse_livraison') }}" required placeholder="Ex : Ouaga 2000, Avenue Kwamé N'Krumah">
                            @error('adresse_livraison')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="quartier" class="form-label fw-semibold">Quartier</label>
                            <input type="text" class="form-control @error('quartier') is-invalid @enderror" id="quartier" name="quartier"
                                value="{{ old('quartier') }}" placeholder="Ex : Zone du Bois">
                            @error('quartier')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="mode_paiement" class="form-label fw-semibold">Mode de paiement</label>
                            <select class="form-select @error('mode_paiement') is-invalid @enderror" id="mode_paiement" name="mode_paiement">
                                <option value="">Choisir...</option>
                                <option value="cash" {{ old('mode_paiement') === 'cash' ? 'selected' : '' }}>Cash à la livraison</option>
                                <option value="orange_money" {{ old('mode_paiement') === 'orange_money' ? 'selected' : '' }}>Orange Money</option>
                                <option value="moov_money" {{ old('mode_paiement') === 'moov_money' ? 'selected' : '' }}>Moov Money</option>
                            </select>
                            @error('mode_paiement')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <div class="form-check form-switch mt-4">
                                <input class="form-check-input" type="checkbox" id="is_livraison" name="is_livraison" value="1"
                                    {{ old('is_livraison') ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="is_livraison">
                                    <i class="bi bi-truck me-1"></i>Besoin de livraison
                                </label>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="notes" class="form-label fw-semibold">Notes supplémentaires</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="2"
                                placeholder="Instructions spéciales, point de repère...">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <input type="hidden" name="panier" id="panier-input">

                    <div class="mt-4 d-flex flex-wrap gap-3">
                        <button type="submit" class="btn btn-primary btn-lg px-5" id="btn-submit-order">
                            <i class="bi bi-check-circle me-2"></i>Confirmer la commande
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-lg" onclick="document.getElementById('checkout-section').style.display='none'; window.scrollTo({top: 0, behavior: 'smooth'});">
                            <i class="bi bi-arrow-left me-1"></i>Revenir au panier
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</section>

@endsection

@push('scripts')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'WebPage',
    'name' => 'Mon Panier — Librairie Scolaire — K\'Educ',
    'description' => 'Gérez votre panier et passez votre commande sur notre librairie scolaire.',
    'url' => url()->current(),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
<script>
    function showCheckoutForm() {
        document.getElementById('checkout-section').style.display = 'block';
        document.getElementById('checkout-section').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function restoreFromSession() {
        var backup = sessionStorage.getItem('librairie_cart_placed');
        if (backup) {
            try {
                var items = JSON.parse(backup);
                if (Array.isArray(items) && items.length > 0) {
                    var current = LibrairieCart.get();
                    if (current.length === 0) {
                        LibrairieCart.save(items);
                    }
                }
            } catch (e) {}
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        restoreFromSession();
        LibrairieCart.renderCart();

        var btnClear = document.getElementById('btn-clear-cart');
        var cart = LibrairieCart.get();
        if (btnClear) {
            btnClear.style.display = cart.length > 0 ? '' : 'none';
        }
        if (cart.length > 0 && {{ session('errors') && session('errors')->has('panier') ? 'true' : 'false' }}) {
            showCheckoutForm();
        }
    });

    document.getElementById('checkout-form').addEventListener('submit', function(e) {
        var panier = LibrairieCart.get();
        if (panier.length === 0) {
            e.preventDefault();
            LibrairieCart.toast('Votre panier est vide.', 'error');
            return;
        }
        document.getElementById('panier-input').value = JSON.stringify(panier);
        sessionStorage.setItem('librairie_cart_placed', JSON.stringify(panier));
        var btn = document.getElementById('btn-submit-order');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Traitement en cours...';
    });
</script>
@endpush
