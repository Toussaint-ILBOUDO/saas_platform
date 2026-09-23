@extends('panel.layouts.app')

@section('title', 'Paiement enseignant')

@section('content')
<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon"><i class="bi bi-wallet2"></i></div>
            <div>
                <h1 class="mb-0">Paiement enseignant</h1>
                <p class="text-muted mb-0">
                    {{ $paiement->enseignant?->user?->prenom }}
                    {{ $paiement->enseignant?->user?->nom }}
                    — {{ $paiement->periode?->label ?? '—' }}
                </p>
            </div>
        </div>

        <div class="heading-actions">
            <a href="{{ route('finance.paiements-enseignants.index') }}" class="btn btn-light">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
        </div>
    </div>

    <div class="row g-3 mb-4">

        <div class="col-lg-3 col-md-6">
            <div class="metric-card metric-primary">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="metric-label">Montant total</div>
                        <div class="metric-value">
                            {{ number_format($paiement->montant_total, 0, ',', ' ') }} F
                        </div>
                    </div>
                    <div class="metric-icon"><i class="bi bi-cash-stack"></i></div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="metric-card metric-warning">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="metric-label">Heures effectuées</div>
                        <div class="metric-value">
                            {{ number_format((float) $paiement->total_heures_effectuees, 1) }} h
                        </div>
                    </div>
                    <div class="metric-icon"><i class="bi bi-clock-history"></i></div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="metric-card metric-success">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="metric-label">Statut</div>
                        <div class="metric-value small">
                            @if($paiement->statut === 'paye')
                                <span class="badge text-bg-success">Payé</span>
                            @else
                                <span class="badge text-bg-warning">En attente</span>
                            @endif
                        </div>
                    </div>
                    <div class="metric-icon"><i class="bi bi-check-circle"></i></div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="metric-card">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="metric-label">Date de paiement</div>
                        <div class="metric-value small">
                            {{ $paiement->date_paiement?->format('d/m/Y') ?? '—' }}
                        </div>
                    </div>
                    <div class="metric-icon"><i class="bi bi-calendar3"></i></div>
                </div>
            </div>
        </div>

    </div>

    <div class="row g-3">

        <div class="col-lg-7">
            <div class="panel">
                <div class="panel-header">
                    <h5 class="mb-0">Détail des heures payées</h5>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Matière</th>
                                <th>Heures</th>
                                <th class="text-end">Montant</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($paiement->lignes as $ligne)
                            <tr>
                                <td>{{ $ligne->affectation?->matiere?->nom ?? '—' }}</td>
                                <td>{{ number_format((float) $ligne->nombre_heures, 1) }} h</td>
                                <td class="text-end">
                                    <strong>{{ number_format($ligne->montant, 0, ',', ' ') }} F</strong>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">
                                    Aucune ligne de détail.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Total</th>
                                <th>{{ number_format((float) $paiement->total_heures_effectuees, 1) }} h</th>
                                <th class="text-end">
                                    {{ number_format($paiement->montant_total, 0, ',', ' ') }} F
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="panel">
                <div class="panel-header">
                    <h5 class="mb-0">Informations</h5>
                </div>
                <div class="panel-body">
                    <div class="info-list">
                        <div>
                            <span>Enseignant</span>
                            <strong>
                                {{ $paiement->enseignant?->user?->prenom }}
                                {{ $paiement->enseignant?->user?->nom }}
                            </strong>
                        </div>

                        <div>
                            <span>Élève</span>
                            <strong>
                                {{ $paiement->contrat?->eleve?->user?->prenom }}
                                {{ $paiement->contrat?->eleve?->user?->nom }}
                            </strong>
                        </div>

                        <div>
                            <span>Contrat</span>
                            <strong>#{{ $paiement->contrat_cours_id }}</strong>
                        </div>

                        <div>
                            <span>Période</span>
                            <strong>{{ $paiement->periode?->label ?? '—' }}</strong>
                        </div>

                        <div>
                            <span>Référence transaction</span>
                            <strong>{{ $paiement->transaction_reference ?? '—' }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection