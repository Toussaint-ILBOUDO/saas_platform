@extends('panel.layouts.app')

@section('title', 'Facturation Cabinet')

@section('content')
<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon">
                <i class="bi bi-briefcase"></i>
            </div>
            <div>
                <h1 class="mb-0">Facturation Cabinet</h1>
                <p class="text-muted mb-0">Commissions et facturation du cabinet</p>
            </div>
        </div>

        <div class="heading-actions">
            <a href="{{ route('finance.facture-cabinet.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i>
                Nouvelle facture
            </a>
        </div>
    </div>


    {{-- KPI --}}
    <div class="row g-3 mb-4">

        <div class="col-lg-3 col-md-6">
            <div class="metric-card metric-primary">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="metric-label">Total factures</div>
                        <div class="metric-value">{{ $factures->total() }}</div>
                    </div>
                    <div class="metric-icon">
                        <i class="bi bi-briefcase"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="metric-card metric-warning">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="metric-label">En attente</div>
                        <div class="metric-value">
                            {{ $factures->getCollection()->where('statut', 'en_attente')->count() }}
                        </div>
                    </div>
                    <div class="metric-icon">
                        <i class="bi bi-clock"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="metric-card metric-success">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="metric-label">Payées</div>
                        <div class="metric-value">
                            {{ $factures->getCollection()->where('statut', 'payee')->count() }}
                        </div>
                    </div>
                    <div class="metric-icon">
                        <i class="bi bi-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="metric-card metric-danger">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="metric-label">Montant total</div>
                        <div class="metric-value">
                            {{ number_format($factures->getCollection()->sum('montant_total_du'), 0, ',', ' ') }} F
                        </div>
                    </div>
                    <div class="metric-icon">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                </div>
            </div>
        </div>

    </div>


    {{-- FILTRES --}}
    <div class="panel mb-4">
        <div class="panel-header">
            <div>
                <h5 class="mb-0">Recherche</h5>
                <p class="text-muted mb-0">Filtrer par statut</p>
            </div>
        </div>
        <div class="panel-body">
            <form method="GET" action="{{ route('finance.facture-cabinet.index') }}">
                <div class="row g-3">
                    <div class="col-lg-6">
                        <input type="text"
                               name="search"
                               value="{{ request('search') }}"
                               class="form-control"
                               placeholder="Rechercher...">
                    </div>
                    <div class="col-lg-4">
                        <select name="statut" class="form-select">
                            <option value="">Tous les statuts</option>
                            <option value="en_attente" @selected(request('statut') === 'en_attente')>En attente</option>
                            <option value="partiel" @selected(request('statut') === 'partiel')>Partiellement payée</option>
                            <option value="payee" @selected(request('statut') === 'payee')>Payée</option>
                            <option value="annulee" @selected(request('statut') === 'annulee')>Annulée</option>
                        </select>
                    </div>
                    <div class="col-lg-2">
                        <button class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Filtrer
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>


    {{-- TABLEAU --}}
    <div class="panel">
        <div class="panel-header">
            <div>
                <h5 class="mb-0">Liste des factures cabinet</h5>
                <p class="text-muted mb-0">Factures de commissions du cabinet</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Période</th>
                        <th>Date facture</th>
                        <th class="text-end">Cours</th>
                        <th class="text-end">Inscriptions</th>
                        <th class="text-end">Ventes</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Payé</th>
                        <th>Statut</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>

                <tbody>
                @forelse($factures as $facture)
                    <tr>
                        <td>
                            <strong>
                                {{ \Carbon\Carbon::parse($facture->periode_debut)->format('d/m/Y') }}
                                —
                                {{ \Carbon\Carbon::parse($facture->periode_fin)->format('d/m/Y') }}
                            </strong>
                        </td>

                        <td>
                            {{ \Carbon\Carbon::parse($facture->date_facture)->format('d/m/Y') }}
                        </td>

                        <td class="text-end">
                            {{ number_format($facture->total_cours, 0, ',', ' ') }}
                        </td>

                        <td class="text-end">
                            {{ number_format($facture->total_inscriptions, 0, ',', ' ') }}
                        </td>

                        <td class="text-end">
                            {{ number_format($facture->total_ventes, 0, ',', ' ') }}
                        </td>

                        <td class="text-end">
                            <strong>
                                {{ number_format($facture->montant_total_du, 0, ',', ' ') }} F
                            </strong>
                        </td>

                        <td class="text-end">
                            <strong class="text-success">
                                {{ number_format($facture->montant_paye, 0, ',', ' ') }} F
                            </strong>
                        </td>

                        <td>
                            @if($facture->estPayee())
                                <span class="badge text-bg-success">
                                    <i class="bi bi-check-circle"></i> Payée
                                </span>
                            @elseif($facture->estPartiellementPayee())
                                <span class="badge text-bg-warning">
                                    <i class="bi bi-clock"></i> Partiel
                                </span>
                            @elseif($facture->estAnnulee())
                                <span class="badge text-bg-secondary">
                                    <i class="bi bi-x-circle"></i> Annulée
                                </span>
                            @else
                                <span class="badge text-bg-danger">
                                    <i class="bi bi-hourglass"></i> En attente
                                </span>
                            @endif
                        </td>

                        <td class="text-end">
                            <div class="btn-group">
                                <a href="{{ route('finance.facture-cabinet.show', $facture) }}"
                                   class="btn btn-sm btn-light"
                                   title="Voir">
                                    <i class="bi bi-eye"></i>
                                </a>

                                <a href="{{ route('finance.facture-cabinet.pdf', $facture) }}"
                                   class="btn btn-sm btn-light"
                                   title="PDF"
                                   target="_blank">
                                    <i class="bi bi-file-earmark-pdf"></i>
                                </a>

                                @if($facture->estEnAttente() || $facture->estPartiellementPayee())
                                    <a href="{{ route('finance.facture-cabinet.payer', $facture) }}"
                                       class="btn btn-sm btn-light text-success"
                                       title="Payer">
                                        <i class="bi bi-check2-circle"></i>
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>

                @empty
                    <tr>
                        <td colspan="9">
                            <div class="blank-panel">
                                <div class="blank-state">
                                    <i class="bi bi-briefcase display-5 text-muted"></i>
                                    <p class="mt-2 text-muted">
                                        Aucune facture cabinet trouvée.
                                    </p>
                                    <a href="{{ route('finance.facture-cabinet.create') }}" class="btn btn-primary mt-2">
                                        <i class="bi bi-plus-lg"></i> Créer une facture
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $factures->links() }}
    </div>

</div>
@endsection
