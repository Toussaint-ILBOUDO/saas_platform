@extends('panel.layouts.app')

@section('title', 'Modifier enseignant')

@section('content')

<div class="container-fluid">

    <div class="page-heading">

        <div class="page-heading-copy">

            <div class="page-icon">
                <i class="bi bi-person-workspace"></i>
            </div>

            <div>
                <h1 class="mb-0">Modifier {{ $enseignant->nom }} {{ $enseignant->prenom }}</h1>
                <p class="text-muted mb-0">
                    Modification du compte enseignant
                </p>
            </div>

        </div>

    </div>

    <form action="{{ route('enseignants.update', $enseignant) }}" method="POST">

        @csrf
        @method('PUT')

        <div class="panel mb-4">

            <div class="panel-header">
                <h5 class="mb-0">
                    Informations générales
                </h5>
            </div>

            <div class="p-4">

                <div class="row g-3">

                    <div class="col-md-6">

                        <label class="form-label">
                            Nom
                        </label>

                        <input type="text"
                               name="nom"
                               value="{{ old('nom', $enseignant->nom) }}"
                               class="form-control @error('nom') is-invalid @enderror"
                               required>

                        @error('nom')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>

                    <div class="col-md-6">

                        <label class="form-label">
                            Prénom
                        </label>

                        <input type="text"
                               name="prenom"
                               value="{{ old('prenom', $enseignant->prenom) }}"
                               class="form-control @error('prenom') is-invalid @enderror"
                               required>

                        @error('prenom')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>

                    <div class="col-md-6">

                        <label class="form-label">
                            Téléphone WhatsApp
                        </label>

                        <input type="text"
                               name="telephone_whatsapp"
                               value="{{ old('telephone_whatsapp', $enseignant->telephone_whatsapp) }}"
                               class="form-control">

                    </div>

                    <div class="col-md-6">

                        <label class="form-label">
                            Téléphone appel
                        </label>

                        <input type="text"
                               name="telephone_appel"
                               value="{{ old('telephone_appel', $enseignant->telephone_appel) }}"
                               class="form-control">

                    </div>

                    <div class="col-md-6">

                        <label class="form-label">
                            Email
                        </label>

                        <input type="email"
                               name="email"
                               value="{{ old('email', $enseignant->email) }}"
                               class="form-control @error('email') is-invalid @enderror">

                        @error('email')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>

                    <div class="col-md-6">

                        <label class="form-label">
                            Mot de passe (laisser vide pour conserver)
                        </label>

                        <input type="password"
                               name="password"
                               class="form-control @error('password') is-invalid @enderror">

                        @error('password')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>

                </div>

            </div>

        </div>

        <div class="panel mb-4">

            <div class="panel-header">
                <h5 class="mb-0">
                    Profil enseignant
                </h5>
            </div>

            <div class="p-4">

                <div class="row g-3">

                    <div class="col-md-6">

                        <label class="form-label">
                            Numéro Orange Money
                        </label>

                        <input type="text"
                               name="numero_orange_money"
                               value="{{ old('numero_orange_money', $enseignant->enseignantProfil?->numero_orange_money) }}"
                               class="form-control">

                    </div>

                    <div class="col-md-6">

                        <label class="form-label">
                            Diplôme maximum
                        </label>

                        <input type="text"
                               name="diplome_max"
                               value="{{ old('diplome_max', $enseignant->enseignantProfil?->diplome_max) }}"
                               class="form-control">

                    </div>

                    <div class="col-md-6">

                        <label class="form-label">
                            Lieu de service
                        </label>

                        <input type="text"
                               name="lieu_de_service"
                               value="{{ old('lieu_de_service', $enseignant->enseignantProfil?->lieu_de_service) }}"
                               class="form-control">

                    </div>

                    <div class="col-md-6">

                        <label class="form-label">
                            Domicile
                        </label>

                        <input type="text"
                               name="domicile"
                               value="{{ old('domicile', $enseignant->enseignantProfil?->domicile) }}"
                               class="form-control">

                    </div>

                    <div class="col-md-6">

                        <div class="form-check mt-4">
                            <input type="hidden" name="frais_annuel_regle" value="0">
                            <input type="checkbox"
                                   name="frais_annuel_regle"
                                   value="1"
                                   class="form-check-input"
                                   {{ old('frais_annuel_regle', $enseignant->enseignantProfil?->frais_annuel_regle) ? 'checked' : '' }}>
                            <label class="form-check-label">
                                Frais annuel réglé
                            </label>
                        </div>

                    </div>

                </div>

            </div>

        </div>

        <div class="panel mb-4">

            <div class="panel-header">
                <h5 class="mb-0">
                    Matières compétentes
                </h5>
            </div>

            <div class="p-4">

                @php
                    $selectedMatieres = old('matieres', $enseignant->enseignantProfil?->matieres->pluck('id')->toArray() ?? []);
                @endphp

                <div class="row g-3">

                    @foreach($matieres as $matiere)

                        <div class="col-md-3">
                            <div class="form-check">
                                <input type="checkbox"
                                       name="matieres[]"
                                       value="{{ $matiere->id }}"
                                       class="form-check-input"
                                       id="matiere_{{ $matiere->id }}"
                                       {{ in_array($matiere->id, $selectedMatieres) ? 'checked' : '' }}>
                                <label class="form-check-label" for="matiere_{{ $matiere->id }}">
                                    {{ $matiere->nom }}
                                </label>
                            </div>
                        </div>

                    @endforeach

                </div>

            </div>

        </div>

        <div class="d-flex justify-content-end gap-2">

            <a href="{{ route('enseignants.index') }}"
            class="btn btn-light">

                <i class="bi bi-x-circle"></i>
                Annuler

            </a>

            <button type="submit"
                    class="btn btn-primary">

                <i class="bi bi-check-circle"></i>
                Enregistrer

            </button>

        </div>

    </form>

</div>

@endsection
