@extends('panel.layouts.app')

@section('title', 'Bulletin ' . $bulletin->numero)

@section('content')
<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon"><i class="bi bi-wallet2"></i></div>
            <div>
                <h1 class="mb-0">Bulletin {{ $bulletin->numero }}</h1>
                <p class="text-muted mb-0">
                    {{ $bulletin->enseignant?->user?->prenom }}
                    {{ $bulletin->enseignant?->user?->nom }}
                    — {{ $bulletin->periode?->label }}
                </p>
            </div>
        </div>

        <div class="heading-actions">
            <a href="{{ route('mes-bulletins.index') }}" class="btn btn-light">
                <i class="bi bi-arrow-left"></i> Retour
            </a>

            <a href="{{ route('mes-bulletins.pdf', $bulletin) }}"
               class="btn btn-primary" target="_blank">
                <i class="bi bi-file-earmark-pdf"></i> PDF
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- STATUT --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="metric-card metric-primary">
                <div class="metric-label">Statut</div>
                <div class="metric-value" style="font-size: 1rem;">
                    @switch($bulletin->statut)
                        @case('brouillon')
                            <span class="badge text-bg-secondary">Brouillon</span>
                            @break
                        @case('genere')
                            <span class="badge text-bg-info">Genere</span>
                            @break
                        @case('consulte')
                            <span class="badge text-bg-primary">Consulte</span>
                            @break
                        @case('valide')
                            <span class="badge text-bg-success">Valide</span>
                            @break
                        @case('verse')
                            <span class="badge text-bg-dark">Verse</span>
                            @break
                        @case('conteste')
                            <span class="badge text-bg-danger">Conteste</span>
                            @break
                        @case('corrige')
                            <span class="badge text-bg-warning">Corrige</span>
                            @break
                    @endswitch
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="metric-card metric-success">
                <div class="metric-label">Total heures</div>
                <div class="metric-value">{{ number_format($bulletin->total_heures, 2) }} h</div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="metric-card metric-warning">
                <div class="metric-label">Montant brut</div>
                <div class="metric-value">{{ number_format($bulletin->montant_brut, 0, ',', ' ') }} FCFA</div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="metric-card metric-success">
                <div class="metric-label">Net a payer</div>
                <div class="metric-value">{{ number_format($bulletin->montant_net, 0, ',', ' ') }} FCFA</div>
            </div>
        </div>
    </div>

    {{-- INFORMATIONS ENSEIGNANT --}}
    <div class="panel mb-4">
        <div class="panel-header">
            <h5 class="mb-0">Informations enseignant</h5>
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-4">
                    <strong>Nom :</strong>
                    {{ $bulletin->enseignant?->user?->nom }}
                </div>
                <div class="col-md-4">
                    <strong>Prenom :</strong>
                    {{ $bulletin->enseignant?->user?->prenom }}
                </div>
                <div class="col-md-4">
                    <strong>Telephone :</strong>
                    {{ $bulletin->enseignant?->user?->telephone_appel ?? '—' }}
                </div>
            </div>
        </div>
    </div>

    {{-- DETAIL DES LIGNES --}}
    <div class="panel mb-4">
        <div class="panel-header">
            <h5 class="mb-0">Detail de la remuneration</h5>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Eleve</th>
                        <th>Matiere</th>
                        <th>Heures</th>
                        <th>Taux horaire</th>
                        <th class="text-end">Montant</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bulletin->lignes as $ligne)
                        <tr>
                            <td>
                                <strong>
                                    {{ $ligne->eleve?->user?->prenom }}
                                    {{ $ligne->eleve?->user?->nom }}
                                </strong>
                            </td>
                            <td>
                                <span class="badge text-bg-light">
                                    {{ $ligne->matiere?->nom ?? '—' }}
                                </span>
                            </td>
                            <td>{{ number_format($ligne->nombre_heures, 2) }} h</td>
                            <td>{{ number_format($ligne->taux_horaire, 0, ',', ' ') }} FCFA/h</td>
                            <td class="text-end">
                                <strong>{{ number_format($ligne->montant, 0, ',', ' ') }} FCFA</strong>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="table-light">
                        <td colspan="2"><strong>Total heures</strong></td>
                        <td><strong>{{ number_format($bulletin->total_heures, 2) }} h</strong></td>
                        <td></td>
                        <td class="text-end">
                            <strong>{{ number_format($bulletin->montant_brut, 0, ',', ' ') }} FCFA</strong>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- AJUSTEMENTS --}}
    <div class="panel mb-4">
        <div class="panel-header">
            <h5 class="mb-0">Ajustements</h5>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Libelle</th>
                        <th class="text-end">Montant</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bulletin->ajustements as $ajustement)
                        <tr>
                            <td>
                                @if($ajustement->type === 'prime')
                                    <span class="badge text-bg-success">Prime</span>
                                @else
                                    <span class="badge text-bg-danger">Retenue</span>
                                @endif
                            </td>
                            <td>{{ $ajustement->libelle }}</td>
                            <td class="text-end">
                                @if($ajustement->type === 'prime')
                                    <span class="text-success">
                                        +{{ number_format($ajustement->montant, 0, ',', ' ') }} FCFA
                                    </span>
                                @else
                                    <span class="text-danger">
                                        -{{ number_format($ajustement->montant, 0, ',', ' ') }} FCFA
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-muted text-center">
                                Aucun ajustement
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2">Primes</td>
                        <td class="text-end text-success">
                            +{{ number_format($totalPrimes, 0, ',', ' ') }} FCFA
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">Retenues</td>
                        <td class="text-end text-danger">
                            -{{ number_format($totalRetenues, 0, ',', ' ') }} FCFA
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- RECAPITULATIF --}}
    <div class="panel mb-4">
        <div class="panel-body">
            <div class="row">
                <div class="col-md-4">
                    <strong>Montant brut :</strong>
                    {{ number_format($bulletin->montant_brut, 0, ',', ' ') }} FCFA
                </div>
                <div class="col-md-4">
                    <strong>Frais de suivi :</strong>
                    - {{ number_format($bulletin->frais_suivi, 0, ',', ' ') }} FCFA
                </div>
                <div class="col-md-4">
                    <strong>Net a payer :</strong>
                    <span class="text-success fw-bold">
                        {{ number_format($bulletin->montant_net, 0, ',', ' ') }} FCFA
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- ACTIONS SELON STATUT (enseignant) --}}
    @if(in_array($bulletin->statut, ['genere', 'corrige']))
        <div class="panel mb-4">
            <div class="panel-body">
                <form method="POST" action="{{ route('mes-bulletins.consulter', $bulletin) }}">
                    @csrf
                    <button class="btn btn-primary">
                        <i class="bi bi-eye"></i> Marquer comme consulte
                    </button>
                </form>
            </div>
        </div>
    @endif

    @if($bulletin->statut === 'consulte')
        <div class="panel mb-4">
            <div class="panel-body d-flex gap-2">
                <form method="POST" action="{{ route('mes-bulletins.valider', $bulletin) }}">
                    @csrf
                    <button class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Valider le bulletin
                    </button>
                </form>

                <a href="{{ route('mes-bulletins.contester.form', $bulletin) }}"
                   class="btn btn-danger">
                    <i class="bi bi-exclamation-triangle"></i> Contester
                </a>
            </div>
        </div>
    @endif

    @if($bulletin->statut === 'conteste')
        <div class="panel mb-4">
            <div class="panel-header">
                <h5 class="mb-0 text-danger">Contestation</h5>
            </div>
            <div class="panel-body">
                <p>{{ $bulletin->commentaire_enseignant }}</p>
            </div>
        </div>
    @endif

    @if($bulletin->statut === 'verse')
        <div class="panel mb-4">
            <div class="panel-header">
                <h5 class="mb-0 text-success">Informations de paiement</h5>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-4">
                        <strong>Date :</strong>
                        {{ $bulletin->date_paiement?->format('d/m/Y') ?? '—' }}
                    </div>
                    <div class="col-md-4">
                        <strong>Mode :</strong>
                        {{ $bulletin->mode_paiement ?? '—' }}
                    </div>
                    <div class="col-md-4">
                        <strong>Reference :</strong>
                        {{ $bulletin->reference_paiement ?? '—' }}
                    </div>
                </div>
            </div>
        </div>
    @endif

</div>
@endsection
