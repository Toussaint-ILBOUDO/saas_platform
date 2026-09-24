@extends('panel.layouts.app')

@section('title', 'Produits — Librairie')

@section('content')

<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon"><i class="bi bi-box-seam"></i></div>
            <div>
                <h1 class="mb-0">Produits</h1>
                <p class="text-muted mb-0">Gestion des produits de la librairie</p>
            </div>
        </div>
        <div class="heading-actions">
            <a href="{{ route('admin.librairie.produits.create') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Nouveau produit
            </a>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header">
            <form action="{{ route('admin.librairie.produits.index') }}" method="GET" class="d-flex gap-2 flex-wrap">
                <input type="text" name="search" class="form-control table-search"
                       placeholder="Rechercher..." value="{{ $filters['search'] ?? '' }}">
                <select name="categorie_id" class="form-select" style="max-width: 200px;" onchange="this.form.submit()">
                    <option value="">Toutes les catégories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ ($filters['categorie_id'] ?? '') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->nom }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-sm btn-light">
                    <i class="bi bi-search"></i>
                </button>
            </form>
        </div>
        <div class="p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="min-width: 60px;">Image</th>
                            <th style="min-width: 200px;">Produit</th>
                            <th>Catégorie</th>
                            <th class="text-end">Prix</th>
                            <th class="text-center">Statut</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($produits as $produit)
                            <tr>
                                <td>
                                        <img src="{{ $produit->image_url }}" alt="{{ $produit->nom }}" class="lib-thumb-img">
                                </td>
                                <td>
                                    <span class="fw-semibold">{{ Str::limit($produit->nom, 50) }}</span>
                                    <div class="text-muted" style="font-size: 0.8rem;">{{ $produit->created_at->format('d/m/Y') }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary">{{ $produit->categorie?->nom ?? '-' }}</span>
                                </td>
                                <td class="text-end fw-bold">{{ number_format((float) $produit->prix, 0, ',', ' ') }} FCFA</td>
                                <td class="text-center">
                                    @if($produit->is_active)
                                        <span class="badge bg-success">Actif</span>
                                    @else
                                        <span class="badge bg-secondary">Inactif</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <a href="{{ route('admin.librairie.produits.edit', $produit) }}"
                                           class="btn btn-sm btn-light" title="Modifier">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('admin.librairie.produits.destroy', $produit) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Supprimer ce produit ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light text-danger" title="Supprimer">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="blank-state">
                                        <i class="bi bi-box-seam display-4 text-muted"></i>
                                        <p class="text-muted mt-2">Aucun produit.</p>
                                        <a href="{{ route('admin.librairie.produits.create') }}" class="btn btn-primary btn-sm">
                                            Créer un produit
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($produits->hasPages())
            <div class="panel-footer">
                {{ $produits->links() }}
            </div>
        @endif
    </div>

</div>

@endsection
