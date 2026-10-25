@extends('landlord.layouts.app')

@section('title', 'Nouvelle facture — cabinet')

@section('content')
    <h1 class="h3 fw-bold mb-1">
        <a href="{{ route('landlord.facturation.index') }}" class="text-decoration-none text-muted me-1">&laquo;</a>
        Nouvelle facture
    </h1>
    <div class="small text-muted mb-3">
        1 · Choisir le cabinet et la période → 2 · Vérifier les activités facturables automatiques
        → 3 · Ajuster avec la catalogue et les lignes manuelles → 4 · Créer le brouillon, puis
        émettre (numérotation définitive, montants figés).
    </div>

    <form method="GET" class="row g-3 align-items-end card p-3 mb-4">
        @csrf
        <div class="col-md-4">
            <label class="form-label small fw-semibold mb-1">Cabinet</label>
            <select name="cabinet" id="facturation-cabinet" class="form-select">
                <option value="">— Choisir —</option>
                @foreach ($cabinets as $c)
                    <option value="{{ $c->id }}" {{ ((string) request()->query('cabinet')) === $c->id ? 'selected' : '' }}>
                        {{ $c->nom }} ({{ $c->id }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Début</label>
            <input type="date" name="debut" id="facturation-fin" class="form-control" value="{{ request()->query('debut') }}">
        </div>

        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Fin</label>
            <input type="date" name="fin" id="facturation-debut" class="form-control" value="{{ request()->query('fin') }}">
        </div>

        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-search me-1"></i>Calculer
            </button>
        </div>
    </form>

    @if ($cabinet === null)
        <div class="alert alert-info">Choisissez un cabinet et une période, puis « Calculer ».</div>
    @elseif ($periode === null)
        <div class="alert alert-warning">Période invalide.</div>
    @else
        {{-- Résumé cabinet + contrat --}}
        <div class="card p-3 mb-3">
            <div class="row g-2 align-items-center">
                <div class="col-md-6">
                    <strong>{{ $cabinet->nom }}</strong>
                    <small class="text-muted">({{ $cabinet->id }})</small>
                </div>
                <div class="col-md-6 text-md-end small text-muted">
                    Contrat validé :
                    @if ($contrat === null)
                        <span class="badge bg-warning text-dark">aucun</span>
                    @else
                        <span class="badge bg-success">{{ $contrat->numero }} (v{{ $contrat->version }})</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Alertes tarifaires (Q-A17 : tarif non négocié ≠ 0) --}}
        @if (! empty($apercu['tarifs_manquants']))
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle me-1"></i>
                Tarifs du contrat non renseignés (service non facturé) :
                <ul class="mb-0 mt-2">
                    @foreach ($apercu['tarifs_manquants'] as $type => $reason)
                        <li><code>{{ $type }}</code> — {{ $reason }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Aperçu des lignes automatiques sur la période --}}
        <h6 class="fw-bold mt-4 mb-2">Activités facturables automatiques</h6>

        @if ($apercu === null || $apercu['evenements']->isEmpty())
            <div class="alert alert-info mb-2">Aucune activité facturable enregistrée pour cette période.</div>
        @else
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Activité</th>
                                <th class="text-end">Assiette</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($apercu['evenements'] as $evenement)
                                <tr>
                                    <td class="small">{{ $evenement->date_evenement->format('d/m') }}</td>
                                    <td><code class="small">{{ $evenement->type }}</code></td>
                                    <td class="small">{{ $evenement->libelle ?? '—' }}</td>
                                    <td class="text-end small">
                                        {{ $evenement->montant_assiette !== null ? number_format((float) $evenement->montant_assiette, 0, ',', ' ') : '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <h6 class="fw-bold mt-4 mb-2">Lignes qui seront facturées (calculateurs par contrat)</h6>
        <div class="card">
            <div class="table-responsive">
                <table class="table table-sm mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Type</th>
                            <th>Libellé</th>
                            <th class="text-end">Qté</th>
                            <th class="text-end">P.U. (FCFA)</th>
                            <th class="text-end">Montant (FCFA)</th>
                            <th class="text-center">Fiable?</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($apercu['lignes'] as $ligne)
                            <tr>
                                <td><code class="small">{{ $ligne['type'] }}</code></td>
                                <td class="small">{{ $ligne['libelle'] }}</td>
                                <td class="text-end">{{ $ligne['quantite'] ?? 1 }}</td>
                                <td class="text-end">{{ $ligne['prix_unitaire'] ?? '—' }}</td>
                                <td class="text-end fw-semibold">{{ number_format((float) $ligne['montant'], 0, ',', ' ') }}</td>
                                <td class="text-center">
                                    @if ($ligne['fiable'] ?? true)
                                        <span class="badge bg-success">oui</span>
                                    @else
                                        <span class="badge bg-warning text-dark" title="Reconstitution approximative (Q-A14)">à vérifier</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-3">Aucune ligne calculée (enfant période/tarifs).</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Total aperçu --}}
        <div class="text-end fw-bold mt-2 mb-4">
            Total : {{ number_format((float) $apercu['lignes']->sum('montant'), 0, ',', ' ') }} FCFA
        </div>

        {{-- Saisie de lignes manuelles --}}
        <div class="d-flex justify-content-between align-items-center mt-4 mb-2">
            <h6 class="fw-bold mb-0">Lignes manuelles (débits + / crédits −)</h6>
            <span class="small text-muted">Cliquez une ligne type du catalogue pour préremplir.</span>
        </div>

        {{-- Catalogue éditable des lignes types (P8) --}}
        @if ($lignesTypes->isNotEmpty())
            <div class="card p-2 mb-2">
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <span class="small fw-semibold text-muted me-1">Catalogue :</span>
                    @foreach ($lignesTypes as $lt)
                        <button type="button"
                            class="btn btn-outline-primary btn-sm js-catalogue"
                            data-libelle="{{ $lt->libelle }}"
                            data-sens="{{ $lt->sens }}"
                            data-montant="{{ $lt->montant ?? 0 }}"
                            data-motif="{{ $lt->description }}"
                            title="{{ $lt->description }}">
                            <i class="bi bi-plus-lg me-1"></i>{{ $lt->libelle }}
                            @if ($lt->sens === 'credit')
                                <span class="badge bg-success">−</span>
                            @else
                                <span class="badge bg-secondary">{{ number_format((float) $lt->montant, 0, ',', ' ') }}</span>
                            @endif
                        </button>
                    @endforeach
                    <a href="{{ route('landlord.catalogue.index') }}" class="small ms-auto" target="_blank">
                        <i class="bi bi-pencil-square me-1"></i>Gérer le catalogue
                    </a>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('landlord.facturation.creer') }}">
            @csrf
            <input type="hidden" name="cabinet" value="{{ $cabinet->id }}">
            <input type="hidden" name="date_debut" value="{{ $periodeDebut }}">
            <input type="hidden" name="date_fin" value="{{ $periodeFin }}">

            <div class="row g-3 card p-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold mb-1">Conditions de paiement</label>
                    <textarea name="conditions_paiement" rows="2" class="form-control form-control-sm"
                        placeholder="Ex : payable à réception, par Orange Money au +226 70 00 00 00 / total :"></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold mb-1">Note interne (non visible au cabinet)</label>
                    <textarea name="note_interne" rows="2" class="form-control form-control-sm"></textarea>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Libellé</th>
                            <th style="width: 11%">Sens</th>
                            <th style="width: 8%">Qté</th>
                            <th style="width: 11%">Montant</th>
                            <th>Motif</th>
                        </tr>
                    </thead>
                    <tbody id="lignes-manuelles">
                        @for ($i = 0; $i < 3; $i++)
                            <tr>
                                <td><input type="text" name="ligne[{{ $i }}][libelle]" class="form-control form-control-sm js-ligne-libelle" placeholder="_remise fidélité…"></td>
                                <td>
                                    <select name="ligne[{{ $i }}][sens]" class="form-select form-select-sm js-ligne-sens">
                                        <option value="debit">Débit</option>
                                        <option value="credit">Crédit</option>
                                    </select>
                                </td>
                                <td><input type="number" name="ligne[{{ $i }}][quantite]" class="form-control form-control-sm" min="1" max="999999" value="1"></td>
                                <td><input type="number" name="ligne[{{ $i }}][montant]" class="form-control form-control-sm js-ligne-montant" min="0" placeholder="Entier FCFA"></td>
                                <td><input type="text" name="ligne[{{ $i }}][motif]" class="form-control form-control-sm js-ligne-motif" placeholder="motif (pour justifier)"></td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>

            <button type="button" id="ajouter-ligne" class="btn btn-outline-secondary btn-sm mb-3">
                <i class="bi bi-plus-lg me-1"></i>Ajouter une ligne
            </button>

            <div class="small text-muted mb-2">
                Montant des lignes manuelles inscrites :
                <span id="total-manuels" class="fw-bold">0</span> FCFA
                <span class="text-muted">(les crédits sont affichés en négatif)</span>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-file-earmark-plus me-1"></i>Créer le brouillon
            </button>
        </form>

        @push('scripts')
            <script>
                (function () {
                    const root = document.getElementById('lignes-manuelles');
                    const total = document.getElementById('total-manuels');
                    const MAX_LIGNES = 20;

                    function nouvelleLigneHTML(i) {
                        return '<tr>'
                            + '<td><input type="text" name="ligne['+i+'][libelle]" class="form-control form-control-sm js-ligne-libelle" placeholder="_remise fidélité…"></td>'
                            + '<td><select name="ligne['+i+'][sens]" class="form-select form-select-sm js-ligne-sens">'
                            + '<option value="debit">Débit</option><option value="credit">Crédit</option></select></td>'
                            + '<td><input type="number" name="ligne['+i+'][quantite]" class="form-control form-control-sm" min="1" max="999999" value="1"></td>'
                            + '<td><input type="number" name="ligne['+i+'][montant]" class="form-control form-control-sm js-ligne-montant" min="0" placeholder="Entier FCFA"></td>'
                            + '<td><input type="text" name="ligne['+i+'][motif]" class="form-control form-control-sm js-ligne-motif" placeholder="motif (pour justifier)"></td>'
                            + '</tr>';
                    }

                    function lignes() {
                        return Array.from(root.querySelectorAll('tr'));
                    }

                    function ligneVide() {
                        return lignes().find(function (tr) {
                            return (tr.querySelector('.js-ligne-libelle')?.value || '') === ''
                                && (tr.querySelector('.js-ligne-montant')?.value || '') === '';
                        }) || null;
                    }

                    function ajouterLigne() {
                        if (lignes().length >= MAX_LIGNES) return null;
                        root.insertAdjacentHTML('beforeend', nouvelleLigneHTML(lignes().length));
                        return lignes()[lignes().length - 1];
                    }

                    function depuisCatalogue(btn) {
                        const libelle = btn.dataset.libelle || '';
                        const sens = btn.dataset.sens || 'debit';
                        const montant = btn.dataset.montant || '';
                        const motif = btn.dataset.motif || '';
                        const cible = ligneVide() || ajouterLigne();
                        if (! cible) return;
                        cible.querySelector('.js-ligne-libelle').value = libelle;
                        cible.querySelector('.js-ligne-sens').value = sens;
                        cible.querySelector('.js-ligne-montant').value = montant || '';
                        cible.querySelector('.js-ligne-motif').value = motif;
                        maj();
                    }

                    function maj() {
                        let somme = 0;
                        lignes().forEach(function (tr) {
                            const sens = tr.querySelector('.js-ligne-sens')?.value ?? 'debit';
                            const montant = parseInt(tr.querySelector('.js-ligne-montant')?.value || '0', 10) || 0;
                            somme += sens === 'credit' ? -montant : montant;
                        });
                        total.textContent = somme.toLocaleString('fr-FR');
                    }

                    document.querySelectorAll('.js-catalogue').forEach(function (btn) {
                        btn.addEventListener('click', function () { depuisCatalogue(btn); });
                    });

                    const boutonAjout = document.getElementById('ajouter-ligne');
                    if (boutonAjout !== null) {
                        boutonAjout.addEventListener('click', function () {
                            const tr = ajouterLigne();
                            if (tr !== null) tr.querySelector('.js-ligne-libelle').focus();
                        });
                    }

                    root.addEventListener('input', maj);
                    root.addEventListener('change', maj);
                    maj();
                })();
            </script>
        @endpush
    @endif
@endsection
