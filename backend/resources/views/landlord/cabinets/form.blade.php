@extends('landlord.layouts.app')

@section('title', $cabinet ? 'Modifier — ' . $cabinet->nom : 'Nouveau cabinet')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold mb-0">{{ $cabinet ? 'Modifier le cabinet' : 'Nouveau cabinet' }}</h1>
        @if ($cabinet)
            <a href="{{ route('landlord.cabinets.show', $cabinet) }}" class="btn btn-outline-secondary">Retour à la fiche</a>
        @endif
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $cabinet ? route('landlord.cabinets.update', $cabinet) : route('landlord.cabinets.store') }}" class="card shadow-sm">
        @csrf
        @method($cabinet ? 'PUT' : 'POST')

        <div class="card-body">
            @if (!$cabinet)
                <div class="mb-3">
                    <label class="form-label">Slug (identifiant du cabinet)</label>
                    <input type="text" name="id" value="{{ old('id') }}" class="form-control"
                           placeholder="ex. c1, cabinet-kodjovi" required>
                    <div class="form-text">Minuscules, chiffres et tirets. Sert aussi de nom de base de données.</div>
                </div>
            @endif

            <div class="mb-3">
                <label class="form-label">Nom du cabinet *</label>
                <input type="text" name="nom" value="{{ old('nom', $cabinet?->nom ?? '') }}" class="form-control" required>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Sous-domaine</label>
                    <input type="text" name="sous_domaine" value="{{ old('sous_domaine', $cabinet?->sous_domaine ?? '') }}"
                           class="form-control" placeholder="{{ $cabinet?->sous_domaine ?? 'c1' }}">
                    <div class="form-text">Domaine généré : {{ $cabinet?->sous_domaine ?: 'sous-domaine' }}.localhost (dev).</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Statut</label>
                    <select name="status" class="form-select">
                        <option value="actif" @selected(old('status', $cabinet?->status ?? 'actif') === 'actif')>$cabinet ? 'Actif' : 'Actif (par défaut)'</option>
                        <option value="suspendu" @selected(old('status', $cabinet?->status ?? '') === 'suspendu')>Suspendu</option>
                        <option value="archive" @selected(old('status', $cabinet?->status ?? '') === 'archive')>Archivé</option>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email de contact</label>
                    <input type="email" name="email" value="{{ old('email', $cabinet?->email ?? '') }}"
                           class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Téléphone</label>
                    <input type="text" name="telephone" value="{{ old('telephone', $cabinet?->telephone ?? '') }}"
                           class="form-control">
                </div>
            </div>
        </div>

        <div class="card-footer d-flex justify-content-end gap-2">
            <a href="{{ route('landlord.cabinets.index') }}" class="btn btn-link">Annuler</a>
            <button type="submit" class="btn btn-primary">
                {{ $cabinet ? 'Enregistrer' : 'Créer le cabinet' }}
            </button>
        </div>
    </form>

    @if (!$cabinet)
        <div class="alert alert-info mt-3 mb-0">
            <i class="bi bi-gear me-2"></i>
            À la création : la base <code>cabinet_&lt;slug&gt;</code> est provisionnée automatiquement
            (migrations tenant + jeux de données, D-027).
        </div>
    @endif
@endsection