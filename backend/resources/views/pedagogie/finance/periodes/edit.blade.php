@extends('panel.layouts.app')

@section('title', 'Modifier période comptable')

@section('content')

<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon">
                <i class="bi bi-calendar-event"></i>
            </div>
            <div>
                <h1 class="mb-0">Modifier période comptable</h1>
                <p class="text-muted mb-0">Mise à jour des informations</p>
            </div>
        </div>
    </div>

    <div class="panel">

        <form method="POST" action="{{ route('finance.periodes.update', $periode) }}">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label>Libellé</label>
                <input type="text"
                       name="label"
                       value="{{ old('label', $periode->label) }}"
                       class="form-control"
                       required>
            </div>

            <div class="row">

                <div class="col-md-6 mb-3">
                    <label>Date début</label>
                    <input type="date"
                           name="date_debut"
                           value="{{ old('date_debut', $periode->date_debut?->format('Y-m-d')) }}"
                           class="form-control"
                           required>
                </div>

                <div class="col-md-6 mb-3">
                    <label>Date fin</label>
                    <input type="date"
                           name="date_fin"
                           value="{{ old('date_fin', $periode->date_fin?->format('Y-m-d')) }}"
                           class="form-control"
                           required>
                </div>

            </div>

            <div class="row">

                <div class="col-md-6 mb-3">
                    <label>Type</label>

                    <select name="type" class="form-select">

                        <option value="mensuel"
                            @selected($periode->type === 'mensuel')>
                            Mensuel
                        </option>

                        <option value="trimestriel"
                            @selected($periode->type === 'trimestriel')>
                            Trimestriel
                        </option>

                        <option value="annuel"
                            @selected($periode->type === 'annuel')>
                            Annuel
                        </option>

                    </select>

                </div>


                <div class="col-md-6 mb-3">
                    <label>Statut</label>

                    <select name="statut" class="form-select">

                        <option value="ouverte"
                            @selected($periode->statut === 'ouverte')>
                            Ouverte
                        </option>

                        <option value="cloturee"
                            @selected($periode->statut === 'cloturee')>
                            Clôturée
                        </option>

                    </select>

                </div>

            </div>


            <div class="d-flex justify-content-end gap-2">

                <a href="{{ route('finance.periodes.index') }}"
                   class="btn btn-light">
                    Annuler
                </a>

                <button class="btn btn-primary">
                    Mettre à jour
                </button>

            </div>

        </form>

    </div>

</div>

@endsection