@extends('landlord.layouts.app')

@section('title', 'Cabinets')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold mb-0">Cabinets</h1>
        <a href="{{ route('landlord.cabinets.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i>Nouveau cabinet
        </a>
    </div>

    <form method="GET" action="{{ route('landlord.cabinets.index') }}" class="row g-2 mb-3">
        <div class="col-md-6 col-lg-4">
            <input type="search" name="q" value="{{ $q }}" class="form-control" placeholder="Rechercher (nom, slug)…">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-outline-secondary">Filtrer</button>
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Slug</th>
                        <th>Nom</th>
                        <th>Domaine</th>
                        <th>Statut</th>
                        <th>Abonnement</th>
                        <th>Créé le</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($cabinets as $cabinet)
                        <tr>
                            <td><code>{{ $cabinet->id }}</code></td>
                            <td class="fw-semibold">{{ $cabinet->nom }}</td>
                            <td>{{ $cabinet->primary_domain ?: '—' }}</td>
                            <td>
                                @if ($cabinet->status === 'actif')
                                    <span class="badge text-bg-success">Actif</span>
                                @else
                                    <span class="badge text-bg-danger">Suspendu</span>
                                @endif
                            </td>
                            <td>{{ number_format($cabinet->parametres?->tarif_abonnement ?? 0, 2, ',', ' ') }} FCFA</td>
                            <td>{{ $cabinet->created_at?->format('d/m/Y') ?: '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('landlord.cabinets.show', $cabinet) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('landlord.cabinets.edit', $cabinet) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-4">Aucun cabinet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $cabinets->links() }}
    </div>
@endsection