@extends('panel.layouts.app')

@section('title', 'Marquer la facture comme payée')

@section('content')
<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon">
                <i class="bi bi-check2-circle"></i>
            </div>
            <div>
                <h1 class="mb-0">Marquer comme payée</h1>
                <p class="text-muted mb-0">
                    Facture {{ $facture->numero_facture }}
                    — {{ number_format($facture->montant_total, 0, ',', ' ') }} F
                </p>
            </div>
        </div>

        <div class="heading-actions">
            <a href="{{ route('finance.factures.show', $facture) }}" class="btn btn-light">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
        </div>
    </div>


    <div class="row g-4">

        {{-- FORMULAIRE PAIEMENT --}}
        <div class="col-lg-8">

            <div class="panel">

                <div class="panel-header">
                    <div>
                        <h5 class="mb-0">Informations de paiement</h5>
                        <p class="text-muted mb-0">
                            Renseignez les détails du paiement reçu
                        </p>
                    </div>
                </div>

                <div class="panel-body">

                    <form action="{{ route('finance.factures.marquer-paye', $facture) }}" method="POST">
                        @csrf

                        {{-- DATE PAIEMENT --}}
                        <div class="mb-4">
                            <label class="form-label">Date du paiement</label>
                            <input type="date"
                                   name="date_paiement"
                                   value="{{ old('date_paiement', now()->format('Y-m-d')) }}"
                                   class="form-control @error('date_paiement') is-invalid @enderror"
                                   required>
                            @error('date_paiement')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- MODE PAIEMENT --}}
                        <div class="mb-4">
                            <label class="form-label">Mode de paiement</label>
                            <select name="mode_paiement"
                                    class="form-select @error('mode_paiement') is-invalid @enderror"
                                    required>
                                <option value="">Sélectionner un mode</option>
                                <option value="especes" @selected(old('mode_paiement') === 'especes')>Espèces</option>
                                <option value="orange_money" @selected(old('mode_paiement') === 'orange_money')>Orange Money</option>
                                <option value="moov_money" @selected(old('mode_paiement') === 'moov_money')>Moov Money</option>
                                <option value="virement" @selected(old('mode_paiement') === 'virement')>Virement bancaire</option>
                                <option value="cheque" @selected(old('mode_paiement') === 'cheque')>Chèque</option>
                                <option value="autre" @selected(old('mode_paiement') === 'autre')>Autre</option>
                            </select>
                            @error('mode_paiement')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- REFERENCE --}}
                        <div class="mb-4">
                            <label class="form-label">Référence</label>
                            <input type="text"
                                   name="reference_paiement"
                                   value="{{ old('reference_paiement') }}"
                                   class="form-control @error('reference_paiement') is-invalid @enderror"
                                   placeholder="Numéro de transaction, chèque...">
                            @error('reference_paiement')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- COMMENTAIRE --}}
                        <div class="mb-4">
                            <label class="form-label">Commentaire</label>
                            <textarea name="commentaire"
                                      rows="3"
                                      class="form-control @error('commentaire') is-invalid @enderror"
                                      placeholder="Note optionnelle...">{{ old('commentaire') }}</textarea>
                            @error('commentaire')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- ACTIONS --}}
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('finance.factures.show', $facture) }}" class="btn btn-light">
                                Annuler
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check2-circle"></i>
                                Confirmer le paiement
                            </button>
                        </div>

                    </form>

                </div>
            </div>
        </div>


        {{-- RÉCAP --}}
        <div class="col-lg-4">

            <div class="panel mb-4">
                <div class="panel-header">
                    <h5 class="mb-0">Récapitulatif facture</h5>
                </div>
                <div class="panel-body">
                    <div class="info-list">
                        <div>
                            <span>Numéro</span>
                            <strong>{{ $facture->numero_facture }}</strong>
                        </div>
                        <div>
                            <span>Élève</span>
                            <strong>
                                {{ $facture->contrat?->eleve?->user?->prenom }}
                                {{ $facture->contrat?->eleve?->user?->nom }}
                            </strong>
                        </div>
                        <div>
                            <span>Parent</span>
                            <strong>
                                {{ $facture->parent?->prenom }}
                                {{ $facture->parent?->nom }}
                            </strong>
                        </div>
                        <div>
                            <span>Période</span>
                            <strong>{{ $facture->periode?->label ?? '—' }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel mb-4">
                <div class="panel-header">
                    <h5 class="mb-0">Montant à payer</h5>
                </div>
                <div class="panel-body text-center">
                    <div class="display-6 fw-bold text-success">
                        {{ number_format($facture->montant_total, 0, ',', ' ') }} F
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <h5 class="mb-0">{{ $cabinet['nom'] }}</h5>
                </div>
                <div class="panel-body">
                    <div class="info-list">
                        <div>
                            <span>Orange Money</span>
                            <strong>{{ $cabinet['orange_money'] }}</strong>
                        </div>
                        <div>
                            <span>Moov Money</span>
                            <strong>{{ $cabinet['moov_money'] }}</strong>
                        </div>
                        <div>
                            <span>Tél</span>
                            <strong>{{ $cabinet['telephone'] }}</strong>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
