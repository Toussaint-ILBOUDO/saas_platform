@extends('panel.layouts.app')

@section('title', 'Nouveau type d\'ajustement')

@section('content')
<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon"><i class="bi bi-plus-circle"></i></div>
            <div>
                <h1 class="mb-0">Nouveau type d'ajustement</h1>
                <p class="text-muted mb-0">Créer un type de prime ou de retenue</p>
            </div>
        </div>

        <div class="heading-actions">
            <a href="{{ route('finance.type-ajustements.index') }}" class="btn btn-light">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="panel">
                <div class="panel-header">
                    <h5 class="mb-0">Informations</h5>
                </div>

                <div class="panel-body">
                    <form method="POST"
                          action="{{ route('finance.type-ajustements.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label fw-bold">Libellé</label>
                            <input type="text"
                                   name="libelle"
                                   value="{{ old('libelle') }}"
                                   class="form-control @error('libelle') is-invalid @enderror"
                                   placeholder="Ex: Prime de rendement, Retenue materiel..."
                                   required>
                            @error('libelle')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Sens</label>
                            <select name="direction"
                                    class="form-select @error('direction') is-invalid @enderror"
                                    required>
                                <option value="credit" {{ old('direction') === 'credit' ? 'selected' : '' }}>
                                    Crédit (+) — Ajoute au montant
                                </option>
                                <option value="debit" {{ old('direction') === 'debit' ? 'selected' : '' }}>
                                    Débit (-) — Retire du montant
                                </option>
                            </select>
                            @error('direction')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox"
                                       name="is_active"
                                       value="1"
                                       class="form-check-input"
                                       role="switch"
                                       {{ old('is_active', '1') === '1' ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold">Actif</label>
                            </div>
                            <small class="text-muted">
                                Les types inactifs n'apparaissent pas dans le formulaire d'ajustement.
                            </small>
                        </div>

                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle"></i> Créer
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
