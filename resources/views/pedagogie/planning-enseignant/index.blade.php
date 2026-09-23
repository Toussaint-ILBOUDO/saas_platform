@extends('panel.layouts.app')

@section('title', 'Mon planning')

@section('content')

@php
    use App\Modules\Pedagogie\Enums\PlanningJourSemaine;

    $mesCreneauxByDay = $mesCreneaux->groupBy('jour_semaine');
    $partagesByDay = $creneauxPartages->groupBy('jour_semaine');
@endphp

<div class="container-fluid">

    <div class="page-heading">

        <div class="page-heading-copy">

            <div class="page-icon">
                <i class="bi bi-calendar-week"></i>
            </div>

            <div>

                <h1 class="mb-0">
                    Mon planning
                </h1>

                <p class="text-muted mb-0">
                    Planifiez vos créneaux récurrents
                    (ex. tous les mercredis de 18h à 19h)
                </p>

            </div>

        </div>

    </div>

    <div class="row g-4">

        <div class="col-lg-4">

            <div class="panel p-4">

                <h2 class="h5 mb-3">
                    <i class="bi bi-calendar-plus me-1"></i>
                    Ajouter un créneau régulier
                </h2>

                @include('pedagogie.planning-enseignant._form', [
                    'formAction' => route('planning-enseignant.store'),
                    'creneau' => null,
                    'affectations' => $affectations,
                ])

            </div>

        </div>

        <div class="col-lg-8">

            <div class="panel mb-4">

                <div class="panel-header">
                    <div>
                        <h2 class="h5 mb-0">
                            <i class="bi bi-calendar-week me-1"></i>
                            Mes créneaux réguliers
                        </h2>
                        <p class="text-muted mb-0">
                            Ces créneaux sont visibles par les élèves, leurs parents
                            et les autres enseignants des mêmes élèves.
                        </p>
                    </div>
                </div>

                <div class="p-3">

                    @forelse($mesCreneauxByDay as $jour => $creneauxDuJour)

                        <h3 class="h6 fw-bold text-muted mt-3 mb-2">
                            {{ PlanningJourSemaine::label((int) $jour) }}
                        </h3>

                        <div class="row g-2">

                            @foreach($creneauxDuJour as $creneau)

                                <div class="col-md-6">

                                    <div class="d-flex align-items-center justify-content-between gap-2 p-3 border rounded bg-light bg-opacity-50">

                                        <div>

                                            <div class="fw-bold">
                                                {{ $creneau->affectation->matiere->nom }}
                                            </div>

                                            <div class="small">
                                                <i class="bi bi-person-badge me-1"></i>
                                                {{ $creneau->affectation->contrat->eleve?->user?->prenom }}
                                                {{ $creneau->affectation->contrat->eleve?->user?->nom }}
                                            </div>

                                            <div class="small text-muted">
                                                <i class="bi bi-clock me-1"></i>
                                                {{ $creneau->tranche_horaire }}
                                            </div>

                                        </div>

                                        <div class="d-flex flex-column gap-1">

                                            <a
                                                href="{{ route('planning-enseignant.edit', $creneau) }}"
                                                class="btn btn-sm btn-outline-primary"
                                                title="Modifier"
                                            >
                                                <i class="bi bi-pencil"></i>
                                            </a>

                                            <form
                                                method="POST"
                                                action="{{ route('planning-enseignant.destroy', $creneau) }}"
                                                onsubmit="return confirm('Supprimer ce créneau régulier ?');"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger w-100" title="Supprimer">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>

                                        </div>

                                    </div>

                                </div>

                            @endforeach

                        </div>

                    @empty

                        <div class="notif-empty">
                            <div class="notif-empty-icon">
                                <i class="bi bi-calendar-week"></i>
                            </div>
                            <h5 class="notif-empty-title">
                                Aucun créneau planifié
                            </h5>
                            <p class="notif-empty-text text-muted mb-0">
                                Ajoutez votre premier créneau régulier via le formulaire.
                            </p>
                        </div>

                    @endforelse

                </div>

            </div>

            <div class="panel">

                <div class="panel-header">
                    <div>
                        <h2 class="h5 mb-0">
                            <i class="bi bi-people me-1"></i>
                            Créneaux des autres enseignants de mes élèves
                        </h2>
                        <p class="text-muted mb-0">
                            Pour une meilleure coordination, voici les cours réguliers
                            de vos élèves assurés par d'autres enseignants.
                        </p>
                    </div>
                </div>

                <div class="p-3">

                    @forelse($partagesByDay as $jour => $creneauxDuJour)

                        <h3 class="h6 fw-bold text-muted mt-3 mb-2">
                            {{ PlanningJourSemaine::label((int) $jour) }}
                        </h3>

                        <div class="row g-2">

                            @foreach($creneauxDuJour as $creneau)

                                <div class="col-md-6">

                                    <div class="p-3 border rounded bg-light bg-opacity-50">

                                        <div class="fw-bold">
                                            {{ $creneau->affectation->matiere->nom }}
                                            <span class="text-muted small fw-normal">
                                                — {{ $creneau->enseignant?->user?->prenom }}
                                                {{ $creneau->enseignant?->user?->nom }}
                                            </span>
                                        </div>

                                        <div class="small">
                                            <i class="bi bi-person-badge me-1"></i>
                                            {{ $creneau->affectation->contrat->eleve?->user?->prenom }}
                                            {{ $creneau->affectation->contrat->eleve?->user?->nom }}
                                        </div>

                                        <div class="small text-muted">
                                            <i class="bi bi-clock me-1"></i>
                                            {{ $creneau->tranche_horaire }}
                                        </div>

                                    </div>

                                </div>

                            @endforeach

                        </div>

                    @empty

                        <div class="notif-empty">
                            <div class="notif-empty-icon">
                                <i class="bi bi-people"></i>
                            </div>
                            <h5 class="notif-empty-title">
                                Aucun créneau partagé
                            </h5>
                            <p class="notif-empty-text text-muted mb-0">
                                Les créneaux des autres enseignants de vos élèves apparaîtront ici.
                            </p>
                        </div>

                    @endforelse

                </div>

            </div>

        </div>

    </div>

</div>

@endsection