@extends('panel.layouts.app')

@section('title', 'Ajouter un ajustement')

@section('content')
<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon"><i class="bi bi-plus-circle"></i></div>
            <div>
                <h1 class="mb-0">Ajouter un ajustement</h1>
                <p class="text-muted mb-0">
                    Bulletin {{ $bulletin->numero }} —
                    {{ $bulletin->enseignant?->user?->prenom }}
                    {{ $bulletin->enseignant?->user?->nom }}
                </p>
            </div>
        </div>

        <div class="heading-actions">
            <a href="{{ route('finance.bulletins-paie.show', $bulletin) }}" class="btn btn-light">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="panel">
                <div class="panel-header">
                    <h5 class="mb-0">Nouvel ajustement</h5>
                </div>

                <div class="panel-body">
                    <form method="POST"
                          action="{{ route('finance.bulletins-paie.ajustement.store', $bulletin) }}">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label fw-bold">Type d'ajustement</label>
                            <select name="type_ajustement_id"
                                    class="form-select @error('type_ajustement_id') is-invalid @enderror"
                                    required>
                                <option value="">-- Choisir un type --</option>
                                @foreach($typesAjustement as $type)
                                    <option value="{{ $type->id }}"
                                        {{ old('type_ajustement_id') == $type->id ? 'selected' : '' }}>
                                        {{ $type->libelle }}
                                        ({{ $type->direction === 'credit' ? 'Crédit (+)' : 'Débit (-)' }})
                                    </option>
                                @endforeach
                            </select>
                            @error('type_ajustement_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">
                                Le type détermine si l'ajustement est un ajout (crédit) ou une retenue (débit).
                            </small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Libellé</label>
                            <input type="text"
                                   name="libelle"
                                   value="{{ old('libelle') }}"
                                   class="form-control @error('libelle') is-invalid @enderror"
                                   placeholder="Ex: Prime déplacement, Retenue matériel..."
                                   required>
                            @error('libelle')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Montant (FCFA)</label>
                            <input type="number"
                                   name="montant"
                                   value="{{ old('montant') }}"
                                   class="form-control @error('montant') is-invalid @enderror"
                                   min="1"
                                   required>
                            @error('montant')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Ajouter
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
