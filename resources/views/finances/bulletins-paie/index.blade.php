@extends('panel.layouts.app')

@section('title', 'Bulletins de paie')

@section('content')
<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon"><i class="bi bi-wallet2"></i></div>
            <div>
                <h1 class="mb-0">Bulletins de paie</h1>
                <p class="text-muted mb-0">Gestion de la rémunération des enseignants</p>
            </div>
        </div>

        <div class="heading-actions">
            <a href="{{ route('finance.bulletins-paie.preview') }}"
               class="btn btn-success">
                <i class="bi bi-lightning"></i> Générer les bulletins
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- KPI --}}
    <div class="row g-3 mb-4">
        @php
            $totalBulletins = $bulletins->total();
            $totalVerse = $bulletins->getCollection()->where('statut', 'verse')->count();
            $totalEnAttente = $bulletins->getCollection()->where('statut', 'valide')->count();
        @endphp

        <div class="col-lg-3 col-md-6">
            <div class="metric-card metric-primary">
                <div class="metric-label">Total bulletins</div>
                <div class="metric-value">{{ $totalBulletins }}</div>
                <div class="metric-meta"><span>Cette période</span></div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="metric-card metric-success">
                <div class="metric-label">Versés</div>
                <div class="metric-value">{{ $totalVerse }}</div>
                <div class="metric-meta"><span>Paiements effectués</span></div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="metric-card metric-warning">
                <div class="metric-label">En attente</div>
                <div class="metric-value">{{ $totalEnAttente }}</div>
                <div class="metric-meta"><span>Validés, à payer</span></div>
            </div>
        </div>
    </div>

    {{-- FILTRES --}}
    <div class="panel mb-4">
        <div class="panel-header">
            <div>
                <h5 class="mb-0">Filtres</h5>
                <p class="text-muted mb-0">Filtrer par période, statut ou enseignant</p>
            </div>
        </div>

        <div class="panel-body">
            <form method="GET" action="{{ route('finance.bulletins-paie.index') }}">
                <div class="row g-3">
                    <div class="col-lg-3">
                        <label class="form-label fw-bold">Période</label>
                        <select name="periode_id" class="form-select">
                            <option value="">Toutes les périodes</option>
                            @foreach($periodes as $periode)
                                <option value="{{ $periode->id }}"
                                    {{ $periodeSelectionnee?->id === $periode->id ? 'selected' : '' }}>
                                    {{ $periode->label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-3">
                        <label class="form-label fw-bold">Statut</label>
                        <select name="statut" class="form-select">
                            <option value="">Tous les statuts</option>
                            <option value="brouillon" {{ request('statut') === 'brouillon' ? 'selected' : '' }}>Brouillon</option>
                            <option value="genere" {{ request('statut') === 'genere' ? 'selected' : '' }}>Généré</option>
                            <option value="consulte" {{ request('statut') === 'consulte' ? 'selected' : '' }}>Consulté</option>
                            <option value="valide" {{ request('statut') === 'valide' ? 'selected' : '' }}>Validé</option>
                            <option value="verse" {{ request('statut') === 'verse' ? 'selected' : '' }}>Versé</option>
                            <option value="conteste" {{ request('statut') === 'conteste' ? 'selected' : '' }}>Contesté</option>
                        </select>
                    </div>

                    <div class="col-lg-4">
                        <label class="form-label fw-bold">Recherche</label>
                        <input type="text"
                               name="search"
                               value="{{ request('search') }}"
                               class="form-control"
                               placeholder="Nom de l'enseignant...">
                    </div>

                    <div class="col-lg-2 d-flex align-items-end">
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
                <h5 class="mb-0">Liste des bulletins</h5>
                <p class="text-muted mb-0">Bulletin unique par enseignant par période</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>N° Bulletin</th>
                        <th>Enseignant</th>
                        <th>Période</th>
                        <th>Heures</th>
                        <th>Montant net</th>
                        <th>Statut</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>

                <tbody>
                @forelse($bulletins as $bulletin)
                    <tr>
                        <td>
                            <strong>{{ $bulletin->numero }}</strong>
                        </td>

                        <td>
                            {{ $bulletin->enseignant?->user?->prenom }}
                            {{ $bulletin->enseignant?->user?->nom }}
                        </td>

                        <td>
                            <span class="badge text-bg-light">
                                {{ $bulletin->periode?->label }}
                            </span>
                        </td>

                        <td>
                            <strong>{{ number_format($bulletin->total_heures, 2) }} h</strong>
                        </td>

                        <td>
                            <strong>{{ number_format($bulletin->montant_net, 0, ',', ' ') }} FCFA</strong>
                        </td>

                        <td>
                            @switch($bulletin->statut)
                                @case('brouillon')
                                    <span class="badge text-bg-secondary">Brouillon</span>
                                    @break
                                @case('genere')
                                    <span class="badge text-bg-info">Généré</span>
                                    @break
                                @case('consulte')
                                    <span class="badge text-bg-primary">Consulté</span>
                                    @break
                                @case('valide')
                                    <span class="badge text-bg-success">Validé</span>
                                    @break
                                @case('verse')
                                    <span class="badge text-bg-dark">Versé</span>
                                    @break
                                @case('conteste')
                                    <span class="badge text-bg-danger">Contesté</span>
                                    @break
                                @case('corrige')
                                    <span class="badge text-bg-warning">Corrigé</span>
                                    @break
                            @endswitch
                        </td>

                        <td class="text-end">
                            <div class="btn-group">
                                <a href="{{ route('finance.bulletins-paie.show', $bulletin) }}"
                                   class="btn btn-sm btn-light"
                                   title="Voir">
                                    <i class="bi bi-eye"></i>
                                </a>

                                <a href="{{ route('finance.bulletins-paie.pdf', $bulletin) }}"
                                   class="btn btn-sm btn-light"
                                   title="PDF"
                                   target="_blank">
                                    <i class="bi bi-file-earmark-pdf"></i>
                                </a>

                                @if(in_array($bulletin->statut, ['valide']))
                                    <a href="{{ route('finance.bulletins-paie.payer', $bulletin) }}"
                                       class="btn btn-sm btn-success"
                                       title="Payer">
                                        <i class="bi bi-wallet2"></i> Payer
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="blank-state">
                                <i class="bi bi-wallet2 display-5 text-muted"></i>
                                <p class="mt-3 text-muted mb-0">
                                    Aucun bulletin de paie trouvé.
                                </p>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $bulletins->links() }}
    </div>

</div>
@endsection
