@extends('panel.layouts.app')

@section('title', 'Modifier — ' . $produit->nom . ' — Librairie')

@section('content')

<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon"><i class="bi bi-box-seam"></i></div>
            <div>
                <h1 class="mb-0">Modifier le produit</h1>
                <p class="text-muted mb-0">{{ $produit->nom }}</p>
            </div>
        </div>
        <div class="heading-actions">
            <a href="{{ route('admin.librairie.produits.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Retour
            </a>
        </div>
    </div>

    <form action="{{ route('admin.librairie.produits.update', $produit) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label for="nom" class="form-label fw-semibold">Nom du produit <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('nom') is-invalid @enderror"
                                   id="nom" name="nom" value="{{ old('nom', $produit->nom) }}" required>
                            @error('nom')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label fw-semibold">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description" name="description" rows="5">{{ old('description', $produit->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="categorie_id" class="form-label fw-semibold">Catégorie <span class="text-danger">*</span></label>
                                <select class="form-select @error('categorie_id') is-invalid @enderror"
                                        id="categorie_id" name="categorie_id" required>
                                    <option value="">Choisir une catégorie...</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}" {{ old('categorie_id', $produit->categorie_id) == $cat->id ? 'selected' : '' }}>
                                            {{ $cat->nom }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('categorie_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="prix" class="form-label fw-semibold">Prix (FCFA) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('prix') is-invalid @enderror"
                                       id="prix" name="prix" value="{{ old('prix', $produit->prix) }}" min="0" step="0.01" required>
                                @error('prix')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="frais_livraison" class="form-label fw-semibold">Frais livraison (FCFA)</label>
                                <input type="number" class="form-control @error('frais_livraison') is-invalid @enderror"
                                       id="frais_livraison" name="frais_livraison" value="{{ old('frais_livraison', $produit->frais_livraison) }}" min="0" step="0.01">
                                @error('frais_livraison')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3">Image du produit</h6>
                        <div class="mb-3">
                            <img src="{{ $produit->image_original }}" alt="{{ $produit->nom }}"
                                 style="width: 100%; height: 200px; object-fit: cover; border-radius: 8px;">
                            <div class="text-muted mt-1" style="font-size: 0.8rem;">Image actuelle</div>
                        </div>
                        <div class="mb-3">
                            <label for="image" class="form-label fw-semibold">Nouvelle image</label>
                            <input type="file" class="form-control @error('image') is-invalid @enderror"
                                   id="image" name="image" accept="image/*">
                            <div class="form-text">Laisser vide pour conserver l'image actuelle.</div>
                            @error('image')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3">Paramètres</h6>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                                {{ old('is_active', $produit->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Produit actif</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 mb-4">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg me-1"></i>Enregistrer
            </button>
            <a href="{{ route('admin.librairie.produits.index') }}" class="btn btn-outline-secondary">Annuler</a>
        </div>
    </form>

</div>

@endsection
