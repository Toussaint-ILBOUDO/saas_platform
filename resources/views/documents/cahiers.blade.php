@extends('panel.layouts.app')

@section('title', 'Cahiers de texte')

@section('content')

<div class="container-fluid">

    <div class="page-heading">

        <div class="page-heading-copy">

            <div class="page-icon">
                <i class="bi bi-journal-text"></i>
            </div>

            <div>
                <h1 class="mb-0">Cahiers de texte</h1>
                <p class="text-muted mb-0">
                    Documents générés automatiquement
                </p>
            </div>

        </div>

    </div>

    <div class="panel">

        <div class="panel-body">

            @forelse($documents as $doc)

                <div class="d-flex justify-content-between align-items-center border-bottom py-2">

                    <div>

                        <strong>{{ $doc->file_name }}</strong>

                        <div class="text-muted small">
                            {{ $doc->created_at->format('d/m/Y H:i') }}
                        </div>

                    </div>

                    <div>

                        <a href="{{ $doc->getUrl() }}"
                           class="btn btn-sm btn-primary">

                            Télécharger

                        </a>

                    </div>

                </div>

            @empty

                <p class="text-muted mb-0">
                    Aucun document disponible
                </p>

            @endforelse

        </div>

    </div>

</div>

@endsection