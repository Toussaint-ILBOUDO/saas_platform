@extends('landlord.layouts.app')

@section('title', $cabinet->nom)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold mb-0">{{ $cabinet->nom }}
            <code class="fs-6 text-secondary">{{ $cabinet->id }}</code>
        </h1>
        <div class="d-flex gap-2">
            <a href="{{ route('landlord.cabinets.index') }}" class="btn btn-outline-secondary">Retour</a>
            <a href="{{ route('landlord.cabinets.edit', $cabinet) }}" class="btn btn-primary">
                <i class="bi bi-pencil me-1"></i>Modifier
            </a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Informations</div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-secondary">Statut</span>
                        @if ($cabinet->status === 'actif')
                            <span class="badge text-bg-success">Actif</span>
                        @else
                            <span class="badge text-bg-danger">Suspendu</span>
                        @endif
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-secondary">Sous-domaine</span>
                        <span>{{ $cabinet->sous_domaine ?: '—' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-secondary">Domaine</span>
                        <span class="font-monospace">{{ $cabinet->primary_domain ?: '—' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-secondary">Email</span>
                        <span>{{ $cabinet->email ?: '—' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-secondary">Téléphone</span>
                        <span>{{ $cabinet->telephone ?: '—' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-secondary">Base de données</span>
                        <span class="font-monospace">cabinet_{{ $cabinet->id }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-secondary">Créé le</span>
                        <span>{{ $cabinet->created_at?->format('d/m/Y H:i') ?: '—' }}</span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="col-lg-7">
            <form method="POST" action="{{ route('landlord.cabinets.parametres.update', $cabinet) }}" class="card shadow-sm">
                @csrf
                @method('PUT')

                <div class="card-header bg-white fw-semibold">Tarifs & fonctionnalités actives</div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Abonnement mensuel (FCFA)</label>
                            <input type="number" step="0.01" min="0" name="tarif_abonnement"
                                   value="{{ old('tarif_abonnement', $cabinet->parametres?->tarif_abonnement ?? 0) }}"
                                   class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tarif par élève (FCFA)</label>
                            <input type="number" step="0.01" min="0" name="tarif_par_eleve"
                                   value="{{ old('tarif_par_eleve', $cabinet->parametres?->tarif_par_eleve ?? 0) }}"
                                   class="form-control" required>
                        </div>
                    </div>

                    <label class="form-label">Fonctionnalités actives</label>
                    <div class="row">
                        @foreach ($modules as $code => $libelle)
                            <div class="col-md-6 form-check">
                                <input class="form-check-input" type="checkbox" name="fonctionnalites_activees[]"
                                       value="{{ $code }}" id="module-{{ $code }}"
                                       @checked(in_array($code, old('fonctionnalites_activees', $cabinet->parametres?->fonctionnalites_activees ?? []), true))>
                                <label class="form-check-label" for="module-{{ $code }}">{{ $libelle }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="card-footer d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
@endsection