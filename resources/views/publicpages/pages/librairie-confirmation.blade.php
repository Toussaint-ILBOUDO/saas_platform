@extends('publicpages.layouts.public')

@section('title')
    Commande Confirmée — Librairie Scolaire — K'Educ
@endsection
@section('meta_description', 'Votre commande sur la librairie scolaire K\'Educ a bien été enregistrée.')
@section('body_class', 'index-page')

@push('meta')
<meta name="robots" content="noindex, nofollow">
@endpush

@section('content')

<section class="section" style="padding: 40px 0;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="panel p-5 text-center" data-aos="zoom-in">
                    <div class="mb-4">
                        <div class="rounded-circle bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                            <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
                        </div>
                    </div>

                    <h1 class="fw-bold mb-3" style="font-size: 2rem;">Commande confirmée !</h1>
                    <p class="text-muted mb-2">
                        Merci pour votre commande. Votre référence de commande est :
                    </p>
                    <div class="bg-light rounded p-3 d-inline-block mb-2">
                        <span class="fw-bold fs-5 text-primary">{{ $commande->reference }}</span>
                    </div>
                    <div class="mb-4">
                        <span class="badge {{ \App\Models\Commande::statutBadgeClass($commande->statut) }} fs-6">
                            {{ \App\Models\Commande::statutLabel($commande->statut) }}
                        </span>
                    </div>

                    <div class="panel p-4 mb-4 text-start" data-aos="fade-up" data-aos-delay="100">
                        <h6 class="fw-bold mb-3">Détails de la commande</h6>
                        <div class="row">
                            <div class="col-sm-6 mb-2">
                                <strong>Nom :</strong> {{ $commande->nom_client }}
                            </div>
                            <div class="col-sm-6 mb-2">
                                <strong>Téléphone :</strong> {{ $commande->telephone_client }}
                            </div>
                            @if($commande->whatsapp)
                            <div class="col-sm-6 mb-2">
                                <strong>WhatsApp :</strong> {{ $commande->whatsapp }}
                            </div>
                            @endif
                            <div class="col-sm-6 mb-2">
                                <strong>Adresse :</strong> {{ $commande->adresse_livraison }}
                            </div>
                            @if($commande->quartier)
                                <div class="col-sm-6 mb-2">
                                    <strong>Quartier :</strong> {{ $commande->quartier }}
                                </div>
                            @endif
                            <div class="col-sm-6 mb-2">
                                <strong>Livraison :</strong> {{ $commande->is_livraison ? 'Oui' : 'Non' }}
                            </div>
                            @if($commande->mode_paiement)
                                <div class="col-sm-6 mb-2">
                                    <strong>Paiement :</strong> {{ ucfirst(str_replace('_', ' ', $commande->mode_paiement)) }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="text-start mb-4" data-aos="fade-up" data-aos-delay="200">
                        <h6 class="fw-bold mb-3">Articles commandés</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Produit</th>
                                        <th class="text-center">Qté</th>
                                        <th class="text-end">Prix</th>
                                        <th class="text-end">Sous-total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($commande->lignes as $ligne)
                                        <tr>
                                            <td>{{ $ligne->produit?->nom ?? 'Produit supprimé' }}</td>
                                            <td class="text-center">{{ $ligne->quantite }}</td>
                                            <td class="text-end">{{ number_format((float) $ligne->prix_unitaire, 0, ',', ' ') }} FCFA</td>
                                            <td class="text-end fw-bold">{{ number_format((float) $ligne->sous_total, 0, ',', ' ') }} FCFA</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end fw-bold">Sous-total :</td>
                                        <td class="text-end fw-bold">{{ number_format((float) $commande->montant_total, 0, ',', ' ') }} FCFA</td>
                                    </tr>
                                    @if((float) $commande->frais_livraison > 0)
                                        <tr>
                                            <td colspan="3" class="text-end fw-bold">Livraison :</td>
                                            <td class="text-end fw-bold">{{ number_format((float) $commande->frais_livraison, 0, ',', ' ') }} FCFA</td>
                                        </tr>
                                    @endif
                                    <tr class="table-primary">
                                        <td colspan="3" class="text-end fw-bold fs-5">Total :</td>
                                        <td class="text-end fw-bold fs-5">{{ number_format((float) $commande->montant_total + (float) $commande->frais_livraison, 0, ',', ' ') }} FCFA</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <div class="d-flex gap-3 justify-content-center flex-wrap" data-aos="fade-up" data-aos-delay="300">
                        <a href="{{ route('librairie.pdf', ['commande' => $commande, 'token' => $commande->token]) }}" class="btn btn-success btn-lg" target="_blank">
                            <i class="bi bi-file-pdf me-2"></i>Télécharger le PDF
                        </a>
                        <a href="{{ route('librairie.index') }}" class="btn btn-primary btn-lg">
                            <i class="bi bi-house me-2"></i>Retour à l'accueil
                        </a>
                        <a href="{{ route('librairie.produits') }}" class="btn btn-outline-primary btn-lg">
                            <i class="bi bi-bag me-2"></i>Continuer les achats
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script>
    (function() {
        const placed = sessionStorage.getItem('librairie_cart_placed');
        if (placed) {
            sessionStorage.removeItem('librairie_cart_placed');
        }
        localStorage.removeItem('librairie_cart');
        if (typeof LibrairieCart !== 'undefined') {
            LibrairieCart.updateBadge();
        }
    })();
</script>
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Order',
    'orderNumber' => $commande->reference,
    'orderStatus' => 'https://schema.org/OrderProcessing',
    'priceCurrency' => 'XOF',
    'price' => (string) $commande->montant_avec_livraison,
    'acceptedOffer' => $commande->lignes->map(fn($l) => [
        '@type' => 'Offer',
        'itemOffered' => [
            '@type' => 'Product',
            'name' => $l->produit?->nom ?? 'Produit',
        ],
        'price' => (string) $l->prix_unitaire,
        'priceCurrency' => 'XOF',
        'quantity' => $l->quantite,
    ])->toArray(),
    'customer' => [
        '@type' => 'Person',
        'name' => $commande->nom_client,
        'telephone' => $commande->telephone_client,
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endpush
