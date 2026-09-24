@extends('panel.layouts.app')

@section('title', 'Paiement bulletin ' . $bulletin->numero)

@section('content')
<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon"><i class="bi bi-wallet2"></i></div>
            <div>
                <h1 class="mb-0">Enregistrer le paiement</h1>
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
        <div class="col-lg-8">
            <div class="panel">
                <div class="panel-header">
                    <h5 class="mb-0">Détails du paiement</h5>
                </div>

                <div class="panel-body">
                    <form method="POST"
                          action="{{ route('finance.bulletins-paie.marquer-paye', $bulletin) }}">
                        @csrf

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Date de paiement</label>
                                <input type="date"
                                       name="date_paiement"
                                       value="{{ old('date_paiement', date('Y-m-d')) }}"
                                       class="form-control @error('date_paiement') is-invalid @enderror"
                                       required>
                                @error('date_paiement')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Mode de paiement</label>
                                <select name="mode_paiement"
                                        class="form-select @error('mode_paiement') is-invalid @enderror"
                                        required>
                                    <option value="">Choisir...</option>
                                    <option value="especes" {{ old('mode_paiement') === 'especes' ? 'selected' : '' }}>
                                        Espèces
                                    </option>
                                    <option value="orange_money" {{ old('mode_paiement') === 'orange_money' ? 'selected' : '' }}>
                                        Orange Money
                                    </option>
                                    <option value="moov_money" {{ old('mode_paiement') === 'moov_money' ? 'selected' : '' }}>
                                        Moov Money
                                    </option>
                                    <option value="virement" {{ old('mode_paiement') === 'virement' ? 'selected' : '' }}>
                                        Virement bancaire
                                    </option>
                                    <option value="cheque" {{ old('mode_paiement') === 'cheque' ? 'selected' : '' }}>
                                        Chèque
                                    </option>
                                </select>
                                @error('mode_paiement')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-bold">Référence transaction</label>
                                <input type="text"
                                       name="reference_paiement"
                                       value="{{ old('reference_paiement') }}"
                                       class="form-control"
                                       placeholder="Numéro de transaction, chèque, etc.">
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="bi bi-check-circle"></i>
                                Confirmer le paiement de
                                {{ number_format($bulletin->montant_net, 0, ',', ' ') }} FCFA
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="panel">
                <div class="panel-header">
                    <h5 class="mb-0">Résumé</h5>
                </div>

                <div class="panel-body">
                    <p><strong>Montant brut :</strong>
                        {{ number_format($bulletin->montant_brut, 0, ',', ' ') }} FCFA
                    </p>
                    <p><strong>Frais de suivi :</strong>
                        - {{ number_format($bulletin->frais_suivi, 0, ',', ' ') }} FCFA
                    </p>
                    <hr>
                    <p class="fw-bold text-success" style="font-size: 1.2rem;">
                        Net à payer : {{ number_format($bulletin->montant_net, 0, ',', ' ') }} FCFA
                    </p>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
