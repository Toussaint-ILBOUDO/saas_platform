@extends('panel.layouts.app')

@section('title', 'Créer type de cours')

@section('content')

<div class="container-fluid">

    <div class="page-heading">
        <div>
            <h1>Créer un type de cours</h1>
            <p class="text-muted">Ajout d’un nouveau type</p>
        </div>
    </div>

    <div class="panel">

        <form method="POST" action="{{ route('type-cours.store') }}">
            @csrf

            <div class="mb-3">
                <label>Libellé</label>
                <input type="text"
                       name="libelle"
                       class="form-control"
                       required>
            </div>

            <div class="mb-3">
                <label>Code</label>
                <input type="text"
                       name="code"
                       class="form-control">
            </div>

            <div class="mb-3">
                <label>Description</label>
                <textarea name="description"
                          class="form-control"></textarea>
            </div>

            <div class="d-flex justify-content-end gap-2">

                <a href="{{ route('type-cours.index') }}"
                class="btn btn-light">

                    Annuler

                </a>

                <button type="submit"
                        class="btn btn-primary">

                    Créer

                </button>

            </div>

        </form>

    </div>

</div>

@endsection