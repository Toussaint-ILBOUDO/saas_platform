@extends('panel.layouts.app')

@section('title', 'Mes enfants')

@section('content')

<div class="container-fluid">

<div class="page-heading">

    <div class="page-heading-copy">

        <div class="page-icon">
            <i class="bi bi-heart"></i>
        </div>

        <div>

            <h1 class="mb-0">
                Mes enfants
            </h1>

            <p class="text-muted mb-0">
                Vos enfants scolarisés chez K'Educ
            </p>

        </div>

    </div>

</div>

@forelse($eleves as $eleve)

    <div class="panel mb-3">

        <div class="panel-body d-flex flex-wrap align-items-center justify-content-between gap-3">

            <div class="d-flex align-items-center gap-3">

                <div class="metric-icon rounded">
                    <i class="bi bi-person-badge"></i>
                </div>

                <div>

                    <h5 class="mb-0">
                        {{ $eleve->user?->prenom }}
                        {{ $eleve->user?->nom }}
                    </h5>

                    <p class="text-muted mb-0">
                        {{ $eleve->classe?->nom ?? 'Classe non définie' }}
                    </p>

                    @if($eleve->user)
                        <p class="text-muted small mb-0">
                            {{ $eleve->user->email }}
                        </p>
                    @endif

                </div>

            </div>

            <div class="d-flex gap-2">

                <a href="{{ route('eleves.fiche', $eleve) }}"
                   class="btn btn-light"
                   target="_blank">
                    <i class="bi bi-eye"></i> Fiche
                </a>

                <a href="{{ route('planning.index', ['eleve' => $eleve->id]) }}"
                   class="btn btn-light">
                    <i class="bi bi-calendar3"></i> Planning
                </a>

                <a href="{{ route('mes-factures.index', ['eleve' => $eleve->id]) }}"
                   class="btn btn-light">
                    <i class="bi bi-receipt"></i> Factures
                </a>

            </div>

        </div>

    </div>

@empty

    <div class="panel">

        <div class="panel-body text-center py-5">

            <i class="bi bi-heart fs-1 text-muted"></i>

            <p class="text-muted mt-3 mb-0">
                Aucun enfant enregistré pour votre compte parent.
            </p>

        </div>

    </div>

@endforelse

</div>

@endsection