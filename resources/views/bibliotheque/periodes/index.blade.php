@extends('panel.layouts.app')

@section('title', 'Périodes des documents')

@section('content')

<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon">
                <i class="bi bi-calendar3"></i>
            </div>
            <div>
                <h1 class="mb-0">Périodes des documents</h1>
                <p class="text-muted mb-0">Gestion des périodes disponibles</p>
            </div>
        </div>

        <div class="heading-actions">
            <a href="{{ route('admin.bibliotheque.periodes.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i>
                Nouvelle période
            </a>
        </div>
    </div>

    <div class="panel">

        <div class="panel-header">
            <div>
                <h5 class="mb-0">Liste des périodes</h5>
                <p class="text-muted mb-0">Toutes les périodes configurées</p>
            </div>
        </div>

        <div class="table-responsive">

            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Libellé</th>
                        <th>Sigle</th>
                        <th>Créé</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>

                <tbody>
                @forelse($periodes as $periode)
                    <tr>
                        <td>
                            <strong>{{ $periode->nom }}</strong>
                        </td>

                        <td>
                            <span class="badge text-bg-secondary">
                                {{ $periode->sigle ?? '-' }}
                            </span>
                        </td>

                        <td class="text-muted">
                            {{ $periode->created_at?->format('d/m/Y') }}
                        </td>

                        <td class="text-end">
                            <a href="{{ route('admin.bibliotheque.periodes.edit', $periode) }}"
                               class="btn btn-sm btn-light">
                                <i class="bi bi-pencil-square"></i>
                            </a>

                            <form action="{{ route('admin.bibliotheque.periodes.destroy', $periode) }}"
                                  method="POST"
                                  class="d-inline"
                                  onsubmit="return confirm('Supprimer cette période ?')">
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
                        <td colspan="4">
                            <div class="blank-panel">
                                <div class="blank-state">
                                    <i class="bi bi-inbox display-5 text-muted"></i>
                                    <p class="mt-2 text-muted">Aucune période</p>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>

            </table>

        </div>

        <div class="mt-3">
            {{ $periodes->links() }}
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
