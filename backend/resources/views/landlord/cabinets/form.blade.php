@extends('landlord.layouts.app')

@section('title', $cabinet ? 'Modifier — ' . $cabinet->nom : 'Nouveau cabinet')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold mb-0">{{ $cabinet ? 'Modifier le cabinet' : 'Nouveau cabinet' }}</h1>
        @if ($cabinet)
            <a href="{{ route('landlord.cabinets.show', $cabinet) }}" class="btn btn-outline-secondary">Retour à la fiche</a>
        @endif
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $cabinet ? route('landlord.cabinets.update', $cabinet) : route('landlord.cabinets.store') }}" class="card shadow-sm">
        @csrf
        @method($cabinet ? 'PUT' : 'POST')

        <div class="card-body">
            @if (!$cabinet)
                <div class="mb-3">
                    <label class="form-label">Slug (identifiant du cabinet)</label>
                    <input type="text" name="id" value="{{ old('id') }}" class="form-control"
                           placeholder="ex. c1, cabinet-kodjovi, magis-plus-center" required>
                    <div class="form-text">Minuscules, chiffres et tirets. Sert aussi de nom de base de données.</div>
                </div>
            @endif

            <div class="mb-3">
                <label class="form-label">Nom du cabinet *</label>
                <input type="text" name="nom" value="{{ old('nom', $cabinet?->nom ?? '') }}" class="form-control" required>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Sous-domaine</label>
                    <input type="text" name="sous_domaine" value="{{ old('sous_domaine', $cabinet?->sous_domaine ?? '') }}"
                           class="form-control" placeholder="{{ $cabinet?->sous_domaine ?? 'c1' }}">
                    <div class="form-text">Domaine généré : {{ $cabinet?->sous_domaine ?: 'sous-domaine' }}.localhost (dev).
                        @if ($cabinet)
                            En cas de changement, le domaine DNS est resynchronisé automatiquement.
                        @endif
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Statut</label>
                    <select name="status" class="form-select">
                        <option value="actif" @selected(old('status', $cabinet?->status ?? 'actif') === 'actif')>Actif</option>
                        <option value="suspendu" @selected(old('status', $cabinet?->status ?? '') === 'suspendu')>Suspendu</option>
                        <option value="archive" @selected(old('status', $cabinet?->status ?? '') === 'archive')>Archivé</option>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email de contact</label>
                    <input type="email" name="email" value="{{ old('email', $cabinet?->email ?? '') }}"
                           class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Téléphone principal</label>
                    <input type="text" name="telephone" value="{{ old('telephone', $cabinet?->telephone ?? '') }}"
                           class="form-control" placeholder="ex. +22670000000">
                </div>
            </div>

            <hr>

            {{-- Fiche cabinet (D-044) : source du site public, enrichie plus tard par l'admin. --}}
            <h6 class="fw-bold text-uppercase text-muted mb-3">Fiche du site public</h6>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Slogan</label>
                    <input type="text" name="slogan" value="{{ old('slogan', $fiche['identite']['slogan'] ?? '') }}"
                           class="form-control" placeholder="ex. L'excellence pour tous">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Directeur</label>
                    <input type="text" name="directeur" value="{{ old('directeur', $fiche['identite']['directeur'] ?? '') }}"
                           class="form-control" placeholder="ex. M. K. Yao">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Téléphone secondaire</label>
                    <input type="text" name="telephone_2" value="{{ old('telephone_2', $fiche['contact']['telephone_2'] ?? '') }}"
                           class="form-control" placeholder="ex. +22671234567">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">WhatsApp</label>
                    <input type="text" name="whatsapp" value="{{ old('whatsapp', $fiche['contact']['whatsapp'] ?? '') }}"
                           class="form-control" placeholder="ex. +22670000000">
                </div>
            </div>

            <div class="row">
                <div class="col-md-8 mb-3">
                    <label class="form-label">Adresse</label>
                    <input type="text" name="adresse" value="{{ old('adresse', $fiche['contact']['adresse'] ?? '') }}"
                           class="form-control" placeholder="ex. Avenue Kwame N'Krumah, Ouagadougou">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Horaires</label>
                    <input type="text" name="horaires" value="{{ old('horaires', $fiche['contact']['horaires'] ?? '') }}"
                           class="form-control" placeholder="ex. 24h/24, 7j/7">
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Orange Money</label>
                    <input type="text" name="orange_money" value="{{ old('orange_money', $fiche['paiements']['orange_money'] ?? '') }}"
                           class="form-control" placeholder="+226xxxxxxxx">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Moov Money</label>
                    <input type="text" name="moov_money" value="{{ old('moov_money', $fiche['paiements']['moov_money'] ?? '') }}"
                           class="form-control" placeholder="+226xxxxxxxx">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Wave</label>
                    <input type="text" name="wave" value="{{ old('wave', $fiche['paiements']['wave'] ?? '') }}"
                           class="form-control" placeholder="+226xxxxxxxx">
                </div>
            </div>

            <div class="form-check mb-3">
                <input type="checkbox" name="cash" value="1" class="form-check-input" id="cash"
                       @checked(old('cash', $fiche['paiements']['cash'] ?? false))>
                <label class="form-check-label" for="cash">Paiement en espèces accepté</label>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Pays</label>
                    <input type="text" name="pays" value="{{ old('pays', $fiche['zones']['pays'] ?? '') }}"
                           class="form-control" placeholder="ex. Burkina Faso">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Devise</label>
                    <input type="text" name="devise" value="{{ old('devise', $fiche['zones']['devise'] ?? '') }}"
                           class="form-control" placeholder="ex. FCFA">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Zones d'intervention (localités)</label>
                <textarea name="localites" class="form-control" rows="3"
                          placeholder="Ouagadougou&#10;Bobo-Dioulasso&#10;Koudougou">{{ old('localites', implode("\n", $fiche['zones']['localites'] ?? [])) }}</textarea>
                <div class="form-text">Une ville ou commune par ligne.</div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Facebook</label>
                    <input type="text" name="facebook" value="{{ old('facebook', $fiche['reseaux']['facebook'] ?? '') }}"
                           class="form-control" placeholder="https://facebook.com/…">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">TikTok</label>
                    <input type="text" name="tiktok" value="{{ old('tiktok', $fiche['reseaux']['tiktok'] ?? '') }}"
                           class="form-control" placeholder="https://tiktok.com/@…">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">WhatsApp Business</label>
                    <input type="text" name="whatsapp_business" value="{{ old('whatsapp_business', $fiche['reseaux']['whatsapp_business'] ?? '') }}"
                           class="form-control" placeholder="https://wa.me/22670000000">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">LinkedIn</label>
                    <input type="text" name="linkedin" value="{{ old('linkedin', $fiche['reseaux']['linkedin'] ?? '') }}"
                           class="form-control" placeholder="https://linkedin.com/company/…">
                </div>
            </div>
        </div>

        <div class="card-footer d-flex justify-content-end gap-2">
            <a href="{{ route('landlord.cabinets.index') }}" class="btn btn-link">Annuler</a>
            <button type="submit" class="btn btn-primary">
                {{ $cabinet ? 'Enregistrer' : 'Créer le cabinet' }}
            </button>
        </div>
    </form>

    @if (!$cabinet)
        <div class="alert alert-info mt-3 mb-0">
            <i class="bi bi-gear me-2"></i>
            À la création : la base <code>cabinet_&lt;slug&gt;</code> est provisionnée automatiquement
            (migrations tenant + jeux de données, D-027), puis la fiche ci-dessus est injectée
            dans les paramètres publics du site (D-044).
        </div>
    @endif
@endsection