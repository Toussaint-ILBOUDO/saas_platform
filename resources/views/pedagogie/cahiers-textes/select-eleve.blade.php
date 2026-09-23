@extends('panel.layouts.app')

@section('title', 'Sélectionner un élève')

@section('content')

<div class="container-fluid">

    <div class="page-heading">

        <div class="page-heading-copy">

            <div class="page-icon">
                <i class="bi bi-people"></i>
            </div>

            <div>
                <h1 class="mb-0">Sélectionner un élève</h1>

                <p class="text-muted mb-0">
                    Choisissez un élève pour créer une nouvelle séance
                </p>
            </div>

        </div>

        <div class="heading-actions">

            <a href="{{ route('cahiers-textes.index') }}"
               class="btn btn-light">

                <i class="bi bi-arrow-left"></i>
                Retour

            </a>

        </div>

    </div>

    <div class="row g-4">

        <div class="col-lg-12">

            <div class="panel">

                <div class="panel-header">

                    <div>

                        <h5 class="mb-0">
                            Liste des élèves
                        </h5>

                        <p class="text-muted mb-0">
                            Cliquez sur un élève pour continuer
                        </p>

                    </div>

                </div>

                <div class="panel-body">

                    <div class="row g-3">

                        @forelse($eleves as $eleve)

                            <div class="col-md-6 col-lg-4">

                                <a href="{{ route('cahiers-textes.create.by-eleve', $eleve->id) }}"
                                   class="eleve-card">

                                    <div class="eleve-card-body">

                                        <div class="eleve-avatar">

                                            <i class="bi bi-person-circle"></i>

                                        </div>

                                        <div class="eleve-info">

                                            <div class="eleve-name">

                                                {{ $eleve->user->prenom }}
                                                {{ $eleve->user->nom }}

                                            </div>

                                            <div class="eleve-meta text-muted">

                                                Élève

                                            </div>

                                        </div>

                                        <div class="eleve-action">

                                            <i class="bi bi-chevron-right"></i>

                                        </div>

                                    </div>

                                </a>

                            </div>

                        @empty

                            <div class="col-12">

                                <div class="alert alert-light text-center">

                                    Aucun élève disponible.

                                </div>

                            </div>

                        @endforelse

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection

@push('styles')

<style>

    .eleve-card {
        display: block;
        text-decoration: none;
        border: 1px solid #e9ecef;
        border-radius: 12px;
        transition: all 0.2s ease;
        background: #fff;
    }

    .eleve-card:hover {
        transform: translateY(-2px);
        border-color: #0d6efd;
        box-shadow: 0 8px 20px rgba(0,0,0,0.06);
    }

    .eleve-card-body {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px;
    }

    .eleve-avatar {
        width: 42px;
        height: 42px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f1f3f5;
        border-radius: 50%;
        font-size: 20px;
        color: #495057;
    }

    .eleve-info {
        flex: 1;
    }

    .eleve-name {
        font-weight: 600;
        color: #212529;
    }

    .eleve-meta {
        font-size: 13px;
    }

    .eleve-action {
        color: #adb5bd;
        font-size: 18px;
    }

    .eleve-card:hover .eleve-action {
        color: #0d6efd;
    }

</style>

@endpush