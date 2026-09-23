@extends('panel.layouts.app')

@section('title', 'Modifier un créneau')

@section('content')

<div class="container-fluid">

    <div class="page-heading">

        <div class="page-heading-copy">

            <div class="page-icon">
                <i class="bi bi-calendar-week"></i>
            </div>

            <div>

                <h1 class="mb-0">
                    Modifier le créneau
                </h1>

                <p class="text-muted mb-0">
                    <a href="{{ route('planning-enseignant.index') }}" class="text-decoration-none">
                        <i class="bi bi-arrow-left me-1"></i>
                        Retour à mon planning
                    </a>
                </p>

            </div>

        </div>

    </div>

    <div class="row justify-content-center">

        <div class="col-lg-6">

            <div class="panel p-4">

                <h2 class="h5 mb-3">
                    {{ $creneau->affectation->matiere->nom }}
                    —
                    {{ $creneau->affectation->contrat->eleve?->user?->prenom }}
                    {{ $creneau->affectation->contrat->eleve?->user?->nom }}
                </h2>

                @include('pedagogie.planning-enseignant._form', [
                    'formAction' => route('planning-enseignant.update', $creneau),
                    'creneau' => $creneau,
                    'affectations' => $affectations,
                ])

            </div>

        </div>

    </div>

</div>

@endsection