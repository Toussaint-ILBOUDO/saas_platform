@extends('landlord.layouts.app')

@section('title', 'Facturation de la plateforme')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h1 class="h3 fw-bold mb-0">Facturation des cabinets</h1>

        <a href="{{ route('landlord.facturation.preparer') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Nouvelle facture
        </a>
    </div>
    <div class="small text-muted mb-4">
        Cycle d'une facture : brouillon (modifiable) → émise (numérotée, montants figés) → réglée.
        Un cabinet ne peut pas avoir deux factures empiétant sur la même période, et une facture
        annulée libère sa période pour régularisation.
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Factures actives</div>
                    <div class="h4 fw-bold mb-0">{{ number_format((int) $resume->nb, 0, ',', ' ') }}</div>
                    <div class="small text-muted">émises ou en cours de règlement</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Facturé</div>
                    <div class="h4 fw-bold mb-0">{{ number_format((float) $resume->facture, 0, ',', ' ') }} FCFA</div>
                    <div class="small text-muted">toutes factures non annulées</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Encaissé</div>
                    <div class="h4 fw-bold mb-0 text-success">{{ number_format((float) $resume->paye, 0, ',', ' ') }} FCFA</div>
                    <div class="small text-muted">paiements enregistrés</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Reste à encaisser</div>
                    <div class="h4 fw-bold mb-0 text-warning">
                        {{ number_format((float) max(0, $resume->facture - $resume->paye), 0, ',', ' ') }} FCFA
                    </div>
                    <div class="small text-muted">impayés du portefeuille</div>
                </div>
            </div>
        </div>
    </div>

    <form method="GET" class="row g-2 mb-3 align-items-end">
        <div class="col-md-5">
            <label class="form-label small text-muted mb-1">Cabinet</label>
            <select name="cabinet" class="form-select form-select-sm">
                <option value="">Tous</option>
                @foreach ($cabinets as $cabinet)
                    <option value="{{ $cabinet->id }}" {{ $cabinetFiltre === $cabinet->id ? 'selected' : '' }}>
                        {{ $cabinet->nom }} ({{ $cabinet->id }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label small text-muted mb-1">Statut</label>
            <select name="statut" class="form-select form-select-sm">
                <option value="">Tous</option>
                @foreach ($statuts as $code => $nom)
                    <option value="{{ $code }}" {{ $statutFiltre === $code ? 'selected' : '' }}>{{ $nom }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <button class="btn btn-outline-secondary btn-sm w-100" type="submit">
                <i class="bi bi-funnel me-1"></i>Filtrer
            </button>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white small text-muted">
            Par période la plus récente d'abord — cliquez l'œil pour ouvrir la fiche de travail.
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Référence</th>
                        <th>Cabinet</th>
                        <th>Période facturée</th>
                        <th>Statut</th>
                        <th class="text-end">Total (FCFA)</th>
                        <th class="text-end">Payé (FCFA)</th>
                        <th class="text-end">Reste (FCFA)</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($factures as $facture)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $facture->numero_facture ?? ('Brouillon #'.$facture->id) }}</div>
                                <div class="small text-muted">émission {{ $facture->emise_le?->format('d/m/Y') ?? '—' }}
                                @if ($facture->estAnnulee() && $facture->annulee_le) — annulée {{ $facture->annulee_le->format('d/m/Y') }}@endif</div>
                            </td>
                            <td>
                                {{ $facture->cabinet?->nom ?? $facture->cabinet_id }}
                            </td>
                            <td class="small">
                                {{ $facture->periode_debut?->format('d/m/Y') }} → {{ $facture->periode_fin?->format('d/m/Y') }}
                            </td>
                            <td>
                                <span class="badge {{ $facture->statut === 'payee' ? 'bg-success' : ($facture->statut === 'partiellement_payee' ? 'bg-warning text-dark' : ($facture->statut === 'annulee' ? 'bg-danger' : ($facture->statut === 'brouillon' ? 'bg-secondary' : 'bg-info text-dark'))) }}">
                                    {{ $facture->statut }}
                                </span>
                            </td>
                            <td class="text-end fw-semibold">{{ number_format((float) $facture->montant_total, 0, ',', ' ') }}</td>
                            <td class="text-end">{{ number_format((float) $facture->montant_paye, 0, ',', ' ') }}</td>
                            <td class="text-end {{ $facture->resteAPayer() > 0 && $facture->statut !== 'annulee' && $facture->statut !== 'brouillon' ? 'text-danger fw-semibold' : '' }}">
                                {{ number_format((float) $facture->resteAPayer(), 0, ',', ' ') }}
                            </td>
                            <td class="text-end">
                                <a href="{{ route('landlord.facturation.show', $facture) }}" class="btn btn-sm btn-outline-primary" title="Ouvrir la fiche">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                Aucune facture correspondante.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $factures->links() }}
@endsection
