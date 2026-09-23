@extends('panel.layouts.app')

@section('title', 'Nouvelle facture')

@section('content')
<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon">
                <i class="bi bi-receipt"></i>
            </div>
            <div>
                <h1 class="mb-0">Nouvelle facture</h1>
                <p class="text-muted mb-0">Générer une facture pour un contrat et une période</p>
            </div>
        </div>

        <div class="heading-actions">
            <a href="{{ route('finance.factures.index') }}" class="btn btn-light">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
        </div>
    </div>


    <form action="{{ route('finance.factures.store') }}" method="POST" id="formFacture">
        @csrf

        <div class="row g-4">

            {{-- FORMULAIRE --}}
            <div class="col-lg-8">

                <div class="panel">

                    <div class="panel-header">
                        <div>
                            <h5 class="mb-0">Informations de la facture</h5>
                            <p class="text-muted mb-0">
                                Sélectionnez le contrat et la période
                            </p>
                        </div>
                    </div>

                    <div class="panel-body">

                        {{-- CONTRAT --}}
                        <div class="mb-4">
                            <label class="form-label">Contrat</label>
                            <select name="contrat_cours_id"
                                    id="contratSelect"
                                    class="form-select @error('contrat_cours_id') is-invalid @enderror"
                                    required>
                                <option value="">Sélectionner un contrat</option>
                                @foreach($contrats as $contrat)
                                    <option value="{{ $contrat->id }}"
                                            data-eleve="{{ $contrat->eleve?->user?->prenom }} {{ $contrat->eleve?->user?->nom }}"
                                            data-frais-suivi="{{ $contrat->autres_frais_suivi ?? 0 }}"
                                            @selected(old('contrat_cours_id') == $contrat->id)>
                                        {{ $contrat->eleve?->user?->prenom }}
                                        {{ $contrat->eleve?->user?->nom }}
                                        — {{ $contrat->typeCours?->libelle ?? 'N/A' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('contrat_cours_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- PERIODE --}}
                        <div class="mb-4">
                            <label class="form-label">Période comptable</label>
                            <select name="periode_id"
                                    id="periodeSelect"
                                    class="form-select @error('periode_id') is-invalid @enderror"
                                    required>
                                <option value="">Sélectionner une période ouverte</option>
                                @foreach($periodes as $periode)
                                    <option value="{{ $periode->id }}"
                                            @selected(old('periode_id') == $periode->id)>
                                        {{ $periode->label }}
                                        —
                                        {{ $periode->date_debut->format('d/m/Y') }}
                                        au
                                        {{ $periode->date_fin->format('d/m/Y') }}
                                    </option>
                                @endforeach
                            </select>
                            @error('periode_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- ALERTE PREREQUIS --}}
                        <div id="alertePrerequis" class="alert alert-danger d-none mb-4">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Rapports manquants :</strong>
                            <ul id="listeManquants" class="mb-0 mt-1"></ul>
                        </div>

                        {{-- FRAIS --}}
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Frais de suivi (F)</label>
                                <input type="number"
                                       name="frais_suivi"
                                       id="fraisSuivi"
                                       value="{{ old('frais_suivi', 0) }}"
                                       min="0"
                                       class="form-control @error('frais_suivi') is-invalid @enderror">
                                @error('frais_suivi')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Autres frais (F)</label>
                                <input type="number"
                                       name="autres_frais"
                                       value="{{ old('autres_frais', 0) }}"
                                       min="0"
                                       class="form-control @error('autres_frais') is-invalid @enderror">
                                @error('autres_frais')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Remise (F)</label>
                                <input type="number"
                                       name="remise"
                                       value="{{ old('remise', 0) }}"
                                       min="0"
                                       class="form-control @error('remise') is-invalid @enderror">
                                @error('remise')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- DATE LIMITE --}}
                        <div class="mb-4">
                            <label class="form-label">Date limite de paiement</label>
                            <input type="date"
                                   name="date_limite_paiement"
                                   value="{{ old('date_limite_paiement') }}"
                                   class="form-control @error('date_limite_paiement') is-invalid @enderror">
                            @error('date_limite_paiement')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- COMMENTAIRE --}}
                        <div class="mb-4">
                            <label class="form-label">Commentaire</label>
                            <textarea name="commentaire"
                                      rows="3"
                                      class="form-control @error('commentaire') is-invalid @enderror"
                                      placeholder="Note interne ou commentaire pour le parent...">{{ old('commentaire') }}</textarea>
                            @error('commentaire')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- ACTIONS --}}
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('finance.factures.index') }}" class="btn btn-light">
                                Annuler
                            </a>
                            <button type="submit"
                                    class="btn btn-primary"
                                    id="btnGenerer"
                                    disabled>
                                <i class="bi bi-receipt"></i>
                                Générer la facture
                            </button>
                        </div>

                    </div>
                </div>
            </div>


            {{-- APERÇU --}}
            <div class="col-lg-4">

                <div class="panel mb-4">
                    <div class="panel-header">
                        <h5 class="mb-0">Aperçu de la facture</h5>
                    </div>
                    <div class="panel-body">

                        <div class="info-list mb-3">
                            <div>
                                <span>Élève</span>
                                <strong id="apercuEleve">—</strong>
                            </div>
                            <div>
                                <span>Parent</span>
                                <strong id="apercuParent">—</strong>
                            </div>
                            <div>
                                <span>Période</span>
                                <strong id="apercuPeriode">—</strong>
                            </div>
                            <div>
                                <span>Statut</span>
                                <strong>
                                    <span class="badge text-bg-warning">En attente</span>
                                </strong>
                            </div>
                        </div>

                        {{-- LIGNES --}}
                        <div id="apercuLignes" class="d-none">
                            <h6 class="mb-2">Lignes calculées</h6>
                            <div class="table-responsive mb-3">
                                <table class="table table-sm table-borderless mb-0">
                                    <thead>
                                        <tr>
                                            <th class="text-muted small">Enseignant</th>
                                            <th class="text-muted small">Matière</th>
                                            <th class="text-muted small text-end">H</th>
                                            <th class="text-muted small text-end">Taux</th>
                                            <th class="text-muted small text-end">Montant</th>
                                        </tr>
                                    </thead>
                                    <tbody id="apercuLignesBody">
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- RÉCAPITULATIF --}}
                        <div id="apercuRecap" class="d-none">
                            <hr class="my-2">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Volume horaire</span>
                                <strong id="apercuVolumeTotal">0 h</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Montant cours</span>
                                <strong id="apercuMontantCours">0 F</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Frais de suivi</span>
                                <span id="apercuFraisSuivi">0 F</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Autres frais</span>
                                <span id="apercuAutresFrais">0 F</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Remise</span>
                                <span class="text-danger" id="apercuRemise">- 0 F</span>
                            </div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between">
                                <strong>TOTAL</strong>
                                <strong class="text-primary" id="apercuMontantTotal">0 F</strong>
                            </div>
                        </div>

                        {{-- MESSAGE VIDE --}}
                        <div id="apercuVide" class="text-center text-muted py-3">
                            <i class="bi bi-receipt fs-1 d-block mb-2"></i>
                            Sélectionnez un contrat et une période pour voir l'aperçu
                        </div>

                    </div>
                </div>

                <div class="panel">
                    <div class="panel-header">
                        <h5 class="mb-0">Règles de calcul</h5>
                    </div>
                    <div class="panel-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <i class="bi bi-check-circle text-success"></i>
                                Heures issues des rapports mensuels
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-check-circle text-success"></i>
                                Vérification automatique des rapports
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-check-circle text-success"></i>
                                Montant = Heures × Taux horaire
                            </li>
                            <li>
                                <i class="bi bi-check-circle text-success"></i>
                                + Frais suivi + Autres frais - Remise
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

        </div>
    </form>

