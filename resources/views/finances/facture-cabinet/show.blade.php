@extends('panel.layouts.app')

@section('title', 'Facture Cabinet — ' . $facture->periode_debut . ' au ' . $facture->periode_fin)

@section('content')
<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon">
                <i class="bi bi-briefcase"></i>
            </div>
            <div>
                <h1 class="mb-0">Facture Cabinet</h1>
                <p class="text-muted mb-0">
                    Période : {{ \Carbon\Carbon::parse($facture->periode_debut)->format('d/m/Y') }}
                    — {{ \Carbon\Carbon::parse($facture->periode_fin)->format('d/m/Y') }}
                </p>
            </div>
        </div>

        <div class="heading-actions d-flex gap-2">
            @can('payer', $facture)
                @if(!$facture->estPayee())
                    <a href="{{ route('finance.facture-cabinet.payer', $facture) }}"
                       class="btn btn-success">
                        <i class="bi bi-cash"></i> Enregistrer un paiement
                    </a>
                @endif
            @endcan

            <a href="{{ route('finance.facture-cabinet.pdf', $facture) }}"
               class="btn btn-light"
               target="_blank">
                <i class="bi bi-file-earmark-pdf"></i> PDF
            </a>

            <a href="{{ route('finance.facture-cabinet.index') }}" class="btn btn-light">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
        </div>
    </div>


    {{-- KPI --}}
    <div class="row g-3 mb-4">

        <div class="col-lg-3 col-md-6">
            <div class="metric-card metric-primary">
                <div class="metric-label">Montant total dû</div>
                <div class="metric-value">
                    {{ number_format($facture->montant_total_du, 0, ',', ' ') }} F
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="metric-card metric-success">
                <div class="metric-label">Montant payé</div>
                <div class="metric-value">
                    {{ number_format($facture->montant_paye, 0, ',', ' ') }} F
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="metric-card metric-warning">
                <div class="metric-label">Montant restant</div>
                <div class="metric-value">
                    {{ number_format($facture->montant_restant, 0, ',', ' ') }} F
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="metric-card metric-danger">
                <div class="metric-label">Statut</div>
                <div class="metric-value">
                    @if($facture->estPayee())
                        <span class="badge text-bg-success">Payée</span>
                    @elseif($facture->estPartiellementPayee())
                        <span class="badge text-bg-warning">Partiel</span>
                    @elseif($facture->estAnnulee())
                        <span class="badge text-bg-secondary">Annulée</span>
                    @else
                        <span class="badge text-bg-danger">En attente</span>
                    @endif
                </div>
            </div>
        </div>

    </div>


    <div class="row g-4">

        {{-- COLONNE GAUCHE --}}
        <div class="col-lg-8">

            {{-- INFORMATIONS --}}
            <div class="panel mb-4">
                <div class="panel-header">
                    <h5 class="mb-0">Informations générales</h5>
                </div>
                <div class="panel-body">
                    <div class="info-list">
                        <div>
                            <span>Période du</span>
                            <strong>{{ \Carbon\Carbon::parse($facture->periode_debut)->format('d/m/Y') }}</strong>
                        </div>
                        <div>
                            <span>Période au</span>
                            <strong>{{ \Carbon\Carbon::parse($facture->periode_fin)->format('d/m/Y') }}</strong>
                        </div>
                        <div>
                            <span>Date facture</span>
                            <strong>{{ \Carbon\Carbon::parse($facture->date_facture)->format('d/m/Y') }}</strong>
                        </div>
                        @if($facture->date_paiement)
                            <div>
                                <span>Date paiement</span>
                                <strong>{{ $facture->date_paiement->format('d/m/Y') }}</strong>
                            </div>
                        @endif
                        <div>
                            <span>Créée le</span>
                            <strong>{{ $facture->created_at?->format('d/m/Y H:i') }}</strong>
                        </div>
                    </div>
                </div>
            </div>


            {{-- LIGNES DE COMMISSION --}}
            <div class="panel mb-4">
                <div class="panel-header">
                    <h5 class="mb-0">Détail des commissions</h5>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th class="text-end">Quantité</th>
                                <th class="text-end">Base calcul</th>
                                <th class="text-end">Taux</th>
                                <th class="text-end">Montant</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($facture->lignes as $ligne)
                                <tr>
                                    <td>
                                        <span class="badge text-bg-primary">
                                            {{ $ligne->typeCommission?->nom_du_type ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        {{ number_format($ligne->quantite, 0, ',', ' ') }}
                                    </td>
                                    <td class="text-end">
                                        {{ number_format($ligne->base_calcul, 0, ',', ' ') }} F
                                    </td>
                                    <td class="text-end">
                                        {{ number_format($ligne->taux_commission, 1) }}%
                                    </td>
                                    <td class="text-end">
                                        <strong>
                                            {{ number_format($ligne->montant, 0, ',', ' ') }} F
                                        </strong>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>


            {{-- PAIEMENTS --}}
            <div class="panel mb-4">
                <div class="panel-header">
                    <div>
                        <h5 class="mb-0">Historique des paiements</h5>
                        <p class="text-muted mb-0">{{ $facture->paiements->count() }} paiement(s)</p>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Mode</th>
                                <th>Référence</th>
                                <th class="text-end">Montant</th>
                                <th>Statut</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($facture->paiements as $paiement)
                                <tr>
                                    <td>
                                        {{ $paiement->date_paiement->format('d/m/Y') }}
                                    </td>
                                    <td>
                                        {{ ucfirst(str_replace('_', ' ', $paiement->mode_paiement)) }}
                                    </td>
                                    <td>
                                        {{ $paiement->reference_paiement ?? '—' }}
                                    </td>
                                    <td class="text-end">
                                        <strong>
                                            {{ number_format($paiement->montant_paye, 0, ',', ' ') }} F
                                        </strong>
                                    </td>
                                    <td>
                                        @if($paiement->estValide())
                                            <span class="badge text-bg-success">Validé</span>
                                        @elseif($paiement->estAnnule())
                                            <span class="badge text-bg-danger">Annulé</span>
                                        @else
                                            <span class="badge text-bg-warning">En attente</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if($paiement->estEnAttente())
                                            <div class="btn-group">
                                                <form method="POST"
                                                      action="{{ route('finance.paiement-cabinet.valider', $paiement) }}"
                                                      style="display:inline">
                                                    @csrf
                                                    <button class="btn btn-sm btn-light text-success"
                                                            title="Valider"
                                                            onclick="return confirm('Valider ce paiement ?')">
                                                        <i class="bi bi-check-lg"></i>
                                                    </button>
                                                </form>
                                                <form method="POST"
                                                      action="{{ route('finance.paiement-cabinet.annuler', $paiement) }}"
                                                      style="display:inline">
                                                    @csrf
                                                    <button class="btn btn-sm btn-light text-danger"
                                                            title="Annuler"
                                                            onclick="return confirm('Annuler ce paiement ?')">
                                                        <i class="bi bi-x-lg"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-3">
                                        Aucun paiement enregistré.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>


        {{-- COLONNE DROITE --}}
        <div class="col-lg-4">

            {{-- RÉCAPITULATIF --}}
            <div class="panel mb-4">
                <div class="panel-header">
                    <h5 class="mb-0">Récapitulatif</h5>
                </div>
                <div class="panel-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Cours ({{ $facture->total_cours }})</span>
                        <strong>
                            @if($facture->lignes->where('typeCommission.nom_du_type', 'Cours')->first())
                                {{ number_format($facture->lignes->where('typeCommission.nom_du_type', 'Cours')->first()->montant, 0, ',', ' ') }} F
                            @else
                                0 F
                            @endif
                        </strong>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span>Inscriptions ({{ $facture->total_inscriptions }})</span>
                        <strong>
                            @if($facture->lignes->where('typeCommission.nom_du_type', 'Inscription')->first())
                                {{ number_format($facture->lignes->where('typeCommission.nom_du_type', 'Inscription')->first()->montant, 0, ',', ' ') }} F
                            @else
                                0 F
                            @endif
                        </strong>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span>Ventes ({{ $facture->total_ventes }})</span>
                        <strong>
                            @if($facture->lignes->where('typeCommission.nom_du_type', 'Vente')->first())
                                {{ number_format($facture->lignes->where('typeCommission.nom_du_type', 'Vente')->first()->montant, 0, ',', ' ') }} F
                            @else
                                0 F
                            @endif
                        </strong>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between">
                        <span class="fs-5"><strong>Total</strong></span>
                        <span class="fs-5">
                            <strong>{{ number_format($facture->montant_total_du, 0, ',', ' ') }} F</strong>
                        </span>
                    </div>
                </div>
            </div>


            {{-- PAIEMENT --}}
            <div class="panel mb-4">
                <div class="panel-header">
                    <h5 class="mb-0">Paiement</h5>
                </div>
                <div class="panel-body">
                    <div class="info-list">
                        <div>
                            <span>Statut</span>
                            @if($facture->estPayee())
                                <span class="badge text-bg-success">Payée</span>
                            @elseif($facture->estPartiellementPayee())
                                <span class="badge text-bg-warning">Partiel</span>
                            @elseif($facture->estAnnulee())
                                <span class="badge text-bg-secondary">Annulée</span>
                            @else
                                <span class="badge text-bg-danger">En attente</span>
                            @endif
                        </div>

                        <div>
                            <span>Montant payé</span>
                            <strong>{{ number_format($facture->montant_paye, 0, ',', ' ') }} F</strong>
                        </div>

                        <div>
                            <span>Restant</span>
                            <strong class="text-danger">{{ number_format($facture->montant_restant, 0, ',', ' ') }} F</strong>
                        </div>

                        @if($facture->date_paiement)
                            <div>
                                <span>Date paiement</span>
                                <strong>{{ $facture->date_paiement->format('d/m/Y') }}</strong>
                            </div>
                        @endif
                    </div>

                    @can('payer', $facture)
                        @if(!$facture->estPayee() && !$facture->estAnnulee())
                            <div class="mt-3">
                                <a href="{{ route('finance.facture-cabinet.payer', $facture) }}"
                                   class="btn btn-success w-100">
                                    <i class="bi bi-cash"></i> Enregistrer un paiement
                                </a>
                            </div>
                        @endif
                    @endcan
                </div>
            </div>


            {{-- INFOS CABINET --}}
            <div class="panel">
                <div class="panel-header">
                    <h5 class="mb-0">{{ $cabinet['nom'] }}</h5>
                </div>
                <div class="panel-body">
                    <div class="info-list">
                        <div>
                            <span>Tél</span>
                            <strong>{{ $cabinet['telephone'] }}</strong>
                        </div>
                        <div>
                            <span>WhatsApp</span>
                            <strong>{{ $cabinet['whatsapp'] }}</strong>
                        </div>
                        <div>
                            <span>Email</span>
                            <strong>{{ $cabinet['email'] }}</strong>
                        </div>
                        <div>
                            <span>Adresse</span>
                            <strong>{{ $cabinet['adresse'] }}</strong>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
