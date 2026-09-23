@extends('panel.layouts.app')

@section('title', 'Factures')

@section('content')
<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon">
                <i class="bi bi-receipt"></i>
            </div>
            <div>
                <h1 class="mb-0">Factures</h1>
                <p class="text-muted mb-0">Gestion de la facturation des parents</p>
            </div>
        </div>

        <div class="heading-actions">
            <a href="{{ route('finance.factures.create') }}" class="btn btn-primary">
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
                        <i class="bi bi-receipt"></i>
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
                            {{ $factures->getCollection()->where('statut_paiement', 'en_attente')->count() }}
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
                            {{ $factures->getCollection()->where('statut_paiement', 'payee')->count() }}
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
                            {{ number_format($factures->getCollection()->sum('montant_total'), 0, ',', ' ') }} F
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
                <p class="text-muted mb-0">Filtrer par nom ou statut</p>
            </div>
        </div>
        <div class="panel-body">
            <form method="GET" action="{{ route('finance.factures.index') }}">
                <div class="row g-3">
                    <div class="col-lg-6">
                        <input type="text"
                               name="search"
                               value="{{ request('search') }}"
                               class="form-control"
                               placeholder="Nom de l'élève...">
                    </div>
                    <div class="col-lg-4">
                        <select name="statut" class="form-select">
                            <option value="">Tous les statuts</option>
                            <option value="en_attente" @selected(request('statut') === 'en_attente')>En attente</option>
                            <option value="payee" @selected(request('statut') === 'payee')>Payée</option>
                            <option value="partiel" @selected(request('statut') === 'partiel')>Partiellement payée</option>
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
                <h5 class="mb-0">Liste des factures</h5>
                <p class="text-muted mb-0">Factures générées pour les parents</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Numéro</th>
                        <th>Élève</th>
                        <th>Parent</th>
                        <th>Période</th>
                        <th>Montant</th>
                        <th>Statut</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>

                <tbody>
                @forelse($factures as $facture)
                    <tr>
                        <td>
                            <strong>{{ $facture->numero_facture }}</strong>
                        </td>

                        <td>
                            {{ $facture->contrat?->eleve?->user?->prenom }}
                            {{ $facture->contrat?->eleve?->user?->nom }}
                        </td>

                        <td>
                            {{ $facture->parent?->prenom }}
                            {{ $facture->parent?->nom }}
                        </td>

                        <td>
                            <span class="badge text-bg-secondary">
                                {{ $facture->periode?->label ?? '—' }}
                            </span>
                        </td>

                        <td>
                            <strong>
                                {{ number_format($facture->montant_total, 0, ',', ' ') }} F
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
                            @else
                                <span class="badge text-bg-danger">
                                    <i class="bi bi-hourglass"></i> En attente
                                </span>
                            @endif
                        </td>

                        <td class="text-end">
                            <div class="btn-group">
                                <a href="{{ route('finance.factures.show', $facture) }}"
                                   class="btn btn-sm btn-light"
                                   title="Voir">
                                    <i class="bi bi-eye"></i>
                                </a>

                                <a href="{{ route('finance.factures.pdf', $facture) }}"
                                   class="btn btn-sm btn-light"
                                   title="PDF"
                                   target="_blank">
                                    <i class="bi bi-file-earmark-pdf"></i>
                                </a>

                                @if($facture->estEnAttente())
                                    <a href="{{ route('finance.factures.payer', $facture) }}"
                                       class="btn btn-sm btn-light text-success"
                                       title="Marquer payé">
                                        <i class="bi bi-check2-circle"></i>
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>

                @empty
                    <tr>
                        <td colspan="7">
                            <div class="blank-panel">
                                <div class="blank-state">
                                    <i class="bi bi-receipt-cutoff display-5 text-muted"></i>
                                    <p class="mt-2 text-muted">
                                        Aucune facture trouvée.
                                    </p>
                                    <a href="{{ route('finance.factures.create') }}" class="btn btn-primary mt-2">
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
