@extends('landlord.layouts.app')

@section('title', 'Journal de la plateforme')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold mb-0">Journal de la plateforme</h1>
        <a href="{{ route('landlord.journal.index') }}" class="btn btn-outline-secondary btn-sm">Réinitialiser les filtres</a>
    </div>

    <form method="GET" action="{{ route('landlord.journal.index') }}" class="card shadow-sm mb-4">
        <div class="card-body d-flex flex-wrap gap-2 align-items-end">
            <div class="flex-grow-1" style="min-width:200px">
                <label class="form-label small text-secondary mb-1">Action (fragment, ex. cabinet.)</label>
                <input type="text" name="action" value="{{ request('action') }}" class="form-control form-control-sm">
            </div>
            <div style="min-width:150px">
                <label class="form-label small text-secondary mb-1">Niveau</label>
                <select name="niveau" class="form-select form-select-sm">
                    <option value="">Tous</option>
                    @foreach ($niveaux as $n)
                        <option value="{{ $n }}" @selected(request('niveau') === $n)>{{ $n }}</option>
                    @endforeach
                </select>
            </div>
            <div style="min-width:150px">
                <label class="form-label small text-secondary mb-1">Cabinet (id)</label>
                <input type="text" name="cabinet" value="{{ request('cabinet') }}" class="form-control form-control-sm">
            </div>
            <button class="btn btn-primary btn-sm">Filtrer</button>
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Niveau</th>
                        <th>Action</th>
                        <th>Cabinet</th>
                        <th>Super admin</th>
                        <th>Contexte</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($entrees as $entree)
                        <tr>
                            <td class="text-nowrap small">
                                {{ $entree->created_at?->format('d/m/Y H:i:s') ?: '—' }}
                            </td>
                            <td>
                                @if ($entree->level === 'error')
                                    <span class="badge text-bg-danger">error</span>
                                @elseif ($entree->level === 'warning')
                                    <span class="badge text-bg-warning">warning</span>
                                @else
                                    <span class="badge text-bg-info">info</span>
                                @endif
                            </td>
                            <td>
                                <code>{{ $entree->action }}</code>
                            </td>
                            <td>
                                @if ($entree->cabinet)
                                    <a href="{{ route('landlord.cabinets.show', $entree->cabinet) }}">
                                        {{ $entree->cabinet->nom }} <code class="small text-secondary">{{ $entree->cabinet->id }}</code>
                                    </a>
                                @else
                                    <span class="text-secondary">{{ $entree->cabinet_id ?: '—' }}</span>
                                @endif
                            </td>
                            <td class="small">
                                {{ $entree->super_admin_id ? '#' . $entree->super_admin_id : '—' }}
                            </td>
                            <td>
                                @if ($entree->contexte)
                                    <details>
                                        <summary class="small link-secondary" style="cursor:pointer">détails</summary>
                                        <pre class="small bg-light p-2 rounded mt-1 mb-0">{{ json_encode($entree->contexte, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </details>
                                @else
                                    <span class="text-secondary">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-4">Aucune entrée au journal.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($entrees->hasPages())
            <div class="card-footer">
                {{ $entrees->links() }}
            </div>
        @endif
    </div>
@endsection