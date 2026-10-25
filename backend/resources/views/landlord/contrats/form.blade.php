@php
    $lecture = (isset($lectureSeule) && $lectureSeule);
    $exoneres = collect($contrat->exonerations ?? []);
    $tauxCodes = ['taux_commission_librairie', 'taux_commission_autres'];
    $ordreCategories = ['ouverture', 'abonnement', 'annuel', 'commission', 'ponctuel'];
    $groupes = [];
    foreach ($ordreCategories as $categorie) {
        $groupes[$categorie] = collect($services)->filter(fn ($s) => $s['categorie'] === $categorie);
    }
    $affiche = array_keys($services);
    foreach ($affiche as $cle) {
        if (! in_array($services[$cle]['categorie'], $ordreCategories, true)) {
            $groupes[$services[$cle]['categorie']] ??= collect();
            $groupes[$services[$cle]['categorie']] = $groupes[$services[$cle]['categorie']]->put($cle, $services[$cle]);
        }
    }
@endphp

<div class="card p-3">
    @if ($lecture)
        <div class="alert alert-secondary py-2">
            <i class="bi bi-lock me-1"></i>
            Version {{ $contrat->statut }} — montants négociés figés ; toute modification passe par une nouvelle version.
        </div>
    @endif

    {{-- Identité / modalités --}}
    <div class="row g-2 mb-3">
        <div class="col-md-4">
            <label class="small fw-semibold mb-1">Responsable cabinet</label>
            <input type="text" name="responsable_nom" maxlength="120" class="form-control form-control-sm"
                value="{{ $contrat->responsable_nom }}" @if($lecture) readonly @endif>
        </div>
        <div class="col-md-2">
            <label class="small fw-semibold mb-1">Date d'effet</label>
            <input type="date" name="date_effet" class="form-control form-control-sm"
                value="{{ $contrat->date_effet?->format('Y-m-d') }}" @if($lecture) readonly @endif>
        </div>
        <div class="col-md-2">
            <label class="small fw-semibold mb-1">Fin de validité</label>
            <input type="date" name="date_fin_validite" class="form-control form-control-sm"
                value="{{ $contrat->date_fin_validite?->format('Y-m-d') }}" @if($lecture) readonly @endif>
        </div>
        <div class="col-md-4">
            <label class="small fw-semibold mb-1">Modalités de paiement</label>
            <input type="text" name="modalites_paiement" maxlength="1000" class="form-control form-control-sm"
                placeholder="Ex : mensuel par Orange Money"
                value="{{ $contrat->modalites_paiement }}" @if($lecture) readonly @endif>
        </div>
    </div>

    <div class="small text-muted mb-3">
        <strong>Comment lire la grille ?</strong>
        Champ <strong>vide</strong> = service non couvert (jamais facturé au cabinet) ·
        <strong>0</strong> = gratuit négocié explicitement ·
        <strong>exonération cochée</strong> = le service n'est PAS facturable à ce cabinet, même s'il survient.
        Les explications de chaque service sont éditables dans la console (menu <code>Catalogue</code>).
    </div>

    {{-- Tarifs groupés par partie, avec explication (catalogue éditable) --}}
    @foreach ($groupes as $categorie => $groupe)
        @if ($groupe->isNotEmpty())
            <h6 class="fw-bold mt-3 mb-2">
                {{ \App\Models\ServicePlateforme::CATEGORIES[$categorie] ?? ucfirst($categorie) }}
            </h6>
            <table class="table table-sm mb-3 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Service</th>
                        <th class="text-end" style="width: 20%">Montant / taux</th>
                        <th class="text-center" style="width: 9%">Exonéré</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($groupe as $code => $service)
                        <tr>
                            <td>
                                <div class="fw-semibold small">{{ $service['libelle'] }}</div>
                                @if ($service['description'])
                                    <div class="small text-muted">{{ $service['description'] }}</div>
                                @endif
                            </td>

                            <td class="text-end align-middle">
                                @if ($lecture)
                                    @if ($contrat->{$code} === null)
                                        <span class="text-muted">— (non couvert)</span>
                                    @elseif (in_array($code, $tauxCodes, true))
                                        {{ number_format((float) $contrat->{$code}, 2, ',', ' ') }} %
                                    @else
                                        {{ number_format((float) $contrat->{$code}, 0, ',', ' ') }} FCFA
                                    @endif
                                @else
                                    <div class="input-group input-group-sm justify-content-end">
                                        <input type="text" name="tarifs-{{ $code }}"
                                            value="{{ $contrat->{$code} }}"
                                            class="form-control text-end"
                                            placeholder="{{ in_array($code, $tauxCodes, true) ? '0-100' : 'entier FCFA' }}">
                                        @if (in_array($code, $tauxCodes, true))
                                            <span class="input-group-text">%</span>
                                        @endif
                                    </div>
                                @endif
                            </td>

                            <td class="text-center align-middle">
                                @if ($lecture)
                                    @if ($exoneres->contains($code))
                                        <i class="bi bi-check-lg text-success" title="Service exonéré"></i>
                                    @endif
                                @else
                                    <input type="checkbox" name="exonerations[]" value="{{ $code }}"
                                        class="form-check-input" @checked($exoneres->contains($code))>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endforeach

    <div class="small text-muted mb-3">
        Exonéré ≠ non couvert : le service « non couvert » n'est tout simplement pas facturable
        pendant CE contrat (tarif non négocié) ; une exonération marque la présence d'un accord
        de gratuité durable, même si le tarif est réglementairement enregistré ailleurs.
    </div>

    {{-- Conditions --}}
    <div class="mb-2">
        <label class="small fw-semibold mb-1">Conditions particulières</label>
        <textarea name="conditions_particulieres" rows="3" maxlength="3000" class="form-control form-control-sm"
            placeholder="Ex : 12 mois, renouvellement à l'échéance…" @if($lecture) readonly @endif>{{ $contrat->conditions_particulieres }}</textarea>
    </div>

    @if (! $lecture)
        <button type="submit" class="btn btn-primary btn-sm mt-2">
            <i class="bi bi-check-lg me-1"></i>Enregistrer les tarifs
        </button>
    @endif
</div>
