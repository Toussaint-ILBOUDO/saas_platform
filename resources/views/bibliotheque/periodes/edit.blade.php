@extends('panel.layouts.app')

@section('title', 'Modifier — ' . $periode->nom)

@section('content')

<div class="container-fluid">

    <div class="page-heading">
        <div>
            <h1>Modifier la période</h1>
            <p class="text-muted">Mise à jour des informations</p>
        </div>
    </div>

    <div class="panel">

        <form method="POST" action="{{ route('admin.bibliotheque.periodes.update', $periode) }}">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label>Nom</label>
                <input type="text"
                       name="nom"
                       value="{{ old('nom', $periode->nom) }}"
                       class="form-control"
                       required>
            </div>

            <div class="mb-3">
                <label>Sigle</label>
                <input type="text"
                       name="sigle"
                       value="{{ old('sigle', $periode->sigle) }}"
                       class="form-control"
                       maxlength="25">
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('admin.bibliotheque.periodes.index') }}"
                   class="btn btn-light">
                    Annuler
                </a>
                <button type="submit"
                        class="btn btn-primary">
                    Mettre à jour
                </button>
            </div>

        </form>

    </div>

</div>

@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assetBiblio/css/bibliotheque.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('assetBiblio/js/bibliotheque.js') }}"></script>
@endpush
