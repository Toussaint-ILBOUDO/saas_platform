@extends('landlord.layouts.app')

@section('title', ($contrat === null ? 'Contrats' : 'Contrat '.$contrat->numero).' — '.($cabinet->nom ?? ''))

@section('content')
    <h1 class="h3 fw-bold mb-1">
        <a href="{{ route('landlord.cabinets.show', $cabinet) }}" class="text-decoration-none text-muted me-1">&laquo;</a>
        Contrat commercial
    </h1>
    <div class="small text-muted mb-3">{{ $cabinet->nom }} ({{ $cabinet->id }})</div>

    {{-- Navig durée des versions : listes déroulantes par numéro/version/statut --}}
    @if ($contrats !== [])
        <div class="d-flex flex-wrap gap-2 mb-3">
            @foreach ($contrats as $c)
                <a href="{{ route('landlord.contrats.show', [$cabinet, $c]) }}"
                   class="btn btn-sm {{ ($contrat !== null && $contrat->id === $c->id) ? 'btn-primary' : 'btn-outline-secondary' }}">
                    {{ $c->numero }} · v{{ $c->version }}
                    <span class="badge {{ $c->statut === 'valide' ? 'bg-success' : ($c->statut === 'archive' ? 'bg-secondary' : 'bg-warning text-dark') }}">
                        {{ $c->statut }}
                    </span>
                </a>
            @endforeach
        </div>
    @endif

    <div class="mb-3">
        <form method="POST" action="{{ route('landlord.contrats.store', $cabinet) }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Nouvelle version (brouillon)
            </button>
        </form>
    </div>

    {{-- Détail de la version ---
         * brouillon : formulaire complet (0 = gratuit négocié, vide = non couvert)
         * validé / archive : affichage lecture seule + PDF --}}
    @if ($contrat !== null)
        <div class="card p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0">
                    Contrat {{ $contrat->numero }} — version {{ $contrat->version }}
                    <span class="badge {{ $contrat->statut === 'valide' ? 'bg-success' : ($contrat->statut === 'archive' ? 'bg-secondary' : 'bg-warning text-dark') }} ms-2">
                        {{ $contrat->statut }}
                    </span>
                </h6>

                <div class="d-flex gap-2">
                    @if ($contrat->estBrouillon())
                        <form method="POST" action="{{ route('landlord.contrats.valider', [$cabinet, $contrat]) }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm"
                                onclick="return confirm('Valider ce contrat ? Les versions valides précédentes seront archivées et les montants figés.')">
                                Valider
                            </button>
                        </form>
                    @endif

                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('landlord.contrats.pdf', [$cabinet, $contrat]) }}" target="_blank">
                        <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                    </a>

                    @if ($contrat->estBrouillon())
                        <form method="POST" action="{{ route('landlord.contrats.destroy', [$cabinet, $contrat]) }}" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Supprimer ce brouillon ?')">
                                Supprimer
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        @if ($contrat->estBrouillon())
            <form method="POST" action="{{ route('landlord.contrats.update', [$cabinet, $contrat]) }}">
                @csrf
                @method('PUT')
                @include('landlord.contrats.form', ['contrat' => $contrat, 'services' => $services])
            </form>
        @else
            @include('landlord.contrats.form', ['contrat' => $contrat, 'services' => $services, 'lectureSeule' => true])
        @endif
    @endif
@endsection