</div>
@endsection


@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    const contratSelect = document.getElementById('contratSelect');
    const periodeSelect = document.getElementById('periodeSelect');
    const btnGenerer = document.getElementById('btnGenerer');
    const alertePrerequis = document.getElementById('alertePrerequis');
    const listeManquants = document.getElementById('listeManquants');
    const apercuEleve = document.getElementById('apercuEleve');
    const apercuParent = document.getElementById('apercuParent');
    const apercuPeriode = document.getElementById('apercuPeriode');
    const apercuLignes = document.getElementById('apercuLignes');
    const apercuLignesBody = document.getElementById('apercuLignesBody');
    const apercuRecap = document.getElementById('apercuRecap');
    const apercuVide = document.getElementById('apercuVide');
    const apercuVolumeTotal = document.getElementById('apercuVolumeTotal');
    const apercuMontantCours = document.getElementById('apercuMontantCours');
    const apercuFraisSuivi = document.getElementById('apercuFraisSuivi');
    const apercuAutresFrais = document.getElementById('apercuAutresFrais');
    const apercuRemise = document.getElementById('apercuRemise');
    const apercuMontantTotal = document.getElementById('apercuMontantTotal');
    const fraisSuiviInput = document.getElementById('fraisSuivi');
    const autresFraisInput = document.querySelector('input[name="autres_frais"]');
    const remiseInput = document.querySelector('input[name="remise"]');

    let ctrlPrerequis = null;
    let ctrlPreview = null;
    let debounceTimer = null;

    contratSelect.addEventListener('change', function () {
        const option = this.options[this.selectedIndex];
        const frais = option.dataset.fraisSuivi || 0;
        fraisSuiviInput.value = frais;
        refresh();
    });

    periodeSelect.addEventListener('change', refresh);

    [fraisSuiviInput, autresFraisInput, remiseInput].forEach(function (el) {
        if (el) {
            el.addEventListener('input', function () {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(refresh, 300);
            });
        }
    });

    function refresh() {
        verifierPrerequis();
        chargerPreview();
    }

    async function verifierPrerequis() {
        if (ctrlPrerequis) ctrlPrerequis.abort();
        ctrlPrerequis = new AbortController();

        const contratId = contratSelect.value;
        const periodeId = periodeSelect.value;

        if (!contratId || !periodeId) {
            alertePrerequis.classList.add('d-none');
            btnGenerer.disabled = true;
            return;
        }

        try {
            const url = new URL(
                '{{ route("finance.factures.verifier-prerequis") }}',
                window.location.origin
            );
            url.searchParams.set('contrat_cours_id', contratId);
            url.searchParams.set('periode_id', periodeId);

            const response = await fetch(url, {
                signal: ctrlPrerequis.signal,
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });

            if (!response.ok) {
                btnGenerer.disabled = true;
                return;
            }

            const data = await response.json();

            if (data.prerequis_ok) {
                alertePrerequis.classList.add('d-none');
                btnGenerer.disabled = false;
            } else {
                listeManquants.innerHTML = '';
                data.manquants.forEach(function (m) {
                    const li = document.createElement('li');
                    li.textContent = m.nom_complet + ' (' + m.matiere + ')';
                    listeManquants.appendChild(li);
                });
                alertePrerequis.classList.remove('d-none');
                btnGenerer.disabled = true;
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Erreur vérification prérequis:', error);
                btnGenerer.disabled = true;
            }
        }
    }

    async function chargerPreview() {
        if (ctrlPreview) ctrlPreview.abort();
        ctrlPreview = new AbortController();

        const contratId = contratSelect.value;
        const periodeId = periodeSelect.value;

        if (!contratId || !periodeId) {
            apercuLignes.classList.add('d-none');
            apercuRecap.classList.add('d-none');
            apercuVide.classList.remove('d-none');
            apercuEleve.textContent = '—';
            apercuParent.textContent = '—';
            apercuPeriode.textContent = '—';
            return;
        }

        try {
            const body = new URLSearchParams();
            body.append('contrat_cours_id', contratId);
            body.append('periode_id', periodeId);
            body.append('frais_suivi', fraisSuiviInput.value || 0);
            body.append('autres_frais', autresFraisInput?.value || 0);
            body.append('remise', remiseInput?.value || 0);

            const response = await fetch(
                '{{ route("finance.factures.preview") }}',
                {
                    method: 'POST',
                    signal: ctrlPreview.signal,
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: body
                }
            );

            if (!response.ok) {
                apercuLignes.classList.add('d-none');
                apercuRecap.classList.add('d-none');
                apercuVide.classList.remove('d-none');
                apercuVide.innerHTML =
                    '<i class="bi bi-exclamation-circle fs-1 d-block mb-2 text-danger"></i>' +
                    'Erreur lors du chargement de l\'aperçu';
                return;
            }

            const data = await response.json();

            apercuEleve.textContent = data.eleve || '—';
            apercuParent.textContent = data.parent || '—';
            apercuPeriode.textContent = data.periode_label || '—';

            if (data.lignes && data.lignes.length > 0) {
                apercuLignesBody.innerHTML = '';
                data.lignes.forEach(function (ligne) {
                    const tr = document.createElement('tr');
                    tr.innerHTML =
                        '<td class="small">' + escapeHtml(ligne.enseignant) + '</td>' +
                        '<td class="small">' + escapeHtml(ligne.matiere) + '</td>' +
                        '<td class="small text-end">' + ligne.nombre_heures.toFixed(2) + '</td>' +
                        '<td class="small text-end">' + formatNombre(ligne.taux_horaire) + '</td>' +
                        '<td class="small text-end">' + formatMontant(ligne.montant) + '</td>';
                    apercuLignesBody.appendChild(tr);
                });

                apercuVolumeTotal.textContent = data.volume_horaire_total.toFixed(2) + ' h';
                apercuMontantCours.textContent = formatMontant(data.montant_cours);
                apercuFraisSuivi.textContent = formatMontant(data.frais_suivi);
                apercuAutresFrais.textContent = formatMontant(data.autres_frais);
                apercuRemise.textContent = '− ' + formatMontant(data.remise);
                apercuMontantTotal.textContent = formatMontant(data.montant_total);

                apercuLignes.classList.remove('d-none');
                apercuRecap.classList.remove('d-none');
                apercuVide.classList.add('d-none');
            } else {
                apercuLignes.classList.add('d-none');
                apercuRecap.classList.add('d-none');
                apercuVide.classList.remove('d-none');
                apercuVide.innerHTML =
                    '<i class="bi bi-exclamation-circle fs-1 d-block mb-2 text-warning"></i>' +
                    'Aucune heure calculée pour cette combinaison contrat / période';
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Erreur preview:', error);
            }
        }
    }

    function formatMontant(valeur) {
        return new Intl.NumberFormat('fr-FR').format(valeur) + ' F';
    }

    function formatNombre(valeur) {
        return new Intl.NumberFormat('fr-FR').format(valeur);
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>
@endpush
