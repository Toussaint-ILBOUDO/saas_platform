@extends('panel.layouts.app')

@section('title', 'Détail objectif pédagogique')

@section('content')

<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon">
                <i class="bi bi-bullseye"></i>
            </div>
            <div>
                <h1 class="mb-0">
                    Objectif — {{ $objectif->eleve?->user?->nom }} {{ $objectif->eleve?->user?->prenom }}
                </h1>
                <p class="text-muted mb-0">
                    {{ $objectif->periode?->label ?? '—' }}
                </p>
            </div>
        </div>
        <div class="heading-actions">
            <span class="badge text-bg-info fs-6">
                {{ $objectif->periode?->label ?? '—' }}
            </span>
        </div>
    </div>

    <div class="row g-4">

        <div class="col-lg-8">

            {{-- Infos générales --}}
            <div class="panel mb-4">
                <div class="panel-header">
                    <h5 class="mb-0">Informations générales</h5>
                </div>
                <div class="p-4">
                    <p><strong>Élève :</strong> {{ $objectif->eleve?->user?->nom }} {{ $objectif->eleve?->user?->prenom }}</p>
                    <p><strong>Classe :</strong> {{ $objectif->eleve?->classe?->nom ?? '-' }}</p>
                    <p><strong>Période :</strong> {{ $objectif->periode?->label ?? '—' }}</p>
                    <p><strong>Moyenne générale visée :</strong> {{ $objectif->moyenne_visee }}</p>
                    <p><strong>Moyenne générale obtenue :</strong> {{ $objectif->moyenne_obtenue ?? '—' }}</p>
                </div>
            </div>

            {{-- Matériel --}}
            <div class="panel mb-4">
                <div class="panel-header">
                    <h5 class="mb-0">Matériel pédagogique</h5>
                </div>
                <div class="p-4">
                    <p><strong>Disponible :</strong></p>
                    <p class="text-muted">{{ $objectif->materiel_disponible ?? '—' }}</p>

                    <p><strong>Manquant :</strong></p>
                    @if($objectif->materiel_manquant)
                        <div class="alert alert-warning">
                            {{ $objectif->materiel_manquant }}
                        </div>
                    @else
                        <p class="text-muted">Aucun</p>
                    @endif
                </div>
            </div>

            {{-- Matières --}}
            <div class="panel mb-4">
                <div class="panel-header">
                    <h5 class="mb-0">Objectifs par matière</h5>
                </div>
                <div class="p-4">
                    @if($objectif->objectifsMatieres->count())
                        <div class="table-responsive">
                            <table class="table">
                            <thead>
                            <tr>
                                <th>Matière</th>
                                <th>Moy. visée</th>
                                <th>Moy. obtenue</th>
                                <th>Commentaire</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($objectif->objectifsMatieres as $om)
                                <tr>
                                    <td>
                                        <strong>{{ $om->matiere?->nom ?? '-' }}</strong>
                                    </td>
                                    <td>{{ $om->moyenne_visee }}</td>
                                    <td>{{ $om->moyenne_obtenue ?? '—' }}</td>
                                    <td>{{ $om->commentaire ?? '—' }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">Aucune matière associée</p>
                    @endif
                </div>
            </div>

            {{-- Commentaire admin --}}
            @if($objectif->commentaire_admin)
                <div class="panel mb-4">
                    <div class="panel-header">
                        <h5 class="mb-0">Commentaire administration</h5>
                    </div>
                    <div class="p-4">
                        <div class="alert alert-info mb-0">
                            {{ $objectif->commentaire_admin }}
                        </div>
                    </div>
                </div>
            @endif

        </div>

        {{-- Sidebar actions --}}
        <div class="col-lg-4">
            <div class="panel">
                <div class="panel-header">
                    <h5 class="mb-0">Actions</h5>
                </div>
                <div class="p-3 d-grid gap-2">
                    @can('update', $objectif)
                        <a href="{{ route('objectifs-pedagogiques.edit', $objectif) }}"
                           class="btn btn-warning">
                            <i class="bi bi-pencil me-1"></i>
                            Modifier
                        </a>
                    @endcan
                    @can('delete', $objectif)
                        <form action="{{ route('objectifs-pedagogiques.destroy', $objectif) }}" method="POST"
                              onsubmit="return confirm('Supprimer cet objectif ?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger w-100">
                                <i class="bi bi-trash me-1"></i>
                                Supprimer
                            </button>
                        </form>
                    @endcan
                    <a href="{{ route('objectifs-pedagogiques.index') }}" class="btn btn-light">
                        <i class="bi bi-arrow-left me-1"></i>
                        Retour à la liste
                    </a>
                </div>
            </div>
        </div>

    </div>

</div>

@endsection
