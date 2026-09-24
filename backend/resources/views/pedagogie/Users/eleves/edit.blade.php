@extends('panel.layouts.app')

@section('title', 'Modifier élève')

@section('content')

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
                <h1 class="mb-0">Modifier élève</h1>
                <p class="text-muted mb-0">
                    Mise à jour du compte élève
                </p>
            </div>

        </div>
    </div>

    {{-- =========================
        FORM
    ========================== --}}
    <form action="{{ route('eleves.update', $eleve->id) }}" method="POST">
        @csrf
        @method('PUT')

        {{-- =========================
            IDENTITÉ UTILISATEUR
        ========================== --}}
        <div class="panel mb-4">

            <div class="panel-header">
                <h5 class="mb-0">Informations générales</h5>
            </div>

            <div class="p-4">

                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label">Nom</label>
                        <input type="text"
                               name="nom"
                               value="{{ old('nom', $eleve->user->nom ?? '') }}"
                               class="form-control @error('nom') is-invalid @enderror">

                        @error('nom')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Prénom</label>
                        <input type="text"
                               name="prenom"
                               value="{{ old('prenom', $eleve->user->prenom ?? '') }}"
                               class="form-control @error('prenom') is-invalid @enderror">

                        @error('prenom')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Téléphone WhatsApp</label>
                        <input type="text"
                               name="telephone_whatsapp"
                               value="{{ old('telephone_whatsapp', $eleve->user->telephone_whatsapp ?? '') }}"
                               class="form-control">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Téléphone appel</label>
                        <input type="text"
                               name="telephone_appel"
                               value="{{ old('telephone_appel', $eleve->user->telephone_appel ?? '') }}"
                               class="form-control">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email"
                               name="email"
                               value="{{ old('email', $eleve->user->email ?? '') }}"
                               class="form-control">
                    </div>

                </div>

            </div>
        </div>

        {{-- =========================
            SCOLARITÉ
        ========================== --}}
        <div class="panel mb-4">

            <div class="panel-header">
                <h5 class="mb-0">Scolarité</h5>
            </div>

            <div class="p-4">

                <div class="row g-3">

                    {{-- Parent --}}
                    <div class="col-md-6">
                        <label class="form-label">Parent</label>

                        <select name="parent_id" class="form-control">
                            <option value="">-- Choisir un parent --</option>

                            @foreach($parents as $parent)
                                <option value="{{ $parent->id }}"
                                    {{ old('parent_id', $eleve->parent_id) == $parent->id ? 'selected' : '' }}>
                                    {{ $parent->nom }} {{ $parent->prenom }}
                                </option>
                            @endforeach
                        </select>

                        @error('parent_id')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Classe (NOM, pas ID) --}}
                    <div class="col-md-6">
                        <label class="form-label">Classe</label>

                        <select name="classe_id" class="form-control">
                            <option value="">-- Choisir une classe --</option>

                            @foreach($classes as $classe)
                                <option value="{{ $classe->id }}"
                                    {{ old('classe_id', $eleve->classe_id) == $classe->id ? 'selected' : '' }}>
                                    {{ $classe->nom }}
                                </option>
                            @endforeach
                        </select>

                        @error('classe_id')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- École --}}
                    <div class="col-md-6">
                        <label class="form-label">École</label>
                        <input type="text"
                               name="ecole"
                               value="{{ old('ecole', $eleve->ecole) }}"
                               class="form-control">
                    </div>

                    {{-- Date naissance --}}
                    <div class="col-md-6">
                        <label class="form-label">Date de naissance</label>
                        <input type="date"
                               name="date_naissance"
                               value="{{ old('date_naissance', $eleve->date_naissance) }}"
                               class="form-control">
                    </div>

                    {{-- Lieu naissance --}}
                    <div class="col-12">
                        <label class="form-label">Lieu de naissance</label>
                        <input type="text"
                               name="lieu_naissance"
                               value="{{ old('lieu_naissance', $eleve->lieu_naissance) }}"
                               class="form-control">
                    </div>

                </div>

            </div>
        </div>


        @if(!$eleve->statut)

        <div class="panel mb-4">

            <div class="panel-header">
                <h5 class="mb-0">
                    Compte de connexion
                </h5>
            </div>

            <div class="p-4">

                <div class="alert alert-warning">
                    Cet élève ne peut pas encore se connecter à la plateforme.
                </div>

                <a href="{{ route('eleves.account.form', $eleve) }}"
                class="btn btn-primary">

                    <i class="bi bi-person-plus"></i>
                    Activer le compte
                </a>

            </div>

        </div>

        @else

        <div class="panel mb-4">

            <div class="panel-header">
                <h5 class="mb-0">
                    Compte de connexion
                </h5>
            </div>

            <div class="p-4">

                <div class="alert alert-success">
                    ✔ L'élève peut se connecter à la plateforme
                </div>

            </div>

        </div>

        @endif

        {{-- =========================
            SUBMIT
        ========================== --}}
        <div class="d-flex justify-content-end gap-2">

            <a href="{{ route('eleves.index') }}"
            class="btn btn-light">

                <i class="bi bi-x-circle"></i>
                Annuler

            </a>

            <button type="submit"
                    class="btn btn-primary">

                <i class="bi bi-check-circle"></i>
                Mettre à jour

            </button>

        </div>

    </form>

</div>

@endsection