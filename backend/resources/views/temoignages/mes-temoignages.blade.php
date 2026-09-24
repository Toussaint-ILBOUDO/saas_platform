@extends('panel.layouts.app')

@section('title', 'Mes témoignages')

@section('content')

<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon"><i class="bi bi-chat-quote"></i></div>
            <div>
                <h1 class="mb-0">Mes témoignages</h1>
                <p class="text-muted mb-0">Les témoignages que vous avez publiés</p>
            </div>
        </div>
        <div class="heading-actions">
            <a href="{{ route('temoignages.create') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Nouveau témoignage
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="panel">
        <div class="panel-header">
            <div>
                <h5 class="mb-0">Liste de mes témoignages</h5>
                <p class="text-muted mb-0">Vous pouvez modifier ou retirer un témoignage à tout moment</p>
            </div>
        </div>
        <div class="p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Témoignage</th>
                            <th class="text-center">Anonyme</th>
                            <th class="text-center">Statut</th>
                            <th class="text-center">Réactions</th>
                            <th class="text-center">Publié le</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($temoignages as $temoignage)
                            <tr>
                                <td style="max-width: 420px;">
                                    <span class="d-block text-truncate">{{ $temoignage->contenu }}</span>
                                    <div class="text-muted" style="font-size: 0.8rem;">/{{ $temoignage->slug }}</div>
                                </td>
                                <td class="text-center">
                                    @if($temoignage->anonyme)
                                        <span class="badge bg-primary-subtle text-primary">Anonyme</span>
                                    @else
                                        <span class="badge bg-light text-muted">Nom affiché</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($temoignage->est_publie)
                                        <span class="badge bg-success">Publié</span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning">Masqué</span>
                                    @endif
                                </td>
                                <td class="text-center">{{ number_format($temoignage->reactions_count) }}</td>
                                <td class="text-center text-muted">
                                    {{ $temoignage->published_at?->format('d/m/Y') ?? '—' }}
                                </td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        @if($temoignage->est_publie)
                                            <a href="{{ route('temoignages.show', $temoignage->slug) }}" target="_blank"
                                               class="btn btn-sm btn-light" title="Voir sur le site">
                                                <i class="bi bi-box-arrow-up-right"></i>
                                            </a>
                                        @endif
                                        <a href="{{ route('temoignages.edit', $temoignage) }}"
                                           class="btn btn-sm btn-light" title="Modifier">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('temoignages.destroy', $temoignage) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Supprimer ce témoignage ?');">
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
                                        <i class="bi bi-chat-quote display-4 text-muted"></i>
                                        <p class="text-muted mt-2">Vous n'avez pas encore publié de témoignage.</p>
                                        <a href="{{ route('temoignages.create') }}" class="btn btn-primary btn-sm">
                                            Partager mon expérience
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($temoignages->hasPages())
            <div class="panel-footer">
                {{ $temoignages->links() }}
            </div>
        @endif
    </div>

</div>

@endsection
