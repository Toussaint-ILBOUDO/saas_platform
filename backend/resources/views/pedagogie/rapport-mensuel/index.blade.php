@extends('panel.layouts.app')

@section('title', 'Rapports mensuels')

@section('content')
<div class="container-fluid">

    {{-- HEADER --}}
    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon"><i class="bi bi-file-earmark-text"></i></div>
            <div>
                <h1 class="mb-0">Rapports mensuels</h1>
                <p class="text-muted mb-0">Suivi pédagogique mensuel des élèves</p>
            </div>
        </div>

        <div class="heading-actions">
            <a href="{{ route('rapports-mensuels.index') }}" class="btn btn-light">
                <i class="bi bi-arrow-clockwise"></i> Actualiser
            </a>
        </div>
    </div>

    {{-- KPI --}}
    <div class="row g-3 mb-4">

        <div class="col-lg-3 col-md-6">
            <div class="metric-card metric-primary">
                <div class="metric-label">Élèves suivis</div>
                <div class="metric-value">{{ $totalContrats }}</div>
                <div class="metric-meta"><span>Contrats actifs</span></div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="metric-card metric-success">
                <div class="metric-label">Rapports créés</div>
                <div class="metric-value">{{ $rapportCrees }}</div>
                <div class="metric-meta">
                    <span>{{ $periodeSelectionnee ? $periodeSelectionnee->label : 'Toutes périodes' }}</span>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="metric-card metric-warning">
                <div class="metric-label">Rapports à créer</div>
                <div class="metric-value">{{ $rapportEnAttente }}</div>
                <div class="metric-meta">
                    <span>{{ $periodeSelectionnee ? $periodeSelectionnee->label : 'Toutes périodes' }}</span>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="metric-card">
                <div class="metric-label">Page actuelle</div>
                <div class="metric-value">{{ $contrats->count() }}</div>
                <div class="metric-meta"><span>Élèves affichés</span></div>
            </div>
        </div>

    </div>

    {{-- FILTRE PÉRIODE + RECHERCHE --}}
    <div class="panel mb-4">
        <div class="panel-header">
            <div>
                <h5 class="mb-0">Filtres</h5>
                <p class="text-muted mb-0">Sélectionner une période et filtrer les résultats</p>
            </div>
        </div>

        <div class="panel-body">
            <form method="GET" action="{{ route('rapports-mensuels.index') }}">
                <div class="row g-3">
                    <div class="col-lg-5">
                        <label class="form-label fw-bold">Période comptable</label>
                        <select name="periode_id" class="form-select">
                            @forelse($periodes as $periode)
                                <option value="{{ $periode->id }}"
                                    {{ $periodeSelectionnee?->id === $periode->id ? 'selected' : '' }}>
                                    {{ $periode->label }}
                                    @if($periode->statut === 'ouverte')
                                        — ouverte
                                    @endif
                                </option>
                            @empty
                                <option disabled>Aucune période disponible</option>
                            @endforelse
                        </select>
                    </div>

                    <div class="col-lg-5">
                        <label class="form-label fw-bold">Recherche élève</label>
                        <input type="text"
                               name="search"
                               value="{{ request('search') }}"
                               class="form-control"
                               placeholder="Nom de l'élève...">
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
                <h5 class="mb-0">Liste des élèves</h5>
                <p class="text-muted mb-0">
                    @if($periodeSelectionnee)
                        Période : {{ $periodeSelectionnee->label }}
                    @else
                        Sélectionnez une période pour commencer
                    @endif
                </p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Élève</th>
                        <th>Matière</th>
                        <th>Enseignant</th>
                        <th>Volume horaire</th>
                        <th>Statut</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>

                <tbody>
                @forelse($contrats as $contrat)
                    @php
                        $rapport = $rapportsParContrat->get($contrat->id);
                    @endphp
                    <tr>
                        <td>
                            <strong>
                                {{ $contrat->eleve->user->prenom }}
                                {{ $contrat->eleve->user->nom }}
                            </strong>
                        </td>

                        <td>
                            <span class="badge text-bg-light">
                                {{$contrat->affectations->first()?->matiere?->nom }}
                            </span>
                        </td>

                        <td>
                            @php
                                $affectation = $contrat->affectations->firstWhere('enseignant_id', auth()->user()->enseignantProfil->id);
                            @endphp

                            {{ $affectation?->enseignant?->user?->prenom }}
                            {{ $affectation?->enseignant?->user?->nom }}
                        </td>
                        <td>
                            @if($rapport)
                                <strong>
                                    {{ number_format($rapport->volume_horaire_cumule, 1) }} h
                                </strong>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>

                        <td>
                            @if($rapport)
                                <span class="badge text-bg-success">
                                    <i class="bi bi-check-circle"></i>
                                    Créé
                                </span>
                            @else
                                <span class="badge text-bg-warning">
                                    <i class="bi bi-clock"></i>
                                    À créer
                                </span>
                            @endif
                        </td>

                        <td class="text-end">

                            <div class="btn-group">

                                @if($rapport)

                                    <a href="{{ route('rapports-mensuels.show', $rapport) }}"
                                       class="btn btn-sm btn-light"
                                       title="Voir">

                                        <i class="bi bi-eye"></i>

                                    </a>

                                    <a href="{{ route('rapports-mensuels.edit', $rapport) }}"
                                       class="btn btn-sm btn-light"
                                       title="Modifier">

                                        <i class="bi bi-pencil"></i>

                                    </a>

                                    <a href="{{ route('rapports-mensuels.pdf.download', $rapport) }}"
                                       class="btn btn-sm btn-light"
                                       title="PDF">

                                        <i class="bi bi-file-earmark-pdf"></i>

                                    </a>

                                @else

                                    @if($periodeSelectionnee)
                                        <a href="{{ route('rapports-mensuels.create', $contrat) }}"
                                           class="btn btn-sm btn-primary">

                                            <i class="bi bi-plus-circle"></i>
                                            Créer

                                        </a>
                                    @else
                                        <span class="text-muted" title="Sélectionnez d'abord une période">
                                            <i class="bi bi-arrow-up-circle"></i>
                                            Sélectionnez une période
                                        </span>
                                    @endif

                                @endif

                            </div>

                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="6">

                            <div class="blank-state">

                                <i class="bi bi-file-earmark-x display-5 text-muted"></i>

                                <p class="mt-3 text-muted mb-0">
                                    Aucun élève trouvé.
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
        {{ $contrats->links() }}
    </div>

</div>

@endsection
