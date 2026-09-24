@extends('panel.layouts.app')

@section('title', 'Signalements — Bibliothèque')

@section('content')

<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon"><i class="bi bi-flag"></i></div>
            <div>
                <h1 class="mb-0">Signalements</h1>
                <p class="text-muted mb-0">Documents signalés par les utilisateurs</p>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Document</th>
                            <th>Signalé par</th>
                            <th>Motif</th>
                            <th>Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($signalements as $signalement)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.bibliotheque.show', $signalement->document?->id) }}"
                                       class="fw-semibold text-decoration-none" style="color: var(--heading-color);">
                                        {{ Str::limit($signalement->document?->titre ?? 'Document supprimé', 40) }}
                                    </a>
                                    <div class="text-muted" style="font-size: 0.8rem;">
                                        par {{ $signalement->document?->auteur?->prenom }} {{ $signalement->document?->auteur?->nom }}
                                    </div>
                                </td>
                                <td>{{ $signalement->user?->prenom }} {{ $signalement->user?->nom }}</td>
                                <td>
                                    <strong>{{ $signalement->motif }}</strong>
                                    @if($signalement->description)
                                        <div class="text-muted" style="font-size: 0.8rem;">
                                            {{ Str::limit($signalement->description, 80) }}
                                        </div>
                                    @endif
                                </td>
                                <td>{{ $signalement->created_at->format('d/m/Y H:i') }}</td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <a href="{{ route('admin.bibliotheque.show', $signalement->document?->id) }}"
                                           class="btn btn-sm btn-light" title="Voir le document">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <form action="{{ route('admin.bibliotheque.signalements.traiter', $signalement->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success" title="Marquer comme traité">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="blank-state">
                                        <i class="bi bi-flag display-4 text-muted"></i>
                                        <p class="text-muted mt-2">Aucun signalement en attente.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($signalements->hasPages())
            <div class="panel-footer">
                {{ $signalements->links() }}
            </div>
        @endif
    </div>

</div>

@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assetBiblio/css/bibliotheque.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('assetBiblio/js/bibliotheque.js') }}"></script>
@endpush
