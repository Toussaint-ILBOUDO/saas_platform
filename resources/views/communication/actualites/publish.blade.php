@extends('panel.layouts.app')

@section('title', 'Publier l\'actualité')

@section('content')

<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon"><i class="bi bi-send"></i></div>
            <div>
                <h1 class="mb-0">Publier l'actualité</h1>
                <p class="text-muted mb-0">Choix des destinataires et du canal de diffusion</p>
            </div>
        </div>
        <div class="heading-actions">
            <a href="{{ route('admin.actualites.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Retour
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">

                    <div class="mb-4">
                        <h5 class="fw-bold mb-1">{{ $actualite->titre }}</h5>
                        <p class="text-muted mb-0">
                            /{{ $actualite->slug }}
                            @if($actualite->resume)
                                <span class="mx-2">·</span>{{ Str::limit($actualite->resume, 80) }}
                            @endif
                        </p>
                    </div>

                    <form action="{{ route('admin.actualites.publier', $actualite) }}" method="POST">
                        @csrf

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Destinataires</label>
                            <div class="row g-2">
                                @foreach(\App\Modules\Communication\Enums\ActualiteDestinataire::LABELS as $valeur => $label)
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="destinataires[]"
                                                   id="dest_{{ $valeur }}" value="{{ $valeur }}">
                                            <label class="form-check-label" for="dest_{{ $valeur }}">
                                                <i class="bi {{ \App\Modules\Communication\Enums\ActualiteDestinataire::ICONS[$valeur] }} me-1"></i>{{ $label }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="form-text">
                                Cochez un ou plusieurs profils, ou laissez vide pour publier sans notifier.
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="canal" class="form-label fw-semibold">Canal de diffusion</label>
                            <select name="canal" id="canal" class="form-select">
                                <option value="interne">Notification interne</option>
                                <option value="email">Email</option>
                                <option value="interne_email">Notification interne + Email</option>
                            </select>
                        </div>

                        <div class="alert alert-info d-flex align-items-center gap-2">
                            <i class="bi bi-info-circle"></i>
                            <div>
                                La publication rend l'actualité visible sur
                                <a href="{{ route('actualites.index') }}" target="_blank">/actualites</a>
                                du site public. Chaque destinataire recevra la notification selon le canal choisi.
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-send me-1"></i>Publier et notifier
                            </button>
                            <a href="{{ route('admin.actualites.index') }}" class="btn btn-outline-secondary">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection
