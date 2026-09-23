@extends('panel.layouts.app')

@section('title', 'Dashboard Parent')

@section('content')

<div class="container-fluid px-3 px-lg-4 py-4">

    <h1>Dashboard Parent</h1>

    <p class="text-muted mb-4">
        Suivez la scolarité de vos enfants en un coup d'œil.
    </p>

    <div class="row g-3">

        <div class="col-md-4">
            <div class="card p-3">
                <h5>Mes enfants</h5>
                <p class="text-muted mb-2">Les fiches et la progression</p>
                <a href="{{ route('mes-enfants') }}">Voir</a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card p-3">
                <h5>Planning</h5>
                <p class="text-muted mb-2">Les séances de vos enfants</p>
                <a href="{{ route('planning.index') }}">Consulter</a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card p-3">
                <h5>Factures</h5>
                <p class="text-muted mb-2">Vos factures et paiements</p>
                <a href="{{ route('mes-factures.index') }}">Voir</a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card p-3">
                <h5>Actualités</h5>
                <p class="text-muted mb-2">Les dernières nouvelles de l'école</p>
                <a href="{{ route('actualites.internes') }}">Suivre</a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card p-3">
                <h5>Bibliothèque</h5>
                <p class="text-muted mb-2">Les documents de l'école</p>
                <a href="{{ route('bibliothequepub.index') }}">Explorer</a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card p-3">
                <h5>Notifications</h5>
                <p class="text-muted mb-2">Vos alertes et messages</p>
                <a href="{{ route('notifications.index') }}">Voir</a>
            </div>
        </div>

    </div>

</div>

@endsection