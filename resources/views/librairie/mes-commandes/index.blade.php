@extends('panel.layouts.app')

@section('title')
    Mes Commandes — Librairie
@endsection

@section('content')

<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon"><i class="bi bi-cart"></i></div>
            <div>
                <h1 class="mb-0">Mes Commandes</h1>
                <p class="text-muted mb-0">Historique de vos commandes</p>
            </div>
        </div>
        <div class="heading-actions">
            <a href="{{ route('librairie.index') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-bag me-1"></i>Librairie
            </a>
        </div>
    </div>

    <div class="panel">
        <div class="p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="min-width: 90px;">Réf.</th>
                            <th class="text-center">Articles</th>
                            <th class="text-end">Montant</th>
                            <th>Statut</th>
                            <th>Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($commandes as $commande)
                            <tr>
                                <td>
                                    <span class="fw-semibold">{{ $commande->reference }}</span>
                                </td>
                                <td class="text-center">{{ $commande->total_articles ?? $commande->lignes->sum('quantite') }}</td>
                                <td class="text-end fw-bold">{{ number_format((float) $commande->montant_total + (float) $commande->frais_livraison, 0, ',', ' ') }} FCFA</td>
                                <td>
                                    <span class="badge {{ \App\Models\Commande::statutBadgeClass($commande->statut) }}">
                                        {{ \App\Models\Commande::statutLabel($commande->statut) }}
                                    </span>
                                </td>
                                <td>{{ $commande->created_at->format('d/m/Y H:i') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('librairie.mes-commandes.show', $commande) }}"
                                       class="btn btn-sm btn-light" title="Voir le détail">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="blank-state">
                                        <i class="bi bi-cart display-4 text-muted"></i>
                                        <p class="text-muted mt-2">Aucune commande pour le moment.</p>
                                        <a href="{{ route('librairie.produits') }}" class="btn btn-primary btn-sm">
                                            Voir les produits
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($commandes->hasPages())
            <div class="panel-footer">
                {{ $commandes->links() }}
            </div>
        @endif
    </div>

</div>

@endsection
