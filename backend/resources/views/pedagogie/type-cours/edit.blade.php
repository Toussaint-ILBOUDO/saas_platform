@extends('panel.layouts.app')

@section('title', 'Modifier type de cours')

@section('content')

<div class="container-fluid">

    <div class="page-heading">
        <div>
            <h1>Modifier type de cours</h1>
            <p class="text-muted">Mise à jour des informations</p>
        </div>
    </div>

    <div class="panel">

        <form method="POST" action="{{ route('type-cours.update', $typeCour) }}">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label>Libellé</label>
                <input type="text"
                       name="libelle"
                       value="{{ $typeCour->libelle }}"
                       class="form-control"
                       required>
            </div>

            <div class="mb-3">
                <label>Code</label>
                <input type="text"
                       name="code"
                       value="{{ $typeCour->code }}"
                       class="form-control">
            </div>

            <div class="mb-3">
                <label>Description</label>
                <textarea name="description"
                          class="form-control">{{ $typeCour->description }}</textarea>
            </div>

            <div class="d-flex justify-content-end gap-2">

                <a href="{{ route('type-cours.index') }}"
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