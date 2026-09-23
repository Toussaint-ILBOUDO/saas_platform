@extends('panel.layouts.app')

@section('title', 'Mes factures')

@section('content')
<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon">
                <i class="bi bi-receipt"></i>
            </div>
            <div>
                <h1 class="mb-0">Mes factures</h1>
                <p class="text-muted mb-0">Vos factures de cours chez K'Educ</p>
            </div>
        </div>

        <div class="heading-actions">
            <a href="{{ route('mes-factures.index') }}"
               class="btn btn-light @if(request('statut') === null) active @endif">
                Toutes
            </a>
            <a href="{{ route('mes-factures.index', ['statut' => 'payee']) }}"
               class="btn btn-light @if(request('statut') === 'payee') active @endif">
                Payées
            </a>
            <a href="{{ route('mes-factures.index', ['statut' => 'en_attente']) }}"
               class="btn btn-light @if(request('statut') === 'en_attente') active @endif">
                En attente
            </a>
        </div>
    </div>

    {{-- KPI --}}
    @php
        $collection = $factures->getCollection();
        $total = $collection->sum('montant_total');
        $payees = $collection->where('statut_paiement', 'payee')->count();
        $attente = $collection->where('statut_paiement', 'en_attente')->count();
    @endphp

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
                        <div class="metric-value">{{ $attente }}</div>
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
                        <div class="metric-value">{{ $payees }}</div>
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
                            {{ number_format($total, 0, ',', ' ') }} F
                        </div>
                    </div>
                    <div class="metric-icon">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- TABLEAU --}}
    <div class="panel">
        <div class="panel-header">
            <div>
                <h5 class="mb-0">Liste des factures</h5>
                <p class="text-muted mb-0">Détail de vos factures de cours</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Numéro</th>
                        <th>Élève</th>
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
                            </div>
                        </td>
                    </tr>

                @empty
                    <tr>
                        <td colspan="6">
                            <div class="blank-panel">
                                <div class="blank-state">
                                    <i class="bi bi-receipt-cutoff display-5 text-muted"></i>
                                    <p class="mt-2 text-muted">
                                        Aucune facture trouvée.
                                    </p>
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