@extends('panel.layouts.app')

@section('title', 'Nouveau paiement enseignant')

@section('content')
<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon"><i class="bi bi-wallet2"></i></div>
            <div>
                <h1 class="mb-0">Nouveau paiement enseignant</h1>
                <p class="text-muted mb-0">Générer le paiement des heures effectuées sur une période</p>
            </div>
        </div>

        <div class="heading-actions">
            <a href="{{ route('finance.paiements-enseignants.index') }}" class="btn btn-light">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle"></i>
            Veuillez corriger les erreurs ci-dessous.
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="panel">
                <div class="panel-header">
                    <h5 class="mb-0">Paramètres du paiement</h5>
                </div>

                <div class="panel-body">
                    <form method="POST"
                          action="{{ route('finance.paiements-enseignants.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label fw-bold">Enseignant</label>
                            <select name="enseignant_id"
                                    class="form-select @error('enseignant_id') is-invalid @enderror"
                                    required>
                                <option value="">Sélectionner un enseignant...</option>
                                @foreach($enseignants as $enseignant)
                                    <option value="{{ $enseignant->id }}"
                                        @selected(old('enseignant_id') == $enseignant->id)>
                                        {{ $enseignant->user?->prenom }}
                                        {{ $enseignant->user?->nom }}
                                        ({{ $enseignant->user?->email }})
                                    </option>
                                @endforeach
                            </select>
                            @error('enseignant_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Contrat (élève)</label>
                            <select name="contrat_cours_id"
                                    class="form-select @error('contrat_cours_id') is-invalid @enderror"
                                    required>
                                <option value="">Sélectionner un contrat actif...</option>
                                @foreach($contrats as $contrat)
                                    <option value="{{ $contrat->id }}"
                                        @selected(old('contrat_cours_id') == $contrat->id)>
                                        {{ $contrat->eleve?->user?->prenom }}
                                        {{ $contrat->eleve?->user?->nom }}
                                        — Contrat #{{ $contrat->id }}
                                    </option>
                                @endforeach
                            </select>
                            @error('contrat_cours_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Période comptable</label>
                            <select name="periode_id"
                                    class="form-select @error('periode_id') is-invalid @enderror"
                                    required>
                                <option value="">Sélectionner une période ouverte...</option>
                                @foreach($periodes as $periode)
                                    <option value="{{ $periode->id }}"
                                        @selected(old('periode_id') == $periode->id)>
                                        {{ $periode->label }}
                                        ({{ $periode->date_debut?->format('d/m/Y') }}
                                        → {{ $periode->date_fin?->format('d/m/Y') }})
                                    </option>
                                @endforeach
                            </select>
                            @error('periode_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Référence de transaction (optionnel)</label>
                            <input type="text"
                                   name="transaction_reference"
                                   value="{{ old('transaction_reference') }}"
                                   class="form-control @error('transaction_reference') is-invalid @enderror"
                                   placeholder="Ex: OM-28437, virement..."
                                   maxlength="255">
                            @error('transaction_reference')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check2-circle"></i> Générer le paiement
                            </button>
                            <a href="{{ route('finance.paiements-enseignants.index') }}"
                               class="btn btn-light">
                                Annuler
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="panel">
                <div class="panel-header">
                    <h5 class="mb-0">Comment ça marche ?</h5>
                </div>
                <div class="panel-body">
                    <ul class="small text-muted mb-0">
                        <li class="mb-2">
                            Le montant est calculé à partir des
                            <strong>heures de cours réellement effectuées</strong>
                            dans les cahiers de textes sur la période choisie.
                        </li>
                        <li class="mb-2">
                            Le taux horaire appliqué est celui de l'affectation
                            de l'enseignant sur le contrat.
                        </li>
                        <li>
                            L'enseignant est notifié automatiquement
                            une fois le paiement généré.
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection