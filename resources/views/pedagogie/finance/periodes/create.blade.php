@extends('panel.layouts.app')

@section('title', 'Créer période comptable')

@section('content')

<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon">
                <i class="bi bi-calendar-plus"></i>
            </div>
            <div>
                <h1 class="mb-0">Créer une période comptable</h1>
                <p class="text-muted mb-0">Ajout d'une nouvelle période</p>
            </div>
        </div>
    </div>

    <div class="panel">

        <form method="POST" action="{{ route('finance.periodes.store') }}">
            @csrf

            <div class="mb-3">
                <label>Libellé</label>
                <input type="text" name="label" class="form-control" value="{{ old('label') }}" required>
            </div>

            <div class="row">

                <div class="col-md-6 mb-3">
                    <label>Date début</label>
                    <input type="date" name="date_debut" class="form-control" value="{{ old('date_debut') }}" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label>Date fin</label>
                    <input type="date" name="date_fin" class="form-control" value="{{ old('date_fin') }}" required>
                </div>

            </div>

            <div class="row">

                <div class="col-md-6 mb-3">
                    <label>Type</label>
                    <select name="type" class="form-select">
                        <option value="mensuel">Mensuel</option>
                        <option value="trimestriel">Trimestriel</option>
                        <option value="annuel">Annuel</option>
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label>Statut</label>
                    <select name="statut" class="form-select">
                        <option value="ouverte">Ouverte</option>
                        <option value="cloturee">Clôturée</option>
                    </select>
                </div>

            </div>

            <div class="d-flex justify-content-end gap-2">

                <a href="{{ route('finance.periodes.index') }}" class="btn btn-light">
                    Annuler
                </a>

                <button class="btn btn-primary">
                    Enregistrer
                </button>

            </div>

        </form>

    </div>

</div>

@endsection