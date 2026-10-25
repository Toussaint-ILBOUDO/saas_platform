@extends('landlord.layouts.app')

@section('title', 'Catalogue de la plateforme')

@section('content')
    <h1 class="h3 fw-bold mb-1">Catalogue de la plateforme</h1>
    <div class="small text-muted mb-4">
        Fonctionnalités facturables du contrat commercial et briques de facturation manuelle.
        Les textes sont éditables à tout moment ; ils alimentent immédiatement les écrans de
        contrat, la préparation des factures et les PDF (les factures déjà émises restent figées).
    </div>

    {{-- ------------------------------------------------------------- --}}
    {{-- Services facturables (grille du contrat commercial) --}}
    {{-- ------------------------------------------------------------- --}}
    <h5 class="fw-bold mb-3"><i class="bi bi-collection me-1"></i>Services du contrat commercial</h5>

    @foreach ($categories as $categorieId => $categorieLabel)
        @php
            $groupe = $services->get($categorieId, collect());
        @endphp

        @if ($groupe->isNotEmpty())
            <div class="card mb-3 shadow-sm">
                <div class="card-header bg-white fw-semibold">{{ $categorieLabel }}</div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 22%">Service</th>
                                <th>Explication (affichée au cabinet)</th>
                                <th class="text-center" style="width: 8%">Actif</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($groupe as $service)
                                <tr>
                                    <td class="small">
                                        <div class="fw-semibold">{{ $service->libelle }}</div>
                                        <code class="text-muted">{{ $service->cle }}</code>
                                    </td>
                                    <td>
                                        <form method="POST" action="{{ route('landlord.catalogue.services.update', $service) }}" class="d-flex gap-2">
                                            @csrf
                                            @method('PUT')
                                            <input type="text" name="libelle" maxlength="150" class="form-control form-control-sm" value="{{ $service->libelle }}">
                                            <input type="text" name="description" maxlength="2000" class="form-control form-control-sm" placeholder="Explication affichée à l'écran du contrat et sur le PDF…" value="{{ $service->description }}">
                                            <input type="hidden" name="categorie" value="{{ $service->categorie }}">
                                            <div class="form-check form-switch d-flex align-items-center">
                                                <input class="form-check-input" type="checkbox" name="actif" value="1"
                                                    id="service-{{ $service->id }}" @checked($service->actif)>
                                            </div>
                                            <button type="submit" class="btn btn-sm btn-outline-primary">Enregistrer</button>
                                        </form>
                                    </td>
                                    <td class="text-center">
                                        <label for="service-{{ $service->id }}" class="form-check-label small text-muted">
                                            {{ $service->actif ? 'oui' : 'non' }}
                                        </label>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endforeach

    {{-- ------------------------------------------------------------- --}}
    {{-- Lignes types de facture (facturation manuelle) --}}
    {{-- ------------------------------------------------------------- --}}
    <h5 class="fw-bold mt-4 mb-3"><i class="bi bi-receipt me-1"></i>Lignes types de facture</h5>

    <div class="card shadow-sm mb-2">
        <div class="card-header bg-white fw-semibold">Mes lignes types</div>

        <div class="card-body p-0">
            <table class="table table-sm mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Libellé</th>
                            <th style="width: 10%">Sens</th>
                            <th style="width: 12%">Prix de départ</th>
                            <th>Texte explicatif</th>
                            <th class="text-center" style="width: 8%">Actif</th>
                            <th style="width: 1%"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($lignesTypes as $ligne)
                            @php
                                $fid = 'f-ligne-'.$ligne->id;
                            @endphp
                            <tr @class(['text-muted opacity-75' => ! $ligne->actif])>
                                <form method="POST" action="{{ route('landlord.catalogue.lignes-types.update', $ligne) }}" id="{{ $fid }}" hidden>
                                    @csrf
                                    @method('PUT')
                                </form>
                                <td><input type="text" form="{{ $fid }}" name="libelle" maxlength="200" class="form-control form-control-sm" value="{{ $ligne->libelle }}"></td>
                                <td>
                                    <select form="{{ $fid }}" name="sens" class="form-select form-select-sm">
                                        <option value="debit" @selected($ligne->sens === 'debit')>Débit +</option>
                                        <option value="credit" @selected($ligne->sens === 'credit')>Crédit −</option>
                                    </select>
                                </td>
                                <td><input type="number" form="{{ $fid }}" name="montant" class="form-control form-control-sm text-end" min="0" value="{{ $ligne->montant }}" placeholder="libre"></td>
                                <td><input type="text" form="{{ $fid }}" name="description" maxlength="2000" class="form-control form-control-sm" placeholder="Texte visible sur la facture…" value="{{ $ligne->description }}"></td>
                                <td class="text-center">
                                    <div class="form-check form-switch d-inline-flex align-items-center">
                                        <input class="form-check-input" form="{{ $fid }}" type="checkbox" name="actif" value="1"
                                            @checked($ligne->actif) aria-label="Actif">
                                    </div>
                                </td>
                                <td class="text-end text-nowrap">
                                    <button type="submit" form="{{ $fid }}" class="btn btn-sm btn-outline-primary">Enregistrer</button>
                                    @if ($ligne->actif)
                                        <form method="POST" action="{{ route('landlord.catalogue.lignes-types.desactiver', $ligne) }}" id="d-ligne-{{ $ligne->id }}" hidden>
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                        <button type="submit" form="d-ligne-{{ $ligne->id }}" class="btn btn-outline-danger btn-sm"
                                            onclick="return confirm('Désactiver cette ligne type ?')">×</button>
                                    @endif
                                </td>
                            </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-3">Aucune ligne type.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Nouvelle ligne type --}}
    <div class="card shadow-sm">
        <div class="card-header bg-white fw-semibold">Ajouter une ligne type</div>
        <div class="card-body">
            <form method="POST" action="{{ route('landlord.catalogue.lignes-types.creer') }}" class="row g-2">
                @csrf
                <div class="col-md-4">
                    <input type="text" name="libelle" maxlength="200" class="form-control form-control-sm" placeholder="Libellé *" required>
                </div>
                <div class="col-md-2">
                    <select name="sens" class="form-select form-select-sm">
                        <option value="debit">Débit (facturé)</option>
                        <option value="credit">Crédit (à déduire)</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="number" name="montant" class="form-control form-control-sm text-end" min="0" placeholder="Prix FCFA (vide = libre)">
                </div>
                <div class="col-md-3">
                    <input type="text" name="description" maxlength="2000" class="form-control form-control-sm" placeholder="Texte explicatif…">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary btn-sm w-100">+</button>
                </div>
            </form>
        </div>
    </div>
@endsection
