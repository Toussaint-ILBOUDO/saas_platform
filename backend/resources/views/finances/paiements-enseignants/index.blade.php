@extends('panel.layouts.app')

@section('title', 'Paiements enseignants')

@section('content')
<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon"><i class="bi bi-wallet2"></i></div>
            <div>
                <h1 class="mb-0">Paiements enseignants</h1>
                <p class="text-muted mb-0">Historique des paiements générés pour les enseignants</p>
            </div>
        </div>

        <div class="heading-actions">
            <a href="{{ route('finance.paiements-enseignants.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Nouveau paiement
            </a>
        </div>
    </div>

    @php
        $collection = $paiements->getCollection();
        $totalMontant = $collection->sum('montant_total');
        $payes = $collection->count();
    @endphp

    <div class="row g-3 mb-4">

        <div class="col-lg-3 col-md-6">
            <div class="metric-card metric-primary">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="metric-label">Paiements</div>
                        <div class="metric-value">{{ $paiements->total() }}</div>
                    </div>
                    <div class="metric-icon"><i class="bi bi-wallet2"></i></div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="metric-card metric-success">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="metric-label">Payés (page)</div>
                        <div class="metric-value">{{ $payes }}</div>
                    </div>
                    <div class="metric-icon"><i class="bi bi-check-circle"></i></div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="metric-card metric-danger">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="metric-label">Montant total</div>
                        <div class="metric-value">{{ number_format($totalMontant, 0, ',', ' ') }} F</div>
                    </div>
                    <div class="metric-icon"><i class="bi bi-cash-stack"></i></div>
                </div>
            </div>
        </div>

    </div>

    {{-- FILTRES --}}
    <div class="panel mb-4">
        <div class="panel-header">
            <div>
                <h5 class="mb-0">Filtrer</h5>
                <p class="text-muted mb-0">Par enseignant ou statut</p>
            </div>
        </div>
        <div class="panel-body">
            <form method="GET" action="{{ route('finance.paiements-enseignants.index') }}">
                <div class="row g-3">
                    <div class="col-lg-5">
                        <select name="enseignant_id" class="form-select">
                            <option value="">Tous les enseignants</option>
                            @foreach($enseignants as $enseignant)
                                <option value="{{ $enseignant->id }}"
                                    @selected(request('enseignant_id') == $enseignant->id)>
                                    {{ $enseignant->user?->prenom }} {{ $enseignant->user?->nom }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-5">
                        <select name="statut" class="form-select">
                            <option value="">Tous les statuts</option>
                            <option value="paye" @selected(request('statut') === 'paye')>Payé</option>
                            <option value="en_attente" @selected(request('statut') === 'en_attente')>En attente</option>
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
                <h5 class="mb-0">Liste des paiements</h5>
                <p class="text-muted mb-0">Paiements par enseignant, contrat et période</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Enseignant</th>
                        <th>Élève</th>
                        <th>Période</th>
                        <th>Heures</th>
                        <th>Montant</th>
                        <th>Statut</th>
                        <th>Date</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>

                <tbody>
                @forelse($paiements as $paiement)
                    <tr>
                        <td>
                            {{ $paiement->enseignant?->user?->prenom }}
                            {{ $paiement->enseignant?->user?->nom }}
                        </td>

                        <td>
                            {{ $paiement->contrat?->eleve?->user?->prenom }}
                            {{ $paiement->contrat?->eleve?->user?->nom }}
                        </td>

                        <td>
                            <span class="badge text-bg-secondary">
                                {{ $paiement->periode?->label ?? '—' }}
                            </span>
                        </td>

                        <td>
                            {{ number_format((float) $paiement->total_heures_effectuees, 1) }} h
                        </td>

                        <td>
                            <strong>{{ number_format($paiement->montant_total, 0, ',', ' ') }} F</strong>
                        </td>

                        <td>
                            @if($paiement->statut === 'paye')
                                <span class="badge text-bg-success">
                                    <i class="bi bi-check-circle"></i> Payé
                                </span>
                            @else
                                <span class="badge text-bg-warning">
                                    <i class="bi bi-clock"></i> En attente
                                </span>
                            @endif
                        </td>

                        <td>
                            {{ $paiement->date_paiement?->format('d/m/Y') ?? '—' }}
                        </td>

                        <td class="text-end">
                            <a href="{{ route('finance.paiements-enseignants.show', $paiement) }}"
                               class="btn btn-sm btn-light"
                               title="Voir">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>

                @empty
                    <tr>
                        <td colspan="8">
                            <div class="blank-panel">
                                <div class="blank-state">
                                    <i class="bi bi-wallet2 display-5 text-muted"></i>
                                    <p class="mt-2 text-muted">
                                        Aucun paiement enseignant enregistré.
                                    </p>
                                    <a href="{{ route('finance.paiements-enseignants.create') }}"
                                       class="btn btn-primary mt-2">
                                        <i class="bi bi-plus-lg"></i> Générer un paiement
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
        {{ $paiements->links() }}
    </div>

</div>
@endsection