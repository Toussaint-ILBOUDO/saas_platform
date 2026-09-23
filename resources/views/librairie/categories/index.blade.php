@extends('panel.layouts.app')

@section('title', 'Catégories — Librairie')

@section('content')

<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon"><i class="bi bi-folder2"></i></div>
            <div>
                <h1 class="mb-0">Catégories de produits</h1>
                <p class="text-muted mb-0">Gestion des catégories de la librairie</p>
            </div>
        </div>
        <div class="heading-actions">
            <a href="{{ route('admin.librairie.categories.create') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Nouvelle catégorie
            </a>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header">
            <form action="{{ route('admin.librairie.categories.index') }}" method="GET" class="d-flex gap-2">
                <input type="text" name="search" class="form-control table-search"
                       placeholder="Rechercher..." value="{{ request('search') }}">
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
                            <th>Ordre</th>
                            <th>Nom</th>
                            <th class="text-center">Produits</th>
                            <th class="text-center">Statut</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $categorie)
                            <tr>
                                <td class="text-muted">{{ $categorie->ordre }}</td>
                                <td>
                                    <span class="fw-semibold">{{ $categorie->nom }}</span>
                                    @if($categorie->description)
                                        <div class="text-muted" style="font-size: 0.8rem;">{{ Str::limit($categorie->description, 60) }}</div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary-subtle text-primary">{{ $categorie->produits_count }}</span>
                                </td>
                                <td class="text-center">
                                    @if($categorie->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <a href="{{ route('admin.librairie.categories.edit', $categorie) }}"
                                           class="btn btn-sm btn-light" title="Modifier">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('admin.librairie.categories.destroy', $categorie) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Supprimer cette catégorie ?');">
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
                                <td colspan="5" class="text-center py-5">
                                    <div class="blank-state">
                                        <i class="bi bi-folder2 display-4 text-muted"></i>
                                        <p class="text-muted mt-2">Aucune catégorie.</p>
                                        <a href="{{ route('admin.librairie.categories.create') }}" class="btn btn-primary btn-sm">
                                            Créer une catégorie
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($categories->hasPages())
            <div class="panel-footer">
                {{ $categories->links() }}
            </div>
        @endif
    </div>

</div>

@endsection
