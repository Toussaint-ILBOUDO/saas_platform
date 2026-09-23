@extends('panel.layouts.app')

@section('title', 'Cahiers de texte')

@section('content')

<div class="container-fluid">

    <div class="page-heading">

        <div class="page-heading-copy">

            <div class="page-icon">
                <i class="bi bi-journal-richtext"></i>
            </div>

            <div>

                <h1 class="mb-0">
                    Cahiers de texte
                </h1>

                <p class="text-muted mb-0">
                    Suivi pédagogique des séances
                </p>

            </div>

        </div>

        <div class="heading-actions d-flex gap-2">

            {{-- PDF EXPORT --}}
            {{-- <a href="{{ route('cahiers-textes.pdf.create', $eleve) }}" class="btn btn-light">
                <i class="bi bi-file-earmark-pdf"></i>
                Télécharger PDF
            </a> --}}

            @can('create', App\Models\CahierTexte::class)

                <a href="{{ route('cahiers-textes.create.select-eleve') }}"
                   class="btn btn-primary">

                    Nouvelle séance

                </a>

            @endcan

        </div>

    </div>

    {{-- KPI --}}

    <div class="row g-3 mb-4">

        <div class="col-lg-3 col-md-6">

            <div class="metric-card metric-primary">
                <div class="metric-label">Séances</div>
                <div class="metric-value">{{ $cahiers->total() }}</div>
            </div>

        </div>

        <div class="col-lg-3 col-md-6">

            <div class="metric-card metric-success">
                <div class="metric-label">Heures réalisées</div>
                <div class="metric-value">
                    {{ number_format($cahiers->sum('duree_heures'),1) }}
                </div>
            </div>

        </div>

        <div class="col-lg-3 col-md-6">

            <div class="metric-card metric-warning">
                <div class="metric-label">Cette page</div>
                <div class="metric-value">{{ $cahiers->count() }}</div>
            </div>

        </div>

        <div class="col-lg-3 col-md-6">

            <div class="metric-card">
                <div class="metric-label">Dernière séance</div>
                <div class="metric-value">
                    {{ $cahiers->first()?->date_seance?->format('d/m') ?? '-' }}
                </div>
            </div>

        </div>

    </div>

    {{-- FILTRES --}}

    <div class="panel mb-4">

        <div class="panel-body">

            <form method="GET">

                <div class="row g-3">

                    <div class="col-lg-8">

                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            value="{{ request('search') }}"
                            placeholder="Rechercher un élève, une matière ou un contenu..."
                        >

                    </div>

                    <div class="col-lg-4">

                        <button class="btn btn-primary w-100">

                            <i class="bi bi-search"></i>
                            Rechercher

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

                <h5 class="mb-0">
                    Journal pédagogique
                </h5>

                <p class="text-muted mb-0">
                    Historique des séances
                </p>

            </div>

        </div>

        <div class="table-responsive">

            <table class="table align-middle">

                <thead>

                <tr>

                    <th>Date</th>
                    <th>Élève</th>
                    <th>Matière</th>
                    <th>Enseignant</th>
                    <th>Durée</th>
                    <th>Résumé</th>
                    <th class="text-end">Actions</th>

                </tr>

                </thead>

                <tbody>

                @forelse($cahiers as $cahier)

                    <tr>

                        <td>

                            <strong>
                                {{ $cahier->date_seance->format('d/m/Y') }}
                            </strong>

                            <div class="small text-muted">
                                {{ substr($cahier->heure_debut,0,5) }}
                                -
                                {{ substr($cahier->heure_fin,0,5) }}
                            </div>

                        </td>

                        <td>
                            <strong>
                                {{ $cahier->affectation->contrat->eleve->user->prenom }}
                                {{ $cahier->affectation->contrat->eleve->user->nom }}
                            </strong>
                        </td>

                        <td>
                            <span class="badge text-bg-light">
                                {{ $cahier->affectation->matiere->nom }}
                            </span>
                        </td>

                        <td>
                            {{ $cahier->affectation->enseignant->user->prenom }}
                            {{ $cahier->affectation->enseignant->user->nom }}
                        </td>

                        <td>
                            <strong>
                                {{ number_format($cahier->duree_heures,1) }} h
                            </strong>
                        </td>

                        <td>
                            {{ Str::limit($cahier->contenu_cours, 80) }}
                        </td>

                        <td class="text-end">

                            <div class="btn-group">

                                <a href="{{ route('cahiers-textes.show', $cahier) }}"
                                   class="btn btn-sm btn-light">
                                    <i class="bi bi-eye"></i>
                                </a>

                                @can('update', $cahier)

                                    <a href="{{ route('cahiers-textes.edit', $cahier) }}"
                                       class="btn btn-sm btn-light">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                @endcan

                                <a href="{{ route('cahiers-textes.pdf.download', $cahier) }}" class="btn btn-light">
                                    <i class="bi bi-file-earmark-pdf"></i>
                                </a>

                            </div>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="7">

                            <div class="blank-state">

                                <i class="bi bi-journal-x display-5 text-muted"></i>

                                <p class="mt-3 text-muted">
                                    Aucun cahier de texte trouvé
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
        {{ $cahiers->links() }}
    </div>

</div>

@endsection