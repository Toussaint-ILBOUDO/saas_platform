@extends('panel.layouts.app')

@section('title', 'Facture ' . $facture->numero_facture)

@section('content')
<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon">
                <i class="bi bi-receipt"></i>
            </div>
            <div>
                <h1 class="mb-0">Facture {{ $facture->numero_facture }}</h1>
                <p class="text-muted mb-0">
                    Détail de la facture —
                    {{ $facture->contrat?->eleve?->user?->prenom }}
                    {{ $facture->contrat?->eleve?->user?->nom }}
                </p>
            </div>
        </div>

        <div class="heading-actions d-flex gap-2">
            @can('update', $facture)
                @if($facture->estEnAttente())
                    <a href="{{ route('finance.factures.payer', $facture) }}"
                       class="btn btn-success">
                        <i class="bi bi-check2-circle"></i> Marquer payé
                    </a>
                @endif
            @endcan

            <a href="{{ route('finance.factures.pdf', $facture) }}"
               class="btn btn-light"
               target="_blank">
                <i class="bi bi-file-earmark-pdf"></i> PDF
            </a>

            @if(auth()->user()->hasRole('parent'))
                <a href="{{ route('mes-factures.index') }}" class="btn btn-light">
                    <i class="bi bi-arrow-left"></i> Retour
                </a>
            @else
                <a href="{{ route('finance.factures.index') }}" class="btn btn-light">
                    <i class="bi bi-arrow-left"></i> Retour
                </a>
            @endif
        </div>
    </div>


    {{-- KPI --}}
    <div class="row g-3 mb-4">

        <div class="col-lg-3 col-md-6">
            <div class="metric-card metric-primary">
                <div class="metric-label">Montant total</div>
                <div class="metric-value">
                    {{ number_format($facture->montant_total, 0, ',', ' ') }} F
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="metric-card metric-success">
                <div class="metric-label">Heures réalisées</div>
                <div class="metric-value">
                    {{ number_format($facture->volume_horaire_total, 1) }} h
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="metric-card metric-warning">
                <div class="metric-label">Statut paiement</div>
                <div class="metric-value">
                    @if($facture->estPayee())
                        <span class="badge text-bg-success">Payée</span>
                    @elseif($facture->estPartiellementPayee())
                        <span class="badge text-bg-warning">Partiel</span>
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
                            <span>Numéro</span>
                            <strong>{{ $facture->numero_facture }}</strong>
                        </div>
                        <div>
                            <span>Élève</span>
                            <strong>
                                {{ $facture->contrat?->eleve?->user?->prenom }}
                                {{ $facture->contrat?->eleve?->user?->nom }}
                            </strong>
                        </div>
                        <div>
                            <span>Parent</span>
                            <strong>
                                {{ $facture->parent?->prenom }}
                                {{ $facture->parent?->nom }}
                            </strong>
                        </div>
                        <div>
                            <span>Période</span>
                            <strong>{{ $facture->periode?->label ?? '—' }}</strong>
                        </div>
                        <div>
                            <span>Date limite</span>
                            <strong>
                                {{ $facture->date_limite_paiement?->format('d/m/Y') ?? 'Non définie' }}
                            </strong>
                        </div>
                        <div>
                            <span>Créée le</span>
                            <strong>{{ $facture->created_at?->format('d/m/Y H:i') }}</strong>
                        </div>
                    </div>
                </div>
            </div>


            {{-- LIGNES DE FACTURE --}}
            <div class="panel mb-4">
                <div class="panel-header">
                    <h5 class="mb-0">Détail des cours</h5>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Enseignant</th>
                                <th>Matière</th>
                                <th class="text-end">Heures</th>
                                <th class="text-end">Taux/h</th>
                                <th class="text-end">Montant</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($facture->lignes as $ligne)
                                <tr>
                                    <td>
                                        {{ $ligne->affectation?->enseignant?->user?->prenom }}
                                        {{ $ligne->affectation?->enseignant?->user?->nom }}
                                    </td>
                                    <td>
                                        <span class="badge text-bg-light">
                                            {{ $ligne->affectation?->matiere?->nom ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        {{ number_format($ligne->nombre_heures, 1) }} h
                                    </td>
                                    <td class="text-end">
                                        {{ number_format($ligne->taux_horaire, 0, ',', ' ') }} F
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


            {{-- COMMENTAIRE --}}
            @if($facture->commentaire)
                <div class="panel mb-4">
                    <div class="panel-header">
                        <h5 class="mb-0">Commentaire</h5>
                    </div>
                    <div class="panel-body">
                        <p class="mb-0">{{ $facture->commentaire }}</p>
                    </div>
                </div>
            @endif

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
                        <span>Cours ({{ number_format($facture->volume_horaire_total, 1) }} h)</span>
                        <strong>
                            {{ number_format($facture->montant_total - $facture->frais_suivi - $facture->autres_frais + $facture->remise, 0, ',', ' ') }} F
                        </strong>
                    </div>

                    @if($facture->frais_suivi > 0)
                        <div class="d-flex justify-content-between mb-2">
                            <span>Frais de suivi</span>
                            <strong>{{ number_format($facture->frais_suivi, 0, ',', ' ') }} F</strong>
                        </div>
                    @endif

                    @if($facture->autres_frais > 0)
                        <div class="d-flex justify-content-between mb-2">
                            <span>Autres frais</span>
                            <strong>{{ number_format($facture->autres_frais, 0, ',', ' ') }} F</strong>
                        </div>
                    @endif

                    @if($facture->remise > 0)
                        <div class="d-flex justify-content-between mb-2 text-danger">
                            <span>Remise</span>
                            <strong>- {{ number_format($facture->remise, 0, ',', ' ') }} F</strong>
                        </div>
                    @endif

                    <hr>

                    <div class="d-flex justify-content-between">
                        <span class="fs-5"><strong>Total</strong></span>
                        <span class="fs-5">
                            <strong>{{ number_format($facture->montant_total, 0, ',', ' ') }} F</strong>
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
                            @else
                                <span class="badge text-bg-danger">En attente</span>
                            @endif
                        </div>

                        @if($facture->date_paiement)
                            <div>
                                <span>Date paiement</span>
                                <strong>{{ $facture->date_paiement->format('d/m/Y') }}</strong>
                            </div>
                        @endif

                        @if($facture->mode_paiement)
                            <div>
                                <span>Mode</span>
                                <strong>{{ ucfirst(str_replace('_', ' ', $facture->mode_paiement)) }}</strong>
                            </div>
                        @endif

                        @if($facture->reference_paiement)
                            <div>
                                <span>Référence</span>
                                <strong>{{ $facture->reference_paiement }}</strong>
                            </div>
                        @endif
                    </div>

                    @can('update', $facture)
                        @if($facture->estEnAttente())
                            <div class="mt-3">
                                <a href="{{ route('finance.factures.payer', $facture) }}"
                                   class="btn btn-success w-100">
                                    <i class="bi bi-check2-circle"></i> Marquer comme payée
                                </a>
                            </div>
                        @endif
                    @endcan
                </div>
            </div>


            {{-- INFOS CABINET --}}
            <div class="panel">
                {{-- <div class="panel-header">
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
                    </div> --}}
                    <div class="panel">
                        <div class="panel-header">
                            <h5 class="mb-0">{{ $cabinet['nom'] }}</h5>
                        </div>
                        <div class="panel-body">
                            <div class="info-list">
                                <div>
                                    <span>Orange Money</span>
                                    <strong>{{ $cabinet['orange_money'] }}</strong>
                                </div>
                                <div>
                                    <span>Moov Money</span>
                                    <strong>{{ $cabinet['moov_money'] }}</strong>
                                </div>
                                <div>
                                    <span>Tél</span>
                                    <strong>{{ $cabinet['telephone'] }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
