@extends('panel.layouts.app')

@section('title', 'Apercu des bulletins de paie')

@section('content')
<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon"><i class="bi bi-eye"></i></div>
            <div>
                <h1 class="mb-0">Apercu des bulletins de paie</h1>
                <p class="text-muted mb-0">
                    @if($periodeSelectionnee)
                        Periode : {{ $periodeSelectionnee->label }}
                    @else
                        Selectionnez une periode pour commencer
                    @endif
                </p>
            </div>
        </div>

        <div class="heading-actions">
            <a href="{{ route('finance.bulletins-paie.index') }}" class="btn btn-light">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ============================================================
         SELECTEUR DE PERIODE (GET)
    ============================================================= --}}
    <div class="panel mb-4">
        <div class="panel-header">
            <h5 class="mb-0">Choisir la periode</h5>
        </div>
        <div class="panel-body">
            <form method="GET" action="{{ route('finance.bulletins-paie.preview') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-6">
                        <label class="form-label fw-bold">Periode comptable</label>
                        <select name="periode_id" class="form-select" required>
                            <option value="">-- Choisir une periode --</option>
                            @foreach($periodes as $periode)
                                <option value="{{ $periode->id }}"
                                    {{ $periodeSelectionnee?->id === $periode->id ? 'selected' : '' }}>
                                    {{ $periode->label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3">
                        <button class="btn btn-primary w-100">
                            <i class="bi bi-eye"></i> Apercu
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================================================
         SIMULATION (si periode selectionnee)
    ============================================================= --}}
    @if($periodeSelectionnee)

        {{-- METRIQUES GLOBALES --}}
        <div class="row g-3 mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="metric-card metric-primary">
                    <div class="metric-label">Enseignants concernes</div>
                    <div class="metric-value">{{ $totalEnseignants }}</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="metric-card metric-success">
                    <div class="metric-label">Total heures</div>
                    <div class="metric-value">{{ number_format($totalHeures, 2) }} h</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="metric-card metric-warning">
                    <div class="metric-label">Montant net total</div>
                    <div class="metric-value">{{ number_format($totalMontant, 0, ',', ' ') }} FCFA</div>
                </div>
            </div>
        </div>

        @if(empty($preview))
            <div class="panel">
                <div class="blank-state">
                    <i class="bi bi-exclamation-circle display-5 text-muted"></i>
                    <p class="mt-3 text-muted mb-0">
                        Aucun bulletin a generer pour cette periode.
                        Verifiez que les enseignants ont soumis leurs rapports mensuels.
                    </p>
                </div>
            </div>
        @else

            {{-- ============================================================
                 FORMULAIRE UNIQUE DE SIMULATION
                 Deux boutons avec formaction different :
                 - "Actualiser" → POST preview.actualiser (recalcule)
                 - "Generer"    → POST generer (cree les bulletins)
            ============================================================= --}}
            <form method="POST" id="form-simulation">
                @csrf
                <input type="hidden" name="periode_id" value="{{ $periodeSelectionnee->id }}">

                {{-- DETAIL PAR ENSEIGNANT --}}
                @foreach($preview as $item)
                    @php
                        $eid = $item['enseignant']->id;
                        $brut = $item['montant_brut'];
                        $fs = $item['frais_suivi'];
                        $net = $item['montant_net'];
                    @endphp

                    <div class="panel mb-4" data-enseignant-id="{{ $eid }}">
                        <div class="panel-header">
                            <div>
                                <h5 class="mb-0">
                                    {{ $item['enseignant']->user->prenom }}
                                    {{ $item['enseignant']->user->nom }}
                                </h5>
                                <p class="text-muted mb-0">
                                    {{ count($item['lignes']) }} ligne(s) —
                                    {{ number_format($item['total_heures'], 2) }} h —
                                    Brut : {{ number_format($brut, 0, ',', ' ') }} FCFA
                                </p>
                            </div>
                        </div>

                        {{-- TABLEAU DES LIGNES --}}
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Eleve</th>
                                        <th>Matiere</th>
                                        <th>Heures</th>
                                        <th>Taux horaire</th>
                                        <th class="text-end">Montant</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($item['lignes'] as $ligne)
                                        <tr>
                                            <td><strong>{{ $ligne['eleve_nom'] }}</strong></td>
                                            <td>
                                                <span class="badge text-bg-light">{{ $ligne['matiere_nom'] }}</span>
                                            </td>
                                            <td>{{ number_format($ligne['nombre_heures'], 2) }} h</td>
                                            <td>{{ number_format($ligne['taux_horaire'], 0, ',', ' ') }} FCFA/h</td>
                                            <td class="text-end">
                                                <strong>{{ number_format($ligne['montant'], 0, ',', ' ') }} FCFA</strong>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    {{-- TOTAL BRUT --}}
                                    <tr class="table-light">
                                        <td colspan="2"><strong>Total brut</strong></td>
                                        <td><strong>{{ number_format($item['total_heures'], 2) }} h</strong></td>
                                        <td></td>
                                        <td class="text-end">
                                            <strong>{{ number_format($brut, 0, ',', ' ') }} FCFA</strong>
                                        </td>
                                    </tr>

                                    {{-- FRAIS DE SUIVI --}}
                                    <tr>
                                        <td colspan="3">
                                            <strong>Frais de suivi :</strong>
                                            <input type="number"
                                                   name="frais_suivi[{{ $eid }}]"
                                                   value="{{ $fs }}"
                                                   min="0"
                                                   class="form-control form-control-sm d-inline-block w-auto frais-suivi-input ms-2"
                                                   data-enseignant="{{ $eid }}">
                                            <span class="text-muted ms-1">FCFA</span>
                                        </td>
                                        <td></td>
                                        <td class="text-end">
                                            <span class="frais-suivi-display">- {{ number_format($fs, 0, ',', ' ') }} FCFA</span>
                                        </td>
                                    </tr>

                                    {{-- AJUSTEMENTS --}}
                                    @foreach($typesAjustement as $type)
                                        @php
                                            $ajMontant = 0;
                                            foreach ($item['ajustements'] as $aj) {
                                                if ($aj['type_ajustement_id'] === $type->id) {
                                                    $ajMontant = $aj['montant'];
                                                    break;
                                                }
                                            }
                                            $sign = $type->direction === 'credit' ? '+' : '-';
                                            $signClass = $type->direction === 'credit' ? 'text-success' : 'text-danger';
                                        @endphp
                                        <tr>
                                            <td colspan="3">
                                                <strong>{{ $type->libelle }} :</strong>
                                                @if($type->direction === 'credit')
                                                    <span class="badge text-bg-success ms-1">Credit</span>
                                                @else
                                                    <span class="badge text-bg-danger ms-1">Debit</span>
                                                @endif
                                                <input type="number"
                                                       name="ajustements[{{ $eid }}][{{ $type->id }}]"
                                                       value="{{ $ajMontant }}"
                                                       min="0"
                                                       class="form-control form-control-sm d-inline-block w-auto ajustement-input ms-2"
                                                       data-enseignant="{{ $eid }}"
                                                       data-direction="{{ $type->direction }}">
                                                <span class="text-muted ms-1">FCFA</span>
                                            </td>
                                            <td></td>
                                            <td class="text-end">
                                                <span class="{{ $signClass }} ajustement-display"
                                                      data-enseignant="{{ $eid }}"
                                                      data-direction="{{ $type->direction }}">
                                                    {{ $sign }} {{ number_format($ajMontant, 0, ',', ' ') }} FCFA
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach

                                    {{-- TOTAL CREDITS / DEBITS --}}
                                    <tr class="table-light">
                                        <td colspan="4">
                                            <strong>Total credits :</strong>
                                        </td>
                                        <td class="text-end">
                                            <span class="text-success fw-bold credits-display"
                                                  data-enseignant="{{ $eid }}">
                                                + {{ number_format($item['total_credits'], 0, ',', ' ') }} FCFA
                                            </span>
                                        </td>
                                    </tr>
                                    <tr class="table-light">
                                        <td colspan="4">
                                            <strong>Total debits :</strong>
                                        </td>
                                        <td class="text-end">
                                            <span class="text-danger fw-bold debits-display"
                                                  data-enseignant="{{ $eid }}">
                                                - {{ number_format($item['total_debits'], 0, ',', ' ') }} FCFA
                                            </span>
                                        </td>
                                    </tr>

                                    {{-- NET A PAYER --}}
                                    <tr class="table-success">
                                        <td colspan="4"><strong>Net a payer</strong></td>
                                        <td class="text-end">
                                            <strong class="montant-net-display fs-5"
                                                    data-enseignant="{{ $eid }}">
                                                {{ number_format($net, 0, ',', ' ') }} FCFA
                                            </strong>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                @endforeach

                {{-- BOUTONS D'ACTION --}}
                <div class="d-flex justify-content-center gap-3 mt-4 mb-5">
                    <button type="submit"
                            class="btn btn-primary btn-lg"
                            formaction="{{ route('finance.bulletins-paie.preview.actualiser') }}">
                        <i class="bi bi-arrow-clockwise"></i> Actualiser l'apercu
                    </button>

                    <button type="submit"
                            class="btn btn-success btn-lg"
                            formaction="{{ route('finance.bulletins-paie.generer') }}"
                            onclick="return confirm('Generer {{ $totalEnseignants }} bulletin(s) pour {{ $periodeSelectionnee->label }} ?')">
                        <i class="bi bi-check-circle"></i> Generer {{ $totalEnseignants }} bulletin(s)
                    </button>
                </div>
            </form>

        @endif
    @endif

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    /**
     * Recalcul local en temps reel quand l'utilisateur modifie un champ.
     * Met a jour le montant net, les totaux credits/debits de l'enseignant.
     */
    function recalculerEnseignant(eid) {
        var panel = document.querySelector('[data-enseignant-id="' + eid + '"]');
        if (!panel) return;

        var brut = 0;
        var fraisSuivi = 0;
        var totalCredits = 0;
        var totalDebits = 0;

        // Lire le brut depuis l'input frais_suivi (data-brut non disponible, on lit le tfoot)
        var brutCell = panel.querySelector('tfoot tr:first-child td:last-child strong');
        if (brutCell) {
            brut = parseInt(brutCell.textContent.replace(/\s/g, '').replace('FCFA', '')) || 0;
        }

        // Frais de suivi
        var fsInput = panel.querySelector('.frais-suivi-input');
        if (fsInput) {
            fraisSuivi = parseInt(fsInput.value) || 0;
            var fsDisplay = panel.querySelector('.frais-suivi-display');
            if (fsDisplay) {
                fsDisplay.textContent = '- ' + fraisSuivi.toLocaleString('fr-FR') + ' FCFA';
            }
        }

        // Ajustements
        var ajInputs = panel.querySelectorAll('.ajustement-input');
        ajInputs.forEach(function (input) {
            var montant = parseInt(input.value) || 0;
            var direction = input.dataset.direction;
            var sign = direction === 'credit' ? '+' : '-';

            if (direction === 'credit') {
                totalCredits += montant;
            } else {
                totalDebits += montant;
            }

            var display = input.closest('tr').querySelector('.ajustement-display');
            if (display) {
                display.textContent = sign + ' ' + montant.toLocaleString('fr-FR') + ' FCFA';
            }
        });

        // Mettre a jour totaux
        var creditsDisplay = panel.querySelector('.credits-display');
        if (creditsDisplay) {
            creditsDisplay.textContent = '+ ' + totalCredits.toLocaleString('fr-FR') + ' FCFA';
        }

        var debitsDisplay = panel.querySelector('.debits-display');
        if (debitsDisplay) {
            debitsDisplay.textContent = '- ' + totalDebits.toLocaleString('fr-FR') + ' FCFA';
        }

        // Calculer le net
        var net = brut - fraisSuivi + totalCredits - totalDebits;
        var netDisplay = panel.querySelector('.montant-net-display');
        if (netDisplay) {
            netDisplay.textContent = net.toLocaleString('fr-FR') + ' FCFA';
        }
    }

    // Attacher les ecouteurs
    document.querySelectorAll('.frais-suivi-input').forEach(function (input) {
        input.addEventListener('input', function () {
            recalculerEnseignant(this.dataset.enseignant);
        });
    });

    document.querySelectorAll('.ajustement-input').forEach(function (input) {
        input.addEventListener('input', function () {
            recalculerEnseignant(this.dataset.enseignant);
        });
    });
});
</script>
@endpush
