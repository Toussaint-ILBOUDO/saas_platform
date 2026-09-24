@extends('panel.layouts.app')

@section('title', 'Détail enseignant')

@section('content')

<div class="container-fluid">

    <div class="page-heading">

        <div class="page-heading-copy">

            <div class="page-icon">
                <i class="bi bi-person-workspace"></i>
            </div>

            <div>
                <h1 class="mb-0">{{ $enseignant->nom }} {{ $enseignant->prenom }}</h1>
                <p class="text-muted mb-0">
                    Détails du compte enseignant
                </p>
            </div>

        </div>

    </div>

    {{-- Informations générales --}}
    <div class="panel mb-4">

        <div class="panel-header">
            <h5 class="mb-0">Informations générales</h5>
        </div>

        <div class="p-4">

            <p><strong>Nom :</strong> {{ $enseignant->nom }}</p>
            <p><strong>Prénom :</strong> {{ $enseignant->prenom }}</p>
            <p><strong>Email :</strong> {{ $enseignant->email ?? '-' }}</p>
            <p><strong>WhatsApp :</strong> {{ $enseignant->telephone_whatsapp ?? '-' }}</p>
            <p><strong>Téléphone :</strong> {{ $enseignant->telephone_appel ?? '-' }}</p>

        </div>

    </div>

    {{-- Profil enseignant --}}
    <div class="panel mb-4">

        <div class="panel-header">
            <h5 class="mb-0">Profil enseignant</h5>
        </div>

        <div class="p-4">

            <p><strong>Numéro Orange Money :</strong> {{ $enseignant->enseignantProfil?->numero_orange_money ?? '-' }}</p>

            <p><strong>Diplôme maximum :</strong>
                {{ $enseignant->enseignantProfil?->diplome_max ?? '-' }}
            </p>

            <p><strong>Lieu de service :</strong>
                {{ $enseignant->enseignantProfil?->lieu_de_service ?? '-' }}
            </p>

            <p><strong>Domicile :</strong>
                {{ $enseignant->enseignantProfil?->domicile ?? '-' }}
            </p>

            <p><strong>Frais annuel réglé :</strong>
                @if($enseignant->enseignantProfil?->frais_annuel_regle)
                    <span class="badge text-bg-success">Oui</span>
                @else
                    <span class="badge text-bg-secondary">Non</span>
                @endif
            </p>

        </div>

    </div>

    {{-- Matières compétentes --}}
    <div class="panel mb-4">

        <div class="panel-header">
            <h5 class="mb-0">Matières compétentes</h5>
        </div>

        <div class="p-4">

            @if($enseignant->enseignantProfil && $enseignant->enseignantProfil->matieres->count())

                <div class="d-flex flex-wrap gap-2">

                    @foreach($enseignant->enseignantProfil->matieres as $matiere)

                        <span class="badge text-bg-primary fs-6">
                            {{ $matiere->nom }}
                        </span>

                    @endforeach

                </div>

            @else

                <p class="text-muted mb-0">
                    Aucune matièreassignée
                </p>

            @endif

        </div>

    </div>

    <div class="d-flex justify-content-end gap-2">

        <a href="{{ route('enseignants.index') }}"
        class="btn btn-light">

            <i class="bi bi-arrow-left"></i>
            Retour

        </a>

        <a href="{{ route('enseignants.edit', $enseignant) }}"
        class="btn btn-warning">

            <i class="bi bi-pencil"></i>
            Modifier

        </a>

    </div>

</div>

@endsection
