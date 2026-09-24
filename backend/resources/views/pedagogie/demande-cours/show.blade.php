@extends('panel.layouts.app')

@section('title', 'Détail demande de cours')

@section('content')

<div class="container-fluid">

    <!-- HEADER -->
    <div class="page-heading">

        <div class="page-heading-copy">

            <div class="page-icon">
                <i class="bi bi-journal-text"></i>
            </div>

            <div>
                <h1 class="mb-0">
                    Demande de cours
                </h1>

                <p class="text-muted mb-0">
                    Suivi et traitement de la demande
                </p>
            </div>

        </div>

        <div class="heading-actions">

            @if($demandeCours->statut === 'en_attente')
                <span class="badge text-bg-warning fs-6">
                    En attente
                </span>
            @else
                <span class="badge text-bg-success fs-6">
                    Traitée
                </span>
            @endif

        </div>

    </div>

    <div class="row g-4">

        <!-- COL GAUCHE -->
        <div class="col-lg-8">

            <!-- INFO PARENT -->
            <div class="panel mb-4">

                <div class="panel-header">
                    <h5 class="mb-0">Informations du parent</h5>
                </div>

                <div class="p-3">

                    <p class="mb-1">
                        <strong>Nom :</strong>
                        {{ $demandeCours->nom_parent }}
                    </p>

                    <p class="mb-1">
                        <strong>Prénom :</strong>
                        {{ $demandeCours->prenom_parent }}
                    </p>

                    <p class="mb-0">
                        <strong>Téléphone :</strong>
                        {{ $demandeCours->telephone }}
                    </p>

                </div>

            </div>

            <!-- INFO DEMANDE -->
            <div class="panel mb-4">

                <div class="panel-header">
                    <h5 class="mb-0">Détails de la demande</h5>
                </div>

                <div class="p-3">

                    <p class="mb-1">
                        <strong>Classe :</strong>
                        {{ $demandeCours->classe?->nom }}
                    </p>

                    <p class="mb-1">
                        <strong>Type de cours :</strong>
                        {{ $demandeCours->typeCours?->libelle }}
                    </p>

                    <p class="mb-1">
                        <strong>Volume horaire :</strong>
                        {{ $demandeCours->volume_horaire_estime }} h / semaine
                    </p>

                    <p class="mb-0">
                        <strong>Message :</strong><br>
                        {{ $demandeCours->message ?? '—' }}
                    </p>

                </div>

            </div>

            <!-- MATIÈRES -->
            <div class="panel">

                <div class="panel-header">
                    <h5 class="mb-0">Matières demandées</h5>
                </div>

                <div class="p-3">

                    @forelse($demandeCours->matieres as $matiere)

                        <span class="badge text-bg-primary me-1 mb-1">
                            {{ $matiere->nom }}
                        </span>

                    @empty

                        <p class="text-muted mb-0">
                            Aucune matière sélectionnée
                        </p>

                    @endforelse

                </div>

            </div>

        </div>

        <!-- COL DROITE (ACTIONS) -->
        <div class="col-lg-4">

            <div class="panel">

                <div class="panel-header">
                    <h5 class="mb-0">Actions</h5>
                </div>

                <div class="p-3 d-grid gap-2">

                    @if($demandeCours->statut === 'en_attente')

                        <button class="btn btn-primary" onclick="window.location='{{ route('parents.create') }}'">
                            <i class="bi bi-person-plus me-1"></i>
                            Créer parent
                        </button>

                        <button class="btn btn-primary" onclick="window.location='{{ route('eleves.create') }}'">
                            <i class="bi bi-person-plus me-1"></i>
                            Créer un élève
                        </button>

                        <form action="{{ route('demande-cours.valider', $demandeCours) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-check-circle me-1"></i>
                                Marquer comme traitée
                            </button>
                        </form>

                    @else

                        <div class="alert alert-success mb-0">
                            Demande déjà traitée
                        </div>

                    @endif

                </div>

            </div>

        </div>

    </div>

</div>

@endsection