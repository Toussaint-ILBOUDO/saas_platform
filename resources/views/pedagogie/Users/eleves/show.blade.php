@extends('panel.layouts.app')

@section('title', 'Détail élève')

@section('content')

@php
    $isAdmin = auth()->user()->hasRole('admin') || auth()->user()->hasRole('super-admin');
    $retourRoute = $isAdmin ? route('eleves.index') : route('dashboard');
@endphp

<div class="container-fluid">

    {{-- =========================
        HEADER
    ========================== --}}
    <div class="page-heading">

        <div class="page-heading-copy">

            <div class="page-icon">
                <i class="bi bi-mortarboard"></i>
            </div>

            <div>

                <h1 class="mb-0">
                    {{ $eleve->user?->nom }}
                    {{ $eleve->user?->prenom }}
                </h1>

                <p class="text-muted mb-0">
                    Détails du profil élève
                </p>

            </div>

        </div>

    </div>

    {{-- =========================
        INFORMATIONS GÉNÉRALES
    ========================== --}}
    <div class="panel mb-4">

        <div class="panel-header">
            <h5 class="mb-0">
                Informations générales
            </h5>
        </div>

        <div class="p-4">

            <div class="row">

                <div class="col-md-6 mb-3">
                    <strong>Nom :</strong><br>
                    {{ $eleve->user?->nom ?? '-' }}
                </div>

                <div class="col-md-6 mb-3">
                    <strong>Prénom :</strong><br>
                    {{ $eleve->user?->prenom ?? '-' }}
                </div>

                <div class="col-md-6 mb-3">
                    <strong>Email :</strong><br>
                    {{ $eleve->user?->email ?? 'Compte non activé' }}
                </div>

                <div class="col-md-6 mb-3">
                    <strong>WhatsApp :</strong><br>
                    {{ $eleve->user?->telephone_whatsapp ?? '-' }}
                </div>

                <div class="col-md-6 mb-3">
                    <strong>Téléphone :</strong><br>
                    {{ $eleve->user?->telephone_appel ?? '-' }}
                </div>

            </div>

        </div>

    </div>

    {{-- =========================
        SCOLARITÉ
    ========================== --}}
    <div class="panel mb-4">

        <div class="panel-header">
            <h5 class="mb-0">
                Scolarité
            </h5>
        </div>

        <div class="p-4">

            <div class="row">

                <div class="col-md-6 mb-3">
                    <strong>Classe :</strong><br>
                    {{ $eleve->classe?->nom ?? '-' }}
                </div>

                <div class="col-md-6 mb-3">
                    <strong>École :</strong><br>
                    {{ $eleve->ecole ?? '-' }}
                </div>

                <div class="col-md-6 mb-3">
                    <strong>Parent responsable :</strong><br>
                    {{ $eleve->parent?->nom }}
                    {{ $eleve->parent?->prenom }}
                </div>

                <div class="col-md-6 mb-3">
                    <strong>Date de naissance :</strong><br>
                    {{ $eleve->date_naissance ?? '-' }}
                </div>

                <div class="col-md-12 mb-3">
                    <strong>Lieu de naissance :</strong><br>
                    {{ $eleve->lieu_naissance ?? '-' }}
                </div>

            </div>

        </div>

    </div>

    {{-- =========================
        INFORMATIONS COMPLÉMENTAIRES
    ========================== --}}
    <div class="panel mb-4">

        <div class="panel-header">
            <h5 class="mb-0">
                Informations complémentaires
            </h5>
        </div>

        <div class="p-4">

            <div class="row">

                <div class="col-md-6 mb-3">
                    <strong>Profession du père :</strong><br>
                    {{ $eleve->profession_pere ?? '-' }}
                </div>

                <div class="col-md-6 mb-3">
                    <strong>Profession de la mère :</strong><br>
                    {{ $eleve->profession_mere ?? '-' }}
                </div>

                <div class="col-md-6 mb-3">
                    <strong>Régime d'étude :</strong><br>
                    {{ $eleve->regime_etude ?? '-' }}
                </div>

                <div class="col-md-6 mb-3">
                    <strong>Religion :</strong><br>
                    {{ $eleve->religion_enfant ?? '-' }}
                </div>

                <div class="col-md-12 mb-3">
                    <strong>Maladies / Allergies :</strong><br>
                    {{ $eleve->maladies_allergies ?? '-' }}
                </div>

                <div class="col-md-12 mb-3">
                    <strong>Observations :</strong><br>
                    {{ $eleve->autres_observations ?? '-' }}
                </div>

            </div>

        </div>

    </div>

    {{-- =========================
        COMPTE UTILISATEUR
    ========================== --}}
    <div class="panel mb-4">

        <div class="panel-header">
            <h5 class="mb-0">
                Compte utilisateur
            </h5>
        </div>

        <div class="p-4">

            @if($eleve->user?->statut)

                <span class="badge text-bg-success">
                    Compte actif
                </span>

            @else

                <span class="badge text-bg-warning">
                    Compte non activé
                </span>

                @if($isAdmin)

                    <div class="mt-3">

                        <a
                            href="{{ route('eleves.account.form', $eleve) }}"
                            class="btn btn-primary"
                        >
                            <i class="bi bi-person-plus"></i>
                            Activer le compte
                        </a>

                    </div>

                @endif

            @endif

        </div>

    </div>

    {{-- ACTIONS --}}
    <div class="d-flex justify-content-end gap-2">

        <a href="{{ $retourRoute }}"
        class="btn btn-light">

            <i class="bi bi-arrow-left"></i>
            Retour

        </a>

        @if($isAdmin)

            <a href="{{ route('eleves.edit', $eleve) }}"
            class="btn btn-warning">

                <i class="bi bi-pencil"></i>
                Modifier

            </a>

        @endif

    </div>

</div>

@endsection