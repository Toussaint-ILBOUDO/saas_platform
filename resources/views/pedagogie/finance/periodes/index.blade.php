@extends('panel.layouts.app')

@section('title', 'Périodes comptables')

@section('content')

<div class="container-fluid">

    <div class="page-heading">

        <div class="page-heading-copy">
            <div class="page-icon">
                <i class="bi bi-calendar3"></i>
            </div>

            <div>
                <h1 class="mb-0">Périodes comptables</h1>
                <p class="text-muted mb-0">Gestion des périodes financières</p>
            </div>
        </div>

        <div class="heading-actions">
            <a href="{{ route('finance.periodes.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i>
                Nouvelle période
            </a>
        </div>

    </div>


    <div class="row g-3 mb-4">

        <div class="col-md-4">
            <div class="metric-card metric-primary">

                <div class="d-flex justify-content-between">
                    <div>
                        <div class="metric-label">Total</div>
                        <div class="metric-value">{{ $periodes->count() }}</div>
                    </div>

                    <div class="metric-icon">
                        <i class="bi bi-calendar"></i>
                    </div>
                </div>

            </div>
        </div>


        <div class="col-md-4">
            <div class="metric-card metric-success">

                <div class="d-flex justify-content-between">
                    <div>
                        <div class="metric-label">Ouvertes</div>
                        <div class="metric-value">
                            {{ $periodes->where('statut','ouverte')->count() }}
                        </div>
                    </div>

                    <div class="metric-icon">
                        <i class="bi bi-unlock"></i>
                    </div>
                </div>

            </div>
        </div>


        <div class="col-md-4">
            <div class="metric-card metric-danger">

                <div class="d-flex justify-content-between">
                    <div>
                        <div class="metric-label">Clôturées</div>
                        <div class="metric-value">
                            {{ $periodes->where('statut','cloturee')->count() }}
                        </div>
                    </div>

                    <div class="metric-icon">
                        <i class="bi bi-lock"></i>
                    </div>
                </div>

            </div>
        </div>

    </div>


    <div class="panel">

        <div class="panel-header">
            <div>
                <h5 class="mb-0">Liste des périodes</h5>
                <p class="text-muted mb-0">Toutes les périodes comptables</p>
            </div>
        </div>


        <div class="table-responsive">

            <table class="table align-middle">

                <thead>
                    <tr>
                        <th>Libellé</th>
                        <th>Début</th>
                        <th>Fin</th>
                        <th>Type</th>
                        <th>Statut</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>


                <tbody>

                @forelse($periodes as $periode)

                    <tr>

                        <td>
                            <strong>{{ $periode->label }}</strong>
                        </td>

                        <td>
                            {{ $periode->date_debut?->format('d/m/Y') }}
                        </td>

                        <td>
                            {{ $periode->date_fin?->format('d/m/Y') }}
                        </td>

                        <td>
                            <span class="badge text-bg-secondary">
                                {{ ucfirst($periode->type) }}
                            </span>
                        </td>

                        <td>

                            @if($periode->statut === 'ouverte')
                                <span class="badge text-bg-success">
                                    Ouverte
                                </span>
                            @else
                                <span class="badge text-bg-danger">
                                    Clôturée
                                </span>
                            @endif

                        </td>


                        <td class="text-end">

                            <a href="{{ route('finance.periodes.show',$periode) }}" class="btn btn-sm btn-light">
                                <i class="bi bi-eye"></i>
                            </a>

                            <a href="{{ route('finance.periodes.edit',$periode) }}" class="btn btn-sm btn-light">
                                <i class="bi bi-pencil-square"></i>
                            </a>

                            @if($periode->statut === 'ouverte')

                            <form action="{{ route('finance.periodes.close',$periode) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Clôturer cette période ? Cette action est irréversible.');">
                                @csrf
                                @method('PATCH')

                                <button type="submit" class="btn btn-sm btn-light text-danger" title="Clôturer la période">
                                    <i class="bi bi-lock"></i>
                                </button>

                            </form>

                            @endif

                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="6">

                            <div class="blank-panel">
                                <div class="blank-state">
                                    <i class="bi bi-calendar-x display-5 text-muted"></i>
                                    <p class="mt-2 text-muted">
                                        Aucune période comptable
                                    </p>
                                </div>
                            </div>

                        </td>
                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>

@endsection