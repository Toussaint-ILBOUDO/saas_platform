@extends('panel.layouts.app')

@section('title')
    Commandes — Librairie
@endsection

@section('content')

<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon"><i class="bi bi-receipt"></i></div>
            <div>
                <h1 class="mb-0">Commandes</h1>
                <p class="text-muted mb-0">Gestion des commandes de la librairie</p>
            </div>
        </div>
        <div class="heading-actions">
            <a href="{{ route('admin.librairie.dashboard') }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-speedometer2 me-1"></i>Dashboard
            </a>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header">
            <form action="{{ route('admin.librairie.commandes.index') }}" method="GET" class="d-flex gap-2 flex-wrap">
                <input type="text" name="search" class="form-control table-search"
                       placeholder="Rechercher par nom, téléphone..." value="{{ $filters['search'] ?? '' }}">
                <select name="statut" class="form-select" style="max-width: 180px;" onchange="this.form.submit()">
                    <option value="">Tous les statuts</option>
                    <option value="en_attente" {{ ($filters['statut'] ?? '') === 'en_attente' ? 'selected' : '' }}>En attente</option>
                    <option value="confirmee" {{ ($filters['statut'] ?? '') === 'confirmee' ? 'selected' : '' }}>Confirmée</option>
                    <option value="en_preparation" {{ ($filters['statut'] ?? '') === 'en_preparation' ? 'selected' : '' }}>En préparation</option>
                    <option value="livree" {{ ($filters['statut'] ?? '') === 'livree' ? 'selected' : '' }}>Livrée</option>
                    <option value="annulee" {{ ($filters['statut'] ?? '') === 'annulee' ? 'selected' : '' }}>Annulée</option>
                </select>
                <button type="submit" class="btn btn-sm btn-light">
                    <i class="bi bi-search"></i>
                </button>
            </form>
        </div>
        <div class="p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="min-width: 90px;">Réf.</th>
                            <th>Client</th>
                            <th>Téléphone</th>
                            <th>WhatsApp</th>
                            <th class="text-center">Articles</th>
                            <th class="text-end">Montant</th>
                            <th class="text-center">Livraison</th>
                            <th>Statut</th>
                            <th>Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($commandes as $commande)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.librairie.commandes.show', $commande) }}"
                                       class="fw-semibold text-decoration-none text-primary">
                                        {{ $commande->reference }}
                                    </a>
                                </td>
                                <td>
                                    <span class="fw-semibold">{{ $commande->nom_client }}</span>
                                    @if($commande->user)
                                        <div class="text-muted" style="font-size: 0.8rem;">
                                            <i class="bi bi-person-check"></i> Compte lié
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <a href="tel:{{ $commande->telephone_client }}" class="text-decoration-none">
                                        <i class="bi bi-telephone me-1"></i>{{ $commande->telephone_client }}
                                    </a>
                                </td>
                                <td>
                                    @if($commande->whatsapp)
                                        <a href="https://wa.me/{{ preg_replace('/[^0-9+]/', '', $commande->whatsapp) }}" target="_blank" rel="noopener" class="btn btn-sm btn-success" title="Contacter sur WhatsApp">
                                            <i class="bi bi-whatsapp"></i>
                                        </a>
                                        <span class="text-muted small">{{ $commande->whatsapp }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-center">{{ $commande->total_articles ?? $commande->lignes->sum('quantite') }}</td>
                                <td class="text-end fw-bold">{{ number_format((float) $commande->montant_total, 0, ',', ' ') }} FCFA</td>
                                <td class="text-center">
                                    @if($commande->is_livraison)
                                        <span class="badge bg-info-subtle text-info"><i class="bi bi-truck"></i></span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ \App\Models\Commande::statutBadgeClass($commande->statut) }}">
                                        {{ \App\Models\Commande::statutLabel($commande->statut) }}
                                    </span>
                                </td>
                                <td>{{ $commande->created_at->format('d/m/Y H:i') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.librairie.commandes.show', $commande) }}"
                                       class="btn btn-sm btn-light" title="Voir">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-5">
                                    <div class="blank-state">
                                        <i class="bi bi-receipt display-4 text-muted"></i>
                                        <p class="text-muted mt-2">Aucune commande trouvée.</p>
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
