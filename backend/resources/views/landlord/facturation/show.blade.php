@extends('landlord.layouts.app')

@section('title', 'Facture '.($facture->numero_facture ?? '#'.$facture->id))

@section('content')
    @php
        $numero = $facture->numero_facture ?? ('Brouillon #'.$facture->id);
        $estEmise = ! $facture->estBrouillon() && ! $facture->estAnnulee();
        $aser = max(0, (int) $facture->resteAPayer());
        $etiquettes = [
            \App\Models\FactureCabinet::STATUT_EMISE => 'Émise',
            \App\Models\FactureCabinet::STATUT_PARTIELLEMENT_PAYEE => 'Partiellement payée',
            \App\Models\FactureCabinet::STATUT_PAYEE => 'Payée',
            \App\Models\FactureCabinet::STATUT_BROUILLON => 'Brouillon',
            \App\Models\FactureCabinet::STATUT_ANNULEE => 'Annulée',
        ];
        $classeBadge = $facture->estPayee() ? 'bg-success'
            : ($facture->estPartiellementPayee() ? 'bg-warning text-dark'
            : ($facture->estAnnulee() ? 'bg-danger'
            : ($facture->estBrouillon() ? 'bg-secondary' : 'bg-info text-dark')));
        $lignesDebitAuto = $facture->lignes->where('sens', 'debit')->where('source', 'auto');
        $lignesDebitManuel = $facture->lignes->where('sens', 'debit')->where('source', 'manuelle');
        $lignesCredit = $facture->lignes->where('sens', 'credit');
    @endphp

    <h1 class="h3 fw-bold mb-1">
        <a href="{{ route('landlord.facturation.index') }}" class="text-decoration-none text-muted me-1">&laquo;</a>
        Facture {{ $numero }}
    </h1>
    <div class="small text-muted mb-3">Fiche de travail du super-admin — le cabinet voit la même version via son PDF.</div>

    {{-- ------------------------------------------------------------- --}}
    {{-- 1. Fiche générale --}}
    {{-- ------------------------------------------------------------- --}}
    <div class="card p-3 mb-2">
        <div class="row g-3">
            <div class="col-md-5">
                <div class="small fw-semibold text-muted mb-1">Cabinet facturé</div>
                <div class="fw-bold">{{ $facture->cabinet?->nom ?? $facture->cabinet_id }}</div>
                <div class="small text-muted">Une facture ne concerne qu'un seul cabinet.</div>
            </div>
            <div class="col-md-3">
                <div class="small fw-semibold text-muted mb-1">Période couverte</div>
                <div>
                    du {{ $facture->periode_debut?->format('d/m/Y') }}
                    au {{ $facture->periode_fin?->format('d/m/Y') }}
                </div>
                <div class="small text-muted">Activités de cette période uniquement.</div>
            </div>
            <div class="col-md-2">
                <div class="small fw-semibold text-muted mb-1">Émise le</div>
                <div>{{ $facture->emise_le?->format('d/m/Y H:i') ?? '—' }}</div>
                <div class="small text-muted"> Numérotation posée à l'émission.</div>
            </div>
            <div class="col-md-2 text-md-end">
                <div class="small fw-semibold text-muted mb-1">Statut</div>
                <span class="badge {{ $classeBadge }} fs-6">{{ $etiquettes[$facture->statut] ?? $facture->statut }}</span>
            </div>
        </div>
    </div>

    {{-- Timeline des statuts --}}
    @php
        $jalons = [
            'brouillon' => 'Création du brouillon',
            'emise' => 'Émission (montants figés)',
            'partiellement_payee' => 'Règlement partiel',
            'payee' => 'Réglage complet',
        ];
        $indexStatut = array_search($facture->statut, array_keys($jalons), true);
    @endphp
    @if (! $facture->estAnnulee())
        <div class="card p-2 mb-3">
            <div class="d-flex flex-wrap gap-2 small">
                @foreach ($jalons as $cle => $libelle)
                    <span class="badge {{ $indexStatut !== false && array_search($cle, array_keys($jalons), true) <= $indexStatut ? ($cle === $facture->statut ? 'bg-success' : 'bg-success-subtle text-success') : 'bg-light text-muted' }}">
                        {{ $libelle }}
                    </span>
                    @if (! $loop->last)<i class="bi bi-arrow-right text-muted align-self-center"></i>@endif
                @endforeach
                @if ($facture->estBrouillon())
                    <span class="ms-auto text-muted">Les montants deviennent figés à l'émission.</span>
                @endif
            </div>
        </div>
    @elseif ($facture->annulee_le)
        <div class="alert alert-danger py-2 mb-3">
            Facture annulée le {{ $facture->annulee_le->format('d/m/Y') }}
            @if ($facture->motif_annulation) — {{ $facture->motif_annulation }} @endif.
            Les activités facturables couvertes ont été relâchées : réémission possible sur une nouvelle facture.
        </div>
    @endif

    {{-- ------------------------------------------------------------- --}}
    {{-- 2. Actions --}}
    {{-- ------------------------------------------------------------- --}}
    <div class="mb-3 d-flex flex-wrap gap-2 align-items-end">
        <a href="{{ route('landlord.facturation.pdf', $facture) }}" class="btn btn-outline-secondary btn-sm" target="_blank">
            <i class="bi bi-file-earmark-pdf me-1"></i>Voir le PDF
        </a>

        @if ($facture->estBrouillon())
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal-emettre">
                <i class="bi bi-send me-1"></i>Émettre (figer + numéroter)
            </button>
            {{-- Briques du catalogue pour compléter un brouillon incomplet --}}
            @if ($aser === 0 && $facture->montantCalcule() > 0)
                <span class="badge bg-success-subtle text-success">Prêt à émettre</span>
            @else
                <span class="badge bg-warning text-dark">Complétez les lignes avant l'émission</span>
            @endif
            <button class="btn btn-outline-danger btn-sm ms-auto" data-bs-toggle="modal" data-bs-target="#modal-supprimer">
                <i class="bi bi-trash me-1"></i>Supprimer le brouillon
            </button>
        @elseif ($estEmise)
            @if ($aser > 0)
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal-annuler">
                    <i class="bi bi-x-circle me-1"></i>Annuler / régulariser
                </button>
                <span class="small text-muted ms-auto">
                    Annulation réservée aux factures sans aucun encaissement (Q-A16).
                </span>
            @else
                <span class="badge bg-light text-muted ms-auto">Aucun encaissement : annulation bloquée.</span>
            @endif
        @endif
    </div>

    {{-- Modal d'émission : rappel du contenu --}}
    @if ($facture->estBrouillon())
        <div class="modal fade" id="modal-emettre" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Émettre cette facture</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>
                            Total final : <strong>{{ number_format((float) $facture->montantCalcule(), 0, ',', ' ') }} FCFA</strong>
                            (Σ débits − Σ crédits).
                        </p>
                        <ul class="small text-muted mb-2">
                            <li>Un numéro <code>FC-…</code> est posé définitivement (jamais réutilisé).</li>
                            <li>Les activités couvertes sont marquées facturées : elles ne peuvent plus servir pour une autre facture.</li>
                            <li>Les lignes deviennent figées : plus aucune modification possible.</li>
                        </ul>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                        <form method="POST" action="{{ route('landlord.facturation.emettre', $facture) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary">Confirmer l'émission</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="modal-supprimer" tabindex="-1">
            <div class="modal-dialog modal-sm">
                <div class="modal-content">
                    <div class="modal-body text-center">
                        Supprimer ce brouillon ? Les activités couvertes redeviennent disponibles.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                        <form method="POST" action="{{ route('landlord.facturation.brouillon.supprimer', $facture) }}">
                            @csrf
                            <button type="submit" class="btn btn-danger">Supprimer</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal d'annulation (motif obligatoire) --}}
    @if ($estEmise)
        <div class="modal fade" id="modal-annuler" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Annuler la facture</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" action="{{ route('landlord.facturation.annuler', $facture) }}">
                        @csrf
                        <div class="modal-body">
                            <p class="small text-muted mb-2">
                                L'annulation relâche les activités facturables couvertes (réfacturables)
                                et libère la période. Numéro conservé : la régularisation passe par une
                                nouvelle facture, jamais en réutilisant ce numéro.
                            </p>
                            <label class="small fw-semibold mb-1">Motif (visible dans la traçabilité)</label>
                            <textarea name="motif" rows="2" class="form-control form-control-sm" maxlength="500" required
                                placeholder="Ex : double facturation de la période…"></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                            <button type="submit" class="btn btn-outline-danger">Annuler la facture</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- ------------------------------------------------------------- --}}
    {{-- 3. Lignes de la facture --}}
    {{-- ------------------------------------------------------------- --}}
    <h5 class="fw-bold mt-4 mb-1">Détail facturé</h5>
    <div class="small text-muted mb-3">
        Les lignes « automatiques » sont calculées d'après les activités facturables de la période et le
        cabinet contrat en cours. Les lignes « manuelles » sont ajoutées sur juste le tarif du catalogue.
    </div>

    {{-- Débits automatiques --}}
    <div class="card mb-2">
        <div class="card-header bg-white fw-semibold d-flex justify-content-between">
            <span>Activités facturables automatiques</span>
            <span class="badge bg-info text-dark">{{ number_format((float) $lignesDebitAuto->sum('montant'), 0, ',', ' ') }} FCFA</span>
        </div>
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Libellé</th>
                        <th class="text-end">Qté</th>
                        <th class="text-end">P.U. (FCFA)</th>
                        <th class="text-end">Montant (FCFA)</th>
                        <th class="text-center" style="width:42px"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($lignesDebitAuto as $ligne)
                        <tr>
                            <td class="small">
                                {{ $ligne->evenement?->date_evenement?->format('d/m/Y') ?? '—' }}
                                @if (! $ligne->fiable)<span class="badge bg-warning text-dark">à vérifier</span>@endif
                            </td>
                            <td><code class="small">{{ $ligne->type }}</code></td>
                            <td class="small">
                                {{ $ligne->libelle }}
                                @if ($ligne->motif)<div class="small text-muted">motif : {{ $ligne->motif }}</div>@endif
                            </td>
                            <td class="text-end">{{ $ligne->quantite }}</td>
                            <td class="text-end">
                                {{ $ligne->prix_unitaire !== null ? number_format((float) $ligne->prix_unitaire, 0, ',', ' ') : '—' }}
                            </td>
                            <td class="text-end fw-semibold">{{ number_format((float) $ligne->montant, 0, ',', ' ') }}</td>
                            <td>
                                @if ($facture->estBrouillon())
                                    <form method="POST" action="{{ route('landlord.facturation.lignes.supprimer', [$facture, $ligne->id]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-secondary btn-sm" title="Retirer cette ligne">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-2">Aucune ligne automatique.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Débits manuels --}}
    <div class="card mb-2">
        <div class="card-header bg-white fw-semibold d-flex justify-content-between">
            <span>Lignes ajoutées à la main</span>
            <span class="badge bg-secondary">{{ number_format((float) $lignesDebitManuel->sum('montant'), 0, ',', ' ') }} FCFA</span>
        </div>
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Libellé</th>
                        <th class="text-end">Qté</th>
                        <th class="text-end">Montant (FCFA)</th>
                        <th>Auteur</th>
                        <th class="text-center" style="width:42px"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($lignesDebitManuel as $ligne)
                        <tr class="{{ $facture->estBrouillon() ? '' : 'opacity-75' }}">
                            <td class="small">
                                {{ $ligne->libelle }}
                                @if ($ligne->motif)<div class="small text-muted">motif : {{ $ligne->motif }}</div>@endif
                            </td>
                            <td class="text-end">{{ $ligne->quantite }}</td>
                            <td class="text-end fw-semibold">{{ number_format((float) $ligne->montant, 0, ',', ' ') }}</td>
                            <td class="small text-muted">
                                {{ ($auteurs[(int) ($ligne->cree_par_user ?? 0)] ?? null) ?: $ligne->cree_par_user }}
                            </td>
                            <td>
                                @if ($facture->estBrouillon())
                                    <form method="POST" action="{{ route('landlord.facturation.lignes.supprimer', [$facture, $ligne->id]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-secondary btn-sm" title="Retirer cette ligne">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-2">Aucun ajout manuel.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Crédits (réductions) --}}
    <div class="card mb-2">
        <div class="card-header bg-white fw-semibold d-flex justify-content-between">
            <span>Réductions (crédits)</span>
            <span class="badge bg-success">{{ number_format((float) $lignesCredit->sum('montant'), 0, ',', ' ') }} FCFA</span>
        </div>
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Libellé</th>
                        <th>Motif</th>
                        <th class="text-end">Montant déduit (FCFA)</th>
                        <th class="text-center" style="width:42px"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($lignesCredit as $ligne)
                        <tr>
                            <td class="small">{{ $ligne->libelle }}</td>
                            <td class="small text-muted">{{ $ligne->motif ?? '—' }}</td>
                            <td class="text-end fw-semibold text-success">−{{ number_format((float) $ligne->montant, 0, ',', ' ') }}</td>
                            <td>
                                @if ($facture->estBrouillon())
                                    <form method="POST" action="{{ route('landlord.facturation.lignes.supprimer', [$facture, $ligne->id]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-secondary btn-sm" title="Retirer cette ligne">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-2">Aucune réduction appliquée.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Total calculé --}}
    <div class="text-end mt-2 mb-3">
        <span class="small text-muted me-3">Total = Σ débits − Σ crédits</span>
        <strong>{{ number_format((float) $facture->montantCalcule(), 0, ',', ' ') }} FCFA</strong>
    </div>

    {{-- ------------------------------------------------------------- --}}
    {{-- 4. Compléter le brouillon (briques catalogue + ajout libre) --}}
    {{-- ------------------------------------------------------------- --}}
    @if ($facture->estBrouillon())
        <h5 class="fw-bold mt-4 mb-1">Compléter la facture</h5>
        <div class="small text-muted mb-2">
            Cliquez une ligne type du catalogue pour préremplir le formulaire, ou saisissez librement.
            Après émission, les lignes deviennent figées à vie.
        </div>

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

        <form method="POST" action="{{ route('landlord.facturation.lignes.ajouter', $facture) }}" class="card p-3 mb-3 js-form-ligne">
            @csrf
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <input type="text" name="libelle" class="form-control form-control-sm js-ligne-libelle" placeholder="Libellé" required>
                </div>
                <div class="col-md-2">
                    <select name="sens" class="form-select form-select-sm js-ligne-sens">
                        <option value="debit">Débit (+)</option>
                        <option value="credit">Crédit (−)</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <input type="number" name="quantite" class="form-control form-control-sm" min="1" value="1">
                </div>
                <div class="col-md-2">
                    <input type="number" name="montant" class="form-control form-control-sm js-ligne-montant" min="1" placeholder="FCFA" required>
                </div>
                <div class="col-md-3">
                    <input type="text" name="motif" class="form-control form-control-sm js-ligne-motif" placeholder="Motif (explication visible)">
                </div>
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Ajouter la ligne</button>
                </div>
            </div>
        </form>
    @endif

    {{-- ------------------------------------------------------------- --}}
    {{-- 5. Paiements --}}
    {{-- ------------------------------------------------------------- --}}
    <h5 class="fw-bold mt-4 mb-1">Paiements encaissés</h5>
    <div class="small text-muted mb-2">
        Le cabinet peut régler en une ou plusieurs fois ; sur-encaissement refusé. Le statut passe
        à « partiellement payée » puis « payée ».
    </div>

    <div class="card mb-3">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr><th>Date</th><th>Mode</th><th>Référence</th><th>Commentaire</th><th class="text-end">Montant (FCFA)</th></tr>
                </thead>
                <tbody>
                    @forelse ($facture->paiements as $paiement)
                        <tr>
                            <td>{{ $paiement->date_paiement?->format('d/m/Y') }}</td>
                            <td>{{ $paiement->mode }}</td>
                            <td>{{ $paiement->reference ?? '—' }}</td>
                            <td class="small text-muted">{{ $paiement->commentaire ?? '—' }}</td>
                            <td class="text-end">{{ number_format((float) $paiement->montant, 0, ',', ' ') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">Aucun paiement enregistré.</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="table-light">
                        <td colspan="4" class="text-end fw-semibold">
                            Encaissé / Facture {{ number_format((float) $facture->montant_total, 0, ',', ' ') }} FCFA
                            @if ($aser > 0) — reste à payer : <strong class="text-danger">{{ number_format((float) $aser, 0, ',', ' ') }} FCFA</strong> @endif
                        </td>
                        <td class="text-end fw-bold">{{ number_format((float) $facture->montant_paye, 0, ',', ' ') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    @if ($estEmise && $aser > 0)
        <form method="POST" action="{{ route('landlord.facturation.paiements.ajouter', $facture) }}" class="card p-3 mb-4">
            @csrf
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="small fw-semibold mb-1">Montant (FCFA)</label>
                    <input type="number" name="montant"
                        class="form-control form-control-sm" min="1" max="{{ $aser }}"
                        value="{{ $aser }}" required>
                </div>
                <div class="col-md-2">
                    <label class="small fw-semibold mb-1">Mode</label>
                    <select name="mode" class="form-select form-select-sm" required>
                        @foreach ($modes as $code => $nom)
                            <option value="{{ $code }}">{{ $nom }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="small fw-semibold mb-1">Référence</label>
                    <input type="text" name="reference" class="form-control form-control-sm" placeholder="N° transaction / reçu">
                </div>
                <div class="col-md-2">
                    <label class="small fw-semibold mb-1">Date</label>
                    <input type="date" name="date_paiement" class="form-control form-control-sm" value="{{ now()->format('Y-m-d') }}" required>
                </div>
                <div class="col-md-3">
                    <label class="small fw-semibold mb-1">Commentaire</label>
                    <input type="text" name="commentaire" class="form-control form-control-sm">
                </div>
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-cash-coin me-1"></i>Encaisser</button>
                    <span class="small text-muted">Le maximum autorisé est le reste à payer ci-dessus.</span>
                </div>
            </div>
        </form>
    @endif

    @if ($facture->note_interne)
        <div class="card p-2 mb-4 bg-body-tertiary">
            <span class="small fw-semibold text-muted">Note interne (non visible au cabinet)</span>
            <div class="small">{{ $facture->note_interne }}</div>
        </div>
    @endif

    @push('scripts')
        <script>
            // Briques du catalogue → préremplissage de la ligne d'ajout (brouillon uniquement).
            document.querySelectorAll('.js-catalogue').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const form = document.querySelector('.js-form-ligne');
                    if (form === null) return;

                    form.querySelector('.js-ligne-libelle').value = btn.dataset.libelle || '';
                    form.querySelector('.js-ligne-montant').value = btn.dataset.montant || '';
                    form.querySelector('.js-ligne-motif').value = btn.dataset.motif || '';
                    form.querySelector('.js-ligne-sens').value = btn.dataset.sens || 'debit';
                    form.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    form.querySelector('.js-ligne-libelle').focus();
                });
            });
        </script>
    @endpush
@endsection
