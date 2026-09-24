@extends('panel.layouts.app')

@section('title', 'Planning')

@section('content')

<div class="container-fluid">

<div class="page-heading">

    <div class="page-heading-copy">

        <div class="page-icon">
            <i class="bi bi-calendar3"></i>
        </div>

        <div>

            <h1 class="mb-0">
                Planning
            </h1>

            <p class="text-muted mb-0">
                Vos séances de la semaine
            </p>

        </div>

    </div>

    <div class="heading-actions">

        <div class="d-flex gap-2">

            <a href="{{ route('planning.index', ['semaine' => $weekOffset - 1]) }}"
               class="btn btn-light">

                <i class="bi bi-chevron-left"></i>
                Semaine précédente

            </a>

            @if($weekOffset !== 0)

                <a href="{{ route('planning.index') }}"
                   class="btn btn-light">

                    <i class="bi bi-calendar-week"></i>
                    Semaine actuelle

                </a>

            @endif

            <a href="{{ route('planning.index', ['semaine' => $weekOffset + 1]) }}"
               class="btn btn-light">

                Semaine suivante
                <i class="bi bi-chevron-right"></i>

            </a>

        </div>

    </div>

</div>

<div class="panel mb-4">

    <div class="panel-header">

        <div>

            <h5 class="mb-0">
                Semaine du
                {{ $start->format('d/m/Y') }}
                au
                {{ $end->format('d/m/Y') }}
            </h5>

        </div>

    </div>

</div>

<div class="row g-3">

    @php
        $jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
        $today = \Carbon\Carbon::today();
    @endphp

    @foreach($weekDays as $index => $dayData)

        <div class="col-12 col-md-6 col-xl">

            <div class="panel h-100">

                <div class="panel-header">

                    <div>

                        <h6 class="mb-0">
                            {{ $jours[$index] }}
                        </h6>

                        <p class="text-muted mb-0">
                            {{ $dayData['date']->format('d/m/Y') }}
                        </p>

                    </div>

                    @if($dayData['date']->isSameDay($today))

                        <span class="badge text-bg-primary">
                            Aujourd'hui
                        </span>

                    @endif

                </div>

                <div class="p-3">

                    @forelse($dayData['sessions'] as $cahier)

                        <a href="{{ route('cahiers-textes.show', $cahier) }}"
                           class="d-block text-decoration-none mb-2 p-2 border rounded bg-light bg-opacity-50">

                            <div class="small fw-bold text-dark">
                                {{ $cahier->affectation->matiere->nom }}
                            </div>

                            @if(isset($isParent) && $isParent && $cahier->affectation->contrat?->eleve?->user)
                                <div class="small">
                                    <i class="bi bi-person-badge"></i>
                                    {{ $cahier->affectation->contrat->eleve->user->prenom }}
                                    {{ $cahier->affectation->contrat->eleve->user->nom }}
                                </div>
                            @endif

                            <div class="small text-muted">
                                <i class="bi bi-clock"></i>
                                {{ $cahier->heure_debut }}
                                →
                                {{ $cahier->heure_fin }}

                                @if($cahier->affectation->enseignant?->user)

                                    •
                                    {{ $cahier->affectation->enseignant->user->prenom }}
                                    {{ $cahier->affectation->enseignant->user->nom }}

                                @endif

                            </div>

                        </a>

                    @empty

                        <p class="text-muted small mb-0">
                            Aucune séance
                        </p>

                    @endforelse

                </div>

            </div>

        </div>

    @endforeach

</div>

@php
    $creneauxByDay = $creneaux->groupBy('jour_semaine');
    $joursSemaine = [1, 2, 3, 4, 5, 6, 7];
@endphp

<div class="panel mt-4">

    <div class="panel-header">

        <div>

            <h2 class="h5 mb-0">
                <i class="bi bi-calendar-week me-1"></i>
                Créneaux réguliers des enseignants
            </h2>

            <p class="text-muted mb-0">
                Cours planifiés chaque semaine (ex. tous les mercredis de 18h à 19h)
            </p>

        </div>

    </div>

</div>

<div class="row g-3 mt-1">

    @foreach($joursSemaine as $numJour)

        <div class="col-12 col-md-6 col-xl">

            <div class="panel h-100">

                <div class="panel-header">

                    <div>

                        <h6 class="mb-0">
                            {{ \App\Modules\Pedagogie\Enums\PlanningJourSemaine::label($numJour) }}
                        </h6>

                    </div>

                </div>

                <div class="p-3">

                    @forelse(($creneauxByDay->get($numJour) ?? collect()) as $creneau)

                        <div class="small mb-2 p-2 border rounded bg-light bg-opacity-50">

                            <div class="fw-bold text-dark">
                                {{ $creneau->affectation->matiere->nom }}
                            </div>

                            @if(isset($isParent) && $isParent && $creneau->affectation->contrat?->eleve?->user)
                                <div class="small">
                                    <i class="bi bi-person-badge"></i>
                                    {{ $creneau->affectation->contrat->eleve->user->prenom }}
                                    {{ $creneau->affectation->contrat->eleve->user->nom }}
                                </div>
                            @endif

                            <div class="small text-muted">
                                <i class="bi bi-clock"></i>
                                {{ $creneau->heure_debut }}
                                →
                                {{ $creneau->heure_fin }}

                                @if($creneau->enseignant?->user)

                                    •
                                    {{ $creneau->enseignant->user->prenom }}
                                    {{ $creneau->enseignant->user->nom }}

                                @endif

                            </div>

                        </div>

                    @empty

                        <p class="text-muted small mb-0">
                            Aucun créneau régulier
                        </p>

                    @endforelse

                </div>

            </div>

        </div>

    @endforeach

</div>

</div>

@endsection