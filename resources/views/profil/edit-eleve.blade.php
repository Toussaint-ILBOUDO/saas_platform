@extends('panel.layouts.app')

@section('title', 'Mon profil')

@section('content')

<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon">
                <i class="bi bi-person-badge"></i>
            </div>
            <div>
                <h1 class="mb-0">Mon profil</h1>
                <p class="text-muted mb-0">
                    Gérez vos informations personnelles
                </p>
            </div>
        </div>
    </div>

    <form action="{{ route('profil.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="panel mb-4">
            <div class="panel-header">
                <h5 class="mb-0">Informations générales</h5>
            </div>
            <div class="p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nom</label>
                        <input type="text" name="nom"
                               value="{{ old('nom', $user->nom) }}"
                               class="form-control @error('nom') is-invalid @enderror" required>
                        @error('nom')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Prénom</label>
                        <input type="text" name="prenom"
                               value="{{ old('prenom', $user->prenom) }}"
                               class="form-control @error('prenom') is-invalid @enderror" required>
                        @error('prenom')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Téléphone WhatsApp</label>
                        <input type="text" name="telephone_whatsapp"
                               value="{{ old('telephone_whatsapp', $user->telephone_whatsapp) }}"
                               class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Téléphone appel</label>
                        <input type="text" name="telephone_appel"
                               value="{{ old('telephone_appel', $user->telephone_appel) }}"
                               class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email"
                               value="{{ old('email', $user->email) }}"
                               class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Mot de passe (laisser vide pour conserver)</label>
                        <input type="password" name="password" class="form-control">
                    </div>
                </div>
            </div>
        </div>

        {{-- Infos élève (lecture seule) --}}
        <div class="panel mb-4">
            <div class="panel-header">
                <h5 class="mb-0">Informations scolaires</h5>
            </div>
            <div class="p-4">
                @if($user->eleve)
                    <p><strong>Classe :</strong> {{ $user->eleve->classe?->nom ?? '-' }}</p>
                    <p><strong>École :</strong> {{ $user->eleve->ecole ?? '-' }}</p>
                    <p><strong>Date de naissance :</strong> {{ $user->eleve->date_naissance ? \Carbon\Carbon::parse($user->eleve->date_naissance)->format('d/m/Y') : '-' }}</p>
                @else
                    <p class="text-muted mb-0">Aucune information scolaire disponible</p>
                @endif
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('dashboard') }}" class="btn btn-light">
                <i class="bi bi-x-circle"></i>
                Annuler
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-circle"></i>
                Enregistrer
            </button>
        </div>
    </form>

</div>

@endsection
