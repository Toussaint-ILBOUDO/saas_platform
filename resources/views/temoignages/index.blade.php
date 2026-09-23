@extends('panel.layouts.app')

@section('title', 'Témoignages — Administration')

@section('content')

<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon"><i class="bi bi-chat-quote"></i></div>
            <div>
                <h1 class="mb-0">Témoignages</h1>
                <p class="text-muted mb-0">Modération des témoignages du site public</p>
            </div>
        </div>
        <div class="heading-actions">
            <a href="{{ route('admin.temoignages.signalements') }}" class="btn btn-sm btn-outline-warning">
                <i class="bi bi-flag me-1"></i>Signalements
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="panel mb-3">
        <div class="panel-body">
            <form method="GET" action="{{ route('admin.temoignages.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label for="search" class="form-label small text-muted mb-1">Recherche</label>
                    <input type="text" name="search" id="search" value="{{ $recherche }}"
                           class="form-control form-control-sm" placeholder="Contenu du témoignage...">
                </div>
                <div class="col-md-3">
                    <label for="statut" class="form-label small text-muted mb-1">Statut</label>
                    <select name="statut" id="statut" class="form-select form-select-sm">
                        <option value="">Tous les statuts</option>
                        <option value="publie" @selected($filtreStatut === 'publie')>Publié</option>
                        <option value="masque" @selected($filtreStatut === 'masque')>Masqué</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-search me-1"></i>Filtrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header">
            <div>
                <h5 class="mb-0">Liste des témoignages</h5>
                <p class="text-muted mb-0">Masquez, restaurez ou supprimez les témoignages</p>
            </div>
        </div>
        <div class="p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Témoignage</th>
                            <th>Auteur</th>
                            <th class="text-center">Score</th>
                            <th class="text-center">Réactions</th>
                            <th class="text-center">Commentaires</th>
                            <th class="text-center">Signalements</th>
                            <th class="text-center">Statut</th>
                            <th class="text-center">Publié le</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($temoignages as $temoignage)
                            <tr>
                                <td style="max-width: 380px;">
                                    <span class="d-block text-truncate">{{ $temoignage->contenu }}</span>
                                    <div class="text-muted" style="font-size: 0.8rem;">/{{ $temoignage->slug }}</div>
                                </td>
                                <td>
                                    @if($temoignage->anonyme)
                                        <span class="badge bg-primary-subtle text-primary">Anonyme</span>
                                        <div class="text-muted" style="font-size: 0.8rem;">
                                            {{ $temoignage->auteur?->prenom }} {{ $temoignage->auteur?->nom }}
                                        </div>
                                    @else
                                        {{ $temoignage->auteur?->prenom }} {{ $temoignage->auteur?->nom }}
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-warning-subtle text-warning">
                                        <i class="bi bi-stars me-1"></i>{{ $temoignage->score }}
                                    </span>
                                </td>
                                <td class="text-center">{{ number_format($temoignage->reactions_count) }}</td>
                                <td class="text-center">{{ number_format($temoignage->commentaires_count) }}</td>
                                <td class="text-center">
                                    @if($temoignage->signalements_en_attente > 0)
                                        <span class="badge bg-danger">{{ $temoignage->signalements_en_attente }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($temoignage->est_publie)
                                        <span class="badge bg-success">Publié</span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning">Masqué</span>
                                    @endif
                                </td>
                                <td class="text-center text-muted">
                                    {{ $temoignage->published_at?->format('d/m/Y') ?? '—' }}
                                </td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <a href="{{ route('admin.temoignages.show', $temoignage) }}"
                                           class="btn btn-sm btn-light" title="Détail">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @if($temoignage->est_publie)
                                            <form action="{{ route('admin.temoignages.masquer', $temoignage) }}" method="POST" class="d-inline"
                                                  onsubmit="return confirm('Masquer ce témoignage et avertir son auteur ?');">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-light" title="Masquer">
                                                    <i class="bi bi-eye-slash"></i>
                                                </button>
                                            </form>
                                        @else
                                            <form action="{{ route('admin.temoignages.restaurer', $temoignage) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-light" title="Restaurer">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                            </form>
                                        @endif
                                        <form action="{{ route('admin.temoignages.destroy', $temoignage) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Supprimer définitivement ce témoignage et avertir son auteur ?');">
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
                                <td colspan="9" class="text-center py-5">
                                    <div class="blank-state">
                                        <i class="bi bi-chat-quote display-4 text-muted"></i>
                                        <p class="text-muted mt-2">Aucun témoignage.</p>
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
