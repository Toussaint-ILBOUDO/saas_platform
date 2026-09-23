@extends('panel.layouts.app')

@section('title', 'Documents administratifs')

@section('content')

<div class="container-fluid">

    {{-- HEADER --}}
    <div class="page-heading">

        <div class="page-heading-copy">

            <div class="page-icon">
                <i class="bi bi-folder2-open"></i>
            </div>

            <div>
                <h1 class="mb-0">Documents administratifs</h1>
                <p class="text-muted mb-0">
                    Accédez à tous vos documents pédagogiques
                </p>
            </div>

        </div>

    </div>

    {{-- CARDS --}}
    <div class="row g-4">

        {{-- CAHIERS DE TEXTE --}}
        <div class="col-md-4">

            <a href="{{ route('documents.cahiers') }}"
               class="text-decoration-none">

                <div class="panel hover-shadow">

                    <div class="panel-body text-center">

                        <i class="bi bi-journal-text display-6 text-primary"></i>

                        <h5 class="mt-3">Cahiers de texte</h5>

                        <div class="text-muted">
                            {{ $stats['cahiers'] }} documents
                        </div>

                    </div>

                </div>

            </a>

        </div>

        {{-- RAPPORTS --}}
        <div class="col-md-4">

            <a href="{{ route('documents.rapports') }}"
               class="text-decoration-none">

                <div class="panel hover-shadow">

                    <div class="panel-body text-center">

                        <i class="bi bi-file-earmark-bar-graph display-6 text-success"></i>

                        <h5 class="mt-3">Rapports mensuels</h5>

                        <div class="text-muted">
                            {{ $stats['rapports'] }} documents
                        </div>

                    </div>

                </div>

            </a>

        </div>

        {{-- FACTURES --}}
        <div class="col-md-4">

            <a href="{{ route('finance.factures.index') }}"
               class="text-decoration-none">

                <div class="panel hover-shadow">

                    <div class="panel-body text-center">

                        <i class="bi bi-receipt display-6 text-warning"></i>

                        <h5 class="mt-3">Factures</h5>

                        <div class="text-muted">
                            {{ $stats['factures'] }} documents
                        </div>

                    </div>

                </div>

            </a>

        </div>

    </div>

</div>

@endsection