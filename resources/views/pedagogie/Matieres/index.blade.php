@extends('panel.layouts.app')

@section('title', 'Matières')

@section('content')

<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">

        <div>
            <h1 class="h3 mb-1">
                Matières
            </h1>

            <p class="text-muted mb-0">
                Gestion des matières enseignées.
            </p>
        </div>

        <a href="{{ route('matieres.create') }}"
           class="btn btn-primary">

            <i class="bi bi-plus-circle me-1"></i>
            Nouvelle matière

        </a>

    </div>

    {{-- Alert --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">

            {{ session('success') }}

            <button type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"></button>

        </div>
    @endif

    {{-- Stats --}}
    <div class="row g-3 mb-4">

        <div class="col-md-4">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between">

                        <div>
                            <div class="text-muted small">
                                Total matières
                            </div>

                            <div class="fs-2 fw-bold">
                                {{ $matieres->total() }}
                            </div>
                        </div>

                        <i class="bi bi-book fs-1 text-primary"></i>

                    </div>

                </div>

            </div>

        </div>

        <div class="col-md-4">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="text-muted small">
                        Page actuelle
                    </div>

                    <div class="fs-2 fw-bold">
                        {{ $matieres->count() }}
                    </div>

                </div>

            </div>

        </div>

        <div class="col-md-4">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="text-muted small">
                        Dernière page
                    </div>

                    <div class="fs-2 fw-bold">
                        {{ $matieres->lastPage() }}
                    </div>

                </div>

            </div>

        </div>

    </div>

    {{-- Tableau --}}
    <div class="card border-0 shadow-sm">

        <div class="card-header bg-transparent">

            <h5 class="mb-0">
                Liste des matières
            </h5>

        </div>

        <div class="table-responsive">

            <table class="table align-middle mb-0">

                <thead>

                <tr>
                    <th>#</th>
                    <th>Nom</th>
                    <th>Sigle</th>
                    <th>Enseignants</th>
                    <th>Affectations</th>
                    <th>Actions</th>
                </tr>

                </thead>

                <tbody>

                @forelse($matieres as $matiere)

                    <tr>

                        <td>
                            {{ $matiere->id }}
                        </td>

                        <td>

                            <div class="fw-semibold">
                                {{ $matiere->nom }}
                            </div>

                            @if($matiere->description)
                                <small class="text-muted">
                                    {{ Str::limit($matiere->description, 80) }}
                                </small>
                            @endif

                        </td>

                        <td>
                            {{ $matiere->sigle }}
                        </td>

                        <td>

                            <span class="badge bg-primary">

                                {{ $matiere->enseignants_count }}

                            </span>

                        </td>

                        <td>

                            <span class="badge bg-success">

                                {{ $matiere->affectations_count }}

                            </span>

                        </td>

                        <td>

                            <div class="d-flex gap-2">

                                <a href="{{ route('matieres.edit', $matiere) }}"
                                   class="btn btn-sm btn-outline-primary">

                                    <i class="bi bi-pencil-square"></i>

                                </a>

                                <form action="{{ route('matieres.destroy', $matiere) }}"
                                      method="POST">

                                    @csrf
                                    @method('DELETE')

                                    <button type="submit"
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Supprimer cette matière ?')">

                                        <i class="bi bi-trash"></i>

                                    </button>

                                </form>

                            </div>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="6"
                            class="text-center py-5 text-muted">

                            Aucune matière enregistrée.

                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

        @if($matieres->hasPages())

            <div class="card-footer bg-white">

                {{ $matieres->links() }}

            </div>

        @endif

    </div>

</div>

@endsection