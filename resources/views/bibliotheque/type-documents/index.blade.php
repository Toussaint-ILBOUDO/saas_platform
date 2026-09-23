@extends('panel.layouts.app')

@section('title', 'Types de document')

@section('content')

<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon">
                <i class="bi bi-file-earmark-medical"></i>
            </div>
            <div>
                <h1 class="mb-0">Types de document</h1>
                <p class="text-muted mb-0">Gestion des types de documents disponibles</p>
            </div>
        </div>

        <div class="heading-actions">
            <a href="{{ route('admin.bibliotheque.type-documents.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i>
                Nouveau type
            </a>
        </div>
    </div>

    <div class="panel">

        <div class="panel-header">
            <div>
                <h5 class="mb-0">Liste des types</h5>
                <p class="text-muted mb-0">Tous les types de documents configurés</p>
            </div>
        </div>

        <div class="table-responsive">

            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Libellé</th>
                        <th>Sigle</th>
                        <th class="text-center">Documents</th>
                        <th>Créé</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>

                <tbody>
                @forelse($typesDocument as $type)
                    <tr>
                        <td>
                            <strong>{{ $type->nom }}</strong>
                        </td>

                        <td>
                            <span class="badge text-bg-secondary">
                                {{ $type->sigle ?? '-' }}
                            </span>
                        </td>

                        <td class="text-center">
                            <span class="badge bg-primary-subtle text-primary">
                                {{ $type->documents_count ?? $type->documents()->count() }}
                            </span>
                        </td>

                        <td class="text-muted">
                            {{ $type->created_at?->format('d/m/Y') }}
                        </td>

                        <td class="text-end">
                            <a href="{{ route('admin.bibliotheque.type-documents.edit', $type) }}"
                               class="btn btn-sm btn-light">
                                <i class="bi bi-pencil-square"></i>
                            </a>

                            <form action="{{ route('admin.bibliotheque.type-documents.destroy', $type) }}"
                                  method="POST"
                                  class="d-inline"
                                  onsubmit="return confirm('Supprimer ce type de document ?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-light text-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <div class="blank-panel">
                                <div class="blank-state">
                                    <i class="bi bi-inbox display-5 text-muted"></i>
                                    <p class="mt-2 text-muted">Aucun type de document</p>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>

            </table>

        </div>

        <div class="mt-3">
            {{ $typesDocument->links() }}
        </div>

    </div>

</div>

@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assetBiblio/css/bibliotheque.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('assetBiblio/js/bibliotheque.js') }}"></script>
@endpush
