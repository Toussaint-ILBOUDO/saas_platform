@extends('panel.layouts.app')

@section('title', 'Détail parent')

@section('content')

<div class="container-fluid">

    <div class="page-heading">

        <div class="page-heading-copy">

            <div class="page-icon">
                <i class="bi bi-people"></i>
            </div>

            <div>
                <h1 class="mb-0">{{ $parent->nom }} {{ $parent->prenom }}</h1>
                <p class="text-muted mb-0">
                    Détails du compte parent
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

            <p><strong>Nom :</strong> {{ $parent->nom }}</p>
            <p><strong>Prénom :</strong> {{ $parent->prenom }}</p>
            <p><strong>Email :</strong> {{ $parent->email ?? '-' }}</p>
            <p><strong>WhatsApp :</strong> {{ $parent->telephone_whatsapp ?? '-' }}</p>
            <p><strong>Téléphone :</strong> {{ $parent->telephone_appel ?? '-' }}</p>

        </div>

    </div>

    {{-- Profil parent --}}
    <div class="panel mb-4">

        <div class="panel-header">
            <h5 class="mb-0">Profil parent</h5>
        </div>

        <div class="p-4">

            <p><strong>Profession :</strong> {{ $parent->parentProfil?->profession ?? '-' }}</p>

            <p><strong>Nombre d'enfants :</strong>
                {{ $parent->parentProfil?->nombre_enfants ?? '-' }}
            </p>

            <p><strong>Adresse :</strong></p>
            <p class="text-muted">
                {{ $parent->parentProfil?->adresse_domicile ?? '-' }}
            </p>

        </div>

    </div>

    {{-- Enfants --}}
    <div class="panel mb-4">

        <div class="panel-header">
            <h5 class="mb-0">Enfants</h5>
        </div>

        <div class="p-4">

            @if($parent->enfants && $parent->enfants->count() > 0)

                <ul class="list-group">

                    @foreach($parent->enfants as $enfant)

                        <li class="list-group-item d-flex justify-content-between align-items-center">

                            <span>
                                {{ $enfant->nom }} {{ $enfant->prenom }}
                            </span>

                            <span class="text-muted">
                                Classe : {{ $enfant->classe_id ?? '-' }}
                            </span>

                        </li>

                    @endforeach

                </ul>

            @else

                <p class="text-muted mb-0">
                    Aucun enfant enregistré
                </p>

            @endif

        </div>

    </div>

    <div class="d-flex justify-content-end gap-2">

        <a href="{{ route('parents.index') }}"
        class="btn btn-light">

            <i class="bi bi-arrow-left"></i>
            Retour

        </a>

        <a href="{{ route('parents.edit', $parent) }}"
        class="btn btn-warning">

            <i class="bi bi-pencil"></i>
            Modifier

        </a>

    </div>

</div>

@endsection