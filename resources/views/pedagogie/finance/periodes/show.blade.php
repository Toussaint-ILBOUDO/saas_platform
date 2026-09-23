@extends('panel.layouts.app')

@section('title', 'Détail période comptable')

@section('content')

<div class="container-fluid">

    <div class="page-heading">

        <div class="page-heading-copy">

            <div class="page-icon">
                <i class="bi bi-calendar-check"></i>
            </div>

            <div>
                <h1 class="mb-0">{{ $periode->label }}</h1>
                <p class="text-muted mb-0">
                    Informations détaillées de la période
                </p>
            </div>

        </div>


        <div class="heading-actions">

            <a href="{{ route('finance.periodes.edit',$periode) }}"
               class="btn btn-primary">

                <i class="bi bi-pencil-square"></i>
                Modifier

            </a>

        </div>

    </div>


    <div class="row g-4">


        <div class="col-lg-8">

            <div class="panel">

                <div class="panel-header">

                    <div>
                        <h5 class="mb-0">
                            Informations générales
                        </h5>

                        <p class="text-muted mb-0">
                            Détails de la période comptable
                        </p>
                    </div>

                </div>


                <div class="info-list">

                    <div>
                        <span>Libellé</span>
                        <strong>{{ $periode->label }}</strong>
                    </div>


                    <div>
                        <span>Date début</span>
                        <strong>
                            {{ $periode->date_debut?->format('d/m/Y') }}
                        </strong>
                    </div>


                    <div>
                        <span>Date fin</span>
                        <strong>
                            {{ $periode->date_fin?->format('d/m/Y') }}
                        </strong>
                    </div>


                    <div>
                        <span>Type</span>
                        <strong>
                            {{ ucfirst($periode->type) }}
                        </strong>
                    </div>


                    <div>
                        <span>Statut</span>

                        @if($periode->statut === 'ouverte')

                            <span class="badge text-bg-success">
                                Ouverte
                            </span>

                        @else

                            <span class="badge text-bg-danger">
                                Clôturée
                            </span>

                        @endif

                    </div>


                    <div>
                        <span>Créée le</span>

                        <strong>
                            {{ $periode->created_at?->format('d/m/Y H:i') }}
                        </strong>

                    </div>


                </div>

            </div>

        </div>


        <div class="col-lg-4">


            <div class="panel">


                <div class="panel-header">

                    <div>
                        <h5 class="mb-0">
                            Actions
                        </h5>

                        <p class="text-muted mb-0">
                            Gestion de la période
                        </p>
                    </div>

                </div>


                <div class="d-grid gap-2">


                    @if($periode->statut === 'ouverte')

                        <form method="POST"
                              action="{{ route('finance.periodes.close',$periode) }}"
                              onsubmit="return confirm('Clôturer cette période ? Cette action est irréversible.');">

                            @csrf
                            @method('PATCH')

                            <button type="submit" class="btn btn-warning w-100">

                                <i class="bi bi-lock"></i>
                                Clôturer

                            </button>

                        </form>

                    @endif


                    <form method="POST"
                          action="{{ route('finance.periodes.destroy',$periode) }}"
                          onsubmit="return confirm('Supprimer cette période ? Cette action est irréversible.');">

                        @csrf
                        @method('DELETE')

                        <button type="submit" class="btn btn-danger w-100">

                            <i class="bi bi-trash"></i>
                            Supprimer

                        </button>

                    </form>


                    <a href="{{ route('finance.periodes.index') }}"
                       class="btn btn-light">

                        Retour liste

                    </a>


                </div>


            </div>
        </div>


    </div>


</div>

@endsection