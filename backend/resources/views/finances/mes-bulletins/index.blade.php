@extends('panel.layouts.app')

@section('title', 'Mes bulletins de paie')

@section('content')
<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon"><i class="bi bi-cash-stack"></i></div>
            <div>
                <h1 class="mb-0">Mes bulletins de paie</h1>
                <p class="text-muted mb-0">Consultez vos bulletins de paie par periode.</p>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- FILTRES --}}
    <div class="panel mb-4">
        <div class="panel-body">
            <form method="GET" action="{{ route('mes-bulletins.index') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Periode</label>
                    <select name="periode_id" class="form-select">
                        <option value="">Toutes les periodes</option>
                        @foreach($periodes as $periode)
                            <option value="{{ $periode->id }}"
                                {{ $periodeSelectionnee?->id === $periode->id ? 'selected' : '' }}>
                                {{ $periode->label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Statut</label>
                    <select name="statut" class="form-select">
                        <option value="">Tous les statuts</option>
                        <option value="genere" {{ request('statut') === 'genere' ? 'selected' : '' }}>Genere</option>
                        <option value="consulte" {{ request('statut') === 'consulte' ? 'selected' : '' }}>Consulte</option>
                        <option value="valide" {{ request('statut') === 'valide' ? 'selected' : '' }}>Valide</option>
                        <option value="verse" {{ request('statut') === 'verse' ? 'selected' : '' }}>Verse</option>
                        <option value="conteste" {{ request('statut') === 'conteste' ? 'selected' : '' }}>Conteste</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel"></i> Filtrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- TABLEAU --}}
    <div class="panel">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Numero</th>
                        <th>Periode</th>
                        <th class="text-end">Heures</th>
                        <th class="text-end">Montant brut</th>
                        <th class="text-end">Net a payer</th>
                        <th>Statut</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bulletins as $bulletin)
                        <tr>
                            <td>
                                <strong>{{ $bulletin->numero }}</strong>
                            </td>
                            <td>{{ $bulletin->periode?->label ?? '—' }}</td>
                            <td class="text-end">{{ number_format($bulletin->total_heures, 2) }} h</td>
                            <td class="text-end">{{ number_format($bulletin->montant_brut, 0, ',', ' ') }} FCFA</td>
                            <td class="text-end">
                                <strong>{{ number_format($bulletin->montant_net, 0, ',', ' ') }} FCFA</strong>
                            </td>
                            <td>
                                @switch($bulletin->statut)
                                    @case('brouillon')
                                        <span class="badge text-bg-secondary">Brouillon</span>
                                        @break
                                    @case('genere')
                                        <span class="badge text-bg-info">Genere</span>
                                        @break
                                    @case('consulte')
                                        <span class="badge text-bg-primary">Consulte</span>
                                        @break
                                    @case('valide')
                                        <span class="badge text-bg-success">Valide</span>
                                        @break
                                    @case('verse')
                                        <span class="badge text-bg-dark">Verse</span>
                                        @break
                                    @case('conteste')
                                        <span class="badge text-bg-danger">Conteste</span>
                                        @break
                                    @case('corrige')
                                        <span class="badge text-bg-warning">Corrige</span>
                                        @break
                                @endswitch
                            </td>
                            <td class="text-end">
                                <a href="{{ route('mes-bulletins.show', $bulletin) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> Voir
                                </a>
                                <a href="{{ route('mes-bulletins.pdf', $bulletin) }}"
                                   class="btn btn-sm btn-outline-secondary" target="_blank">
                                    <i class="bi bi-file-earmark-pdf"></i> PDF
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-muted text-center py-4">
                                Aucun bulletin de paie disponible.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="panel-footer">
            {{ $bulletins->links() }}
        </div>
    </div>

</div>
@endsection
