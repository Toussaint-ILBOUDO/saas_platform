@extends('panel.layouts.app')

@section('title', 'Nouvelle facture cabinet')

@section('content')
<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon">
                <i class="bi bi-briefcase"></i>
            </div>
            <div>
                <h1 class="mb-0">Nouvelle facture cabinet</h1>
                <p class="text-muted mb-0">Générer une facture de commissions</p>
            </div>
        </div>

        <div class="heading-actions">
            <a href="{{ route('finance.facture-cabinet.index') }}" class="btn btn-light">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
        </div>
    </div>


    <form method="POST" action="{{ route('finance.facture-cabinet.store') }}" id="form-facture-cabinet">
        @csrf

        <div class="row g-4">

            {{-- COLONNE GAUCHE --}}
            <div class="col-lg-8">

                {{-- PÉRIODE --}}
                <div class="panel mb-4">
                    <div class="panel-header">
                        <h5 class="mb-0">Période de facturation</h5>
                    </div>
                    <div class="panel-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Date début *</label>
                                <input type="date"
                                       name="date_debut"
                                       class="form-control @error('date_debut') is-invalid @enderror"
                                       value="{{ old('date_debut', now()->startOfMonth()->format('Y-m-d')) }}"
                                       required
                                       id="date_debut">
                                @error('date_debut')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date fin *</label>
                                <input type="date"
                                       name="date_fin"
                                       class="form-control @error('date_fin') is-invalid @enderror"
                                       value="{{ old('date_fin', now()->endOfMonth()->format('Y-m-d')) }}"
                                       required
                                       id="date_fin">
                                @error('date_fin')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>


                {{-- TAUX DE COMMISSION --}}
                <div class="panel mb-4">
                    <div class="panel-header">
                        <h5 class="mb-0">Taux de commission</h5>
                    </div>
                    <div class="panel-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Par cours actif (F)</label>
                                <input type="number"
                                       name="taux_cours"
                                       class="form-control"
                                       value="{{ old('taux_cours', 2000) }}"
                                       min="0"
                                       id="taux_cours">
                                <small class="text-muted">Montant fixe par cours actif</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Par inscription (F)</label>
                                <input type="number"
                                       name="taux_inscription"
                                       class="form-control"
                                       value="{{ old('taux_inscription', 3000) }}"
                                       min="0"
                                       id="taux_inscription">
                                <small class="text-muted">Montant fixe par élève inscrit</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Commission ventes (%)</label>
                                <input type="number"
                                       name="taux_vente"
                                       class="form-control"
                                       value="{{ old('taux_vente', 10) }}"
                                       min="0"
                                       step="0.1"
                                       id="taux_vente">
                                <small class="text-muted">Pourcentage sur les ventes</small>
                            </div>
                        </div>

                        <div class="mt-3">
                            <button type="button"
                                    class="btn btn-outline-primary"
                                    id="btn-preview">
                                <i class="bi bi-calculator"></i> Calculer l'aperçu
                            </button>
                        </div>
                    </div>
                </div>


                {{-- APERÇU --}}
                <div class="panel mb-4" id="preview-panel" style="display:none">
                    <div class="panel-header">
                        <h5 class="mb-0">Aperçu de la facture</h5>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th class="text-end">Quantité</th>
                                    <th class="text-end">Base calcul</th>
                                    <th class="text-end">Taux</th>
                                    <th class="text-end">Montant</th>
                                </tr>
                            </thead>
                            <tbody id="preview-lignes">
                            </tbody>
                        </table>
                    </div>

                    <div class="panel-body">
                        <div class="d-flex justify-content-between">
                            <span class="fs-5"><strong>Total</strong></span>
                            <span class="fs-5">
                                <strong id="preview-total">0 F</strong>
                            </span>
                        </div>
                    </div>
                </div>

            </div>


            {{-- COLONNE DROITE --}}
            <div class="col-lg-4">

                <div class="panel mb-4">
                    <div class="panel-header">
                        <h5 class="mb-0">Actions</h5>
                    </div>
                    <div class="panel-body">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check-lg"></i> Générer la facture
                        </button>

                        <div class="mt-3 text-center">
                            <a href="{{ route('finance.facture-cabinet.index') }}" class="text-muted">
                                Annuler
                            </a>
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-header">
                        <h5 class="mb-0">Informations</h5>
                    </div>
                    <div class="panel-body">
                        <p class="text-muted small mb-0">
                            La facture sera calculée automatiquement à partir des données réelles du système :
                        </p>
                        <ul class="text-muted small mt-2 mb-0">
                            <li>Cours actifs sur la période</li>
                            <li>Élèves inscrits sur la période</li>
                            <li>Ventes (factures clients payées)</li>
                        </ul>
                    </div>
                </div>

            </div>

        </div>

    </form>

</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const btnPreview = document.getElementById('btn-preview');
    const previewPanel = document.getElementById('preview-panel');
    const previewLignes = document.getElementById('preview-lignes');
    const previewTotal = document.getElementById('preview-total');

    btnPreview.addEventListener('click', function () {
        const dateDebut = document.getElementById('date_debut').value;
        const dateFin = document.getElementById('date_fin').value;
        const tauxCours = document.getElementById('taux_cours').value;
        const tauxInscription = document.getElementById('taux_inscription').value;
        const tauxVente = document.getElementById('taux_vente').value;

        if (!dateDebut || !dateFin) {
            alert('Veuillez sélectionner les dates de la période.');
            return;
        }

        fetch('{{ route("finance.facture-cabinet.preview") }}?date_debut=' + dateDebut + '&date_fin=' + dateFin + '&taux_cours=' + tauxCours + '&taux_inscription=' + tauxInscription + '&taux_vente=' + tauxVente, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            previewPanel.style.display = 'block';
            previewLignes.innerHTML = '';

            data.lignes.forEach(function (ligne) {
                previewLignes.innerHTML += '<tr>' +
                    '<td><span class="badge text-bg-primary">' + ligne.type_nom + '</span></td>' +
                    '<td class="text-end">' + new Intl.NumberFormat('fr-FR').format(ligne.quantite) + '</td>' +
                    '<td class="text-end">' + new Intl.NumberFormat('fr-FR').format(ligne.base_calcul) + ' F</td>' +
                    '<td class="text-end">' + ligne.taux_commission + '%</td>' +
                    '<td class="text-end"><strong>' + new Intl.NumberFormat('fr-FR').format(ligne.montant) + ' F</strong></td>' +
                '</tr>';
            });

            previewTotal.textContent = new Intl.NumberFormat('fr-FR').format(data.montant_total_du) + ' F';
        })
        .catch(function (error) {
            console.error('Erreur:', error);
            alert('Erreur lors du calcul de l\'aperçu.');
        });
    });
});
</script>
@endpush

@endsection
