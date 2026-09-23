@extends('panel.layouts.app')

@section('title', 'Paiement facture cabinet')

@section('content')
<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon">
                <i class="bi bi-cash"></i>
            </div>
            <div>
                <h1 class="mb-0">Enregistrer un paiement</h1>
                <p class="text-muted mb-0">
                    Facture du
                    {{ \Carbon\Carbon::parse($facture->periode_debut)->format('d/m/Y') }}
                    —
                    {{ \Carbon\Carbon::parse($facture->periode_fin)->format('d/m/Y') }}
                </p>
            </div>
        </div>

        <div class="heading-actions">
            <a href="{{ route('finance.facture-cabinet.show', $facture) }}" class="btn btn-light">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
        </div>
    </div>


    <div class="row g-4">

        {{-- COLONNE GAUCHE --}}
        <div class="col-lg-8">

            <form method="POST" action="{{ route('finance.facture-cabinet.paiement.store', $facture) }}">
                @csrf

                <div class="panel mb-4">
                    <div class="panel-header">
                        <h5 class="mb-0">Détails du paiement</h5>
                    </div>
                    <div class="panel-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Montant payé (F) *</label>
                                <input type="number"
                                       name="montant_paye"
                                       class="form-control @error('montant_paye') is-invalid @enderror"
                                       value="{{ old('montant_paye', $facture->montant_restant) }}"
                                       min="1"
                                       max="{{ $facture->montant_restant }}"
                                       required>
                                @error('montant_paye')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">
                                    Restant : {{ number_format($facture->montant_restant, 0, ',', ' ') }} F
                                </small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Mode de paiement *</label>
                                <select name="mode_paiement"
                                        class="form-select @error('mode_paiement') is-invalid @enderror"
                                        required>
                                    <option value="">— Sélectionner —</option>
                                    <option value="especes" @selected(old('mode_paiement') === 'especes')>Espèces</option>
                                    <option value="orange_money" @selected(old('mode_paiement') === 'orange_money')>Orange Money</option>
                                    <option value="moov_money" @selected(old('mode_paiement') === 'moov_money')>Moov Money</option>
                                    <option value="virement" @selected(old('mode_paiement') === 'virement')>Virement</option>
                                    <option value="cheque" @selected(old('mode_paiement') === 'cheque')>Chèque</option>
                                    <option value="autre" @selected(old('mode_paiement') === 'autre')>Autre</option>
                                </select>
                                @error('mode_paiement')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Date du paiement *</label>
                                <input type="date"
                                       name="date_paiement"
                                       class="form-control @error('date_paiement') is-invalid @enderror"
                                       value="{{ old('date_paiement', now()->format('Y-m-d')) }}"
                                       required>
                                @error('date_paiement')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Référence</label>
                                <input type="text"
                                       name="reference_paiement"
                                       class="form-control @error('reference_paiement') is-invalid @enderror"
                                       value="{{ old('reference_paiement') }}"
                                       placeholder="Numéro de reçu, transaction...">
                                @error('reference_paiement')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-body">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-lg"></i> Enregistrer le paiement
                        </button>
                    </div>
                </div>

            </form>

        </div>


        {{-- COLONNE DROITE --}}
        <div class="col-lg-4">

            <div class="panel mb-4">
                <div class="panel-header">
                    <h5 class="mb-0">Résumé de la facture</h5>
                </div>
                <div class="panel-body">
                    <div class="info-list">
                        <div>
                            <span>Montant total</span>
                            <strong>{{ number_format($facture->montant_total_du, 0, ',', ' ') }} F</strong>
                        </div>
                        <div>
                            <span>Déjà payé</span>
                            <strong class="text-success">{{ number_format($facture->montant_paye, 0, ',', ' ') }} F</strong>
                        </div>
                        <div>
                            <span>Restant</span>
                            <strong class="text-danger">{{ number_format($facture->montant_restant, 0, ',', ' ') }} F</strong>
                        </div>
                    </div>
                </div>
            </div>


            <div class="panel">
                <div class="panel-header">
                    <h5 class="mb-0">{{ config('keduc.cabinet.nom') }}</h5>
                </div>
                <div class="panel-body">
                    <div class="info-list">
                        <div>
                            <span>Tél</span>
                            <strong>{{ config('keduc.cabinet.telephone') }}</strong>
                        </div>
                        <div>
                            <span>WhatsApp</span>
                            <strong>{{ config('keduc.cabinet.whatsapp') }}</strong>
                        </div>
                        <div>
                            <span>Orange Money</span>
                            <strong>{{ config('keduc.cabinet.orange_money') }}</strong>
                        </div>
                        <div>
                            <span>Moov Money</span>
                            <strong>{{ config('keduc.cabinet.moov_money') }}</strong>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
