@extends('panel.layouts.app')

@section('title', 'Créer une période')

@section('content')

<div class="container-fluid">

    <div class="page-heading">
        <div>
            <h1>Créer une période</h1>
            <p class="text-muted">Ajout d'une nouvelle période de document</p>
        </div>
    </div>

    <div class="panel">

        <form method="POST" action="{{ route('admin.bibliotheque.periodes.store') }}">
            @csrf

            <div class="mb-3">
                <label>Nom</label>
                <input type="text"
                       name="nom"
                       class="form-control"
                       value="{{ old('nom') }}"
                       placeholder="Ex: Premier trimestre"
                       required>
            </div>

            <div class="mb-3">
                <label>Sigle</label>
                <input type="text"
                       name="sigle"
                       class="form-control"
                       value="{{ old('sigle') }}"
                       placeholder="Ex: T1"
                       maxlength="25">
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('admin.bibliotheque.periodes.index') }}"
                   class="btn btn-light">
                    Annuler
                </a>
                <button type="submit"
                        class="btn btn-primary">
                    Enregistrer
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
