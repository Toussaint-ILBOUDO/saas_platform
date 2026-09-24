@extends('panel.layouts.app')

@section('title')
    Dashboard Librairie
@endsection

@section('content')

<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon"><i class="bi bi-shop"></i></div>
            <div>
                <h1 class="mb-0">Dashboard Librairie</h1>
                <p class="text-muted mb-0">Vue d'ensemble de la librairie scolaire</p>
            </div>
        </div>
        <div class="heading-actions">
            <a href="{{ route('admin.librairie.commandes.index') }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-receipt me-1"></i>Commandes
            </a>
            <a href="{{ route('admin.librairie.produits.index') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-box-seam me-1"></i>Produits
            </a>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="metric-card metric-primary">
                <div class="metric-icon"><i class="bi bi-box-seam"></i></div>
                <div class="metric-label">Produits</div>
                <div class="metric-value">{{ $produits }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="metric-card metric-warning">
                <div class="metric-icon"><i class="bi bi-folder2"></i></div>
                <div class="metric-label">Catégories</div>
                <div class="metric-value">{{ $categories }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="metric-card metric-success">
                <div class="metric-icon"><i class="bi bi-receipt"></i></div>
                <div class="metric-label">Commandes totales</div>
                <div class="metric-value">{{ $commandes }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="metric-card metric-danger">
                <div class="metric-icon"><i class="bi bi-cart-check"></i></div>
                <div class="metric-label">Commandes du jour</div>
                <div class="metric-value">{{ $commandesDuJour }}</div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-4">
            <div class="panel p-4 h-100">
                <h6 class="fw-bold mb-3"><i class="bi bi-currency-exchange me-2"></i>Chiffre d'affaires</h6>
                <div class="display-6 fw-bold text-primary">{{ number_format($montantTotalVentes, 0, ',', ' ') }} FCFA</div>
                <p class="text-muted mt-2 mb-0">Total des ventes (hors annulées)</p>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="panel h-100">
                <div class="panel-header">
                    <h6 class="fw-bold mb-0"><i class="bi bi-clock-history me-2"></i>Dernières commandes</h6>
                    <a href="{{ route('admin.librairie.commandes.index') }}" class="btn btn-sm btn-outline-primary">Voir tout</a>
                </div>
                <div class="p-0">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-3">Réf.</th>
                                    <th>Client</th>
                                    <th class="text-center">Articles</th>
                                    <th class="text-end">Montant</th>
                                    <th>Statut</th>
                                    <th class="text-end pe-3">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($dernieresCommandes as $commande)
                                    <tr>
                                        <td class="ps-3">
                                            <a href="{{ route('admin.librairie.commandes.show', $commande) }}" class="fw-semibold text-decoration-none">
                                                {{ $commande->reference }}
                                            </a>
                                        </td>
                                        <td>{{ $commande->nom_client }}</td>
                                        <td class="text-center">{{ $commande->total_articles ?? '-' }}</td>
                                        <td class="text-end fw-bold">{{ number_format((float) $commande->montant_total + (float) $commande->frais_livraison, 0, ',', ' ') }} FCFA</td>
                                        <td>
                                            <span class="badge {{ \App\Models\Commande::statutBadgeClass($commande->statut) }}">
                                                {{ \App\Models\Commande::statutLabel($commande->statut) }}
                                            </span>
                                        </td>
                                        <td class="text-end text-muted pe-3">{{ $commande->created_at->format('d/m H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            <i class="bi bi-inbox display-4 d-block mb-2"></i>
                                            Aucune commande pour le moment.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12">
            <div class="panel p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-graph-up me-2"></i>Produits les plus commandés</h6>
                    <a href="{{ route('admin.librairie.produits.index') }}" class="btn btn-sm btn-outline-primary">Voir tout</a>
                </div>
                @if($topProduits->isNotEmpty())
                    <div class="row g-3">
                        @foreach($topProduits as $produit)
                            <div class="col-sm-6 col-lg-3">
                                <div class="d-flex align-items-center gap-3 p-3 border rounded-3">
                                    <img src="{{ $produit->image_url }}" alt="{{ $produit->nom }}" class="lib-thumb-img" loading="lazy">
                                    <div class="min-w-0">
                                        <div class="fw-semibold text-truncate" style="max-width: 160px;">{{ $produit->nom }}</div>
                                        <div class="text-primary fw-bold">{{ number_format((float) $produit->prix, 0, ',', ' ') }} FCFA</div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted mb-0">Pas encore de données de ventes.</p>
                @endif
            </div>
        </div>
    </div>

</div>

@endsection
