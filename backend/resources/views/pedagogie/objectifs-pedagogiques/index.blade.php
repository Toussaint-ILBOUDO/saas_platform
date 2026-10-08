@extends('panel.layouts.app')

@section('title', 'Objectifs pédagogiques')

@section('content')

<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon">
                <i class="bi bi-bullseye"></i>
            </div>
            <div>
                <h1 class="mb-0">Objectifs pédagogiques</h1>
                <p class="text-muted mb-0">
                    Suivi des objectifs par élève et par matière
                </p>
            </div>
        </div>
        <div class="heading-actions">
            @can('create', \App\Models\ObjectifPedagogique::class)
                <a href="{{ route('objectifs-pedagogiques.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i>
                    Nouvel objectif
                </a>
            @endcan
        </div>
    </div>

    {{-- Filtres --}}
    <div class="panel mb-4">
        <div class="p-3">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Rechercher</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                           class="form-control" placeholder="Nom de l'élève...">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Période</label>
                    <select name="periode_id" class="form-select">
                        <option value="">Toutes les périodes</option>
                        @foreach($periodes as $periode)
                            <option value="{{ $periode->id }}" @selected((int) request('periode_id') === $periode->id)>
                                {{ $periode->label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Filtrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Liste --}}
    <div class="panel">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                <tr>
                    <th>Élève</th>
                    <th>Classe</th>
                    <th>Période</th>
                    <th>Matières</th>
                    <th>Moy. visée</th>
                    <th>Moy. obtenue</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($objectifs as $objectif)
                    <tr>
                        <td>
                            <strong>
                                {{ $objectif->eleve?->user?->nom }}
                                {{ $objectif->eleve?->user?->prenom }}
                            </strong>
                        </td>
                        <td>{{ $objectif->eleve?->classe?->nom ?? '-' }}</td>
                        <td>
                            <span class="badge text-bg-info">
                                {{ $objectif->periode?->label ?? '—' }}
                            </span>
                        </td>
                        <td>
                            @foreach($objectif->objectifsMatieres->take(3) as $om)
                                <span class="badge text-bg-primary me-1">
                                    {{ $om->matiere?->sigle ?? $om->matiere?->nom }}
                                </span>
                            @endforeach
                            @if($objectif->objectifsMatieres->count() > 3)
                                <span class="badge text-bg-secondary">
                                    +{{ $objectif->objectifsMatieres->count() - 3 }}
                                </span>
                            @endif
                        </td>
                        <td>{{ $objectif->moyenne_visee }}</td>
                        <td>{{ $objectif->moyenne_obtenue ?? '—' }}</td>
                        <td class="text-end">
                            <a href="{{ route('objectifs-pedagogiques.show', $objectif) }}"
                               class="btn btn-sm btn-light">
                                <i class="bi bi-eye"></i>
                            </a>
                            @can('update', $objectif)
                                <a href="{{ route('objectifs-pedagogiques.edit', $objectif) }}"
                                   class="btn btn-sm btn-light">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="blank-panel">
                                <div class="blank-state">
                                    <i class="bi bi-inbox display-5 text-muted"></i>
                                    <p class="mt-2 text-muted">
                                        Aucun objectif pédagogique enregistré
                                    </p>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $objectifs->links() }}
        </div>
    </div>

</div>

@endsection
