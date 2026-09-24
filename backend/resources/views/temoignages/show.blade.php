@extends('panel.layouts.app')

@section('title', 'Témoignage — Administration')

@section('content')

<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon"><i class="bi bi-chat-quote"></i></div>
            <div>
                <h1 class="mb-0">Détail du témoignage</h1>
                <p class="text-muted mb-0">
                    {{ $temoignage->auteur?->prenom }} {{ $temoignage->auteur?->nom }}
                    @if($temoignage->anonyme)
                        · <span class="badge bg-primary-subtle text-primary">Anonyme</span>
                    @endif
                </p>
            </div>
        </div>
        <div class="heading-actions">
            @if($temoignage->est_publie)
                <form action="{{ route('admin.temoignages.masquer', $temoignage) }}" method="POST" class="d-inline"
                      onsubmit="return confirm('Masquer ce témoignage et avertir son auteur ?');">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-sm btn-warning">
                        <i class="bi bi-eye-slash me-1"></i>Masquer
                    </button>
                </form>
            @else
                <form action="{{ route('admin.temoignages.restaurer', $temoignage) }}" method="POST" class="d-inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-sm btn-success">
                        <i class="bi bi-eye me-1"></i>Restaurer
                    </button>
                </form>
            @endif
            <a href="{{ route('admin.temoignages.index') }}" class="btn btn-sm btn-light">
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

    <div class="row g-3">

        <div class="col-lg-8">

            <div class="panel">
                <div class="panel-body">
                    <blockquote class="border-start border-4 border-warning ps-3" style="font-size: 1.05rem; line-height: 1.8;">
                        {{ $temoignage->contenu }}
                    </blockquote>

                    <hr>

                    <div class="row small text-muted">
                        <div class="col-md-4 mb-2">
                            <i class="bi bi-person me-1"></i>
                            {{ $temoignage->auteur?->email ?? '—' }}
                        </div>
                        <div class="col-md-4 mb-2">
                            <i class="bi bi-stars me-1"></i>Score : <strong>{{ $temoignage->score }}</strong>
                        </div>
                        <div class="col-md-4 mb-2">
                            <i class="bi bi-calendar3 me-1"></i>
                            {{ $temoignage->published_at?->format('d/m/Y à H\hi') ?? '—' }}
                        </div>
                        <div class="col-md-4 mb-2">
                            <i class="bi bi-tag me-1"></i>Rôle : {{ $temoignage->role_label }}
                        </div>
                        <div class="col-md-4 mb-2">
                            <i class="bi bi-emoji-smile me-1"></i>{{ $temoignage->nb_reactions }} réactions
                        </div>
                        <div class="col-md-4 mb-2">
                            <i class="bi bi-chat-dots me-1"></i>{{ $temoignage->nb_commentaires }} commentaires
                        </div>
                    </div>

                    <div class="mt-3">
                        <h6 class="fw-bold">Réactions</h6>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach(\App\Modules\Temoignages\Enums\TemoignageReactionType::VALIDES as $reaction)
                                <span class="badge bg-light text-dark border">
                                    {{ \App\Modules\Temoignages\Enums\TemoignageReactionType::EMOJIS[$reaction] }}
                                    {{ $reactionCounts->get($reaction, 0) }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <div>
                        <h5 class="mb-0">Commentaires ({{ $temoignage->commentaires->count() }})</h5>
                    </div>
                </div>
                <div class="panel-body">
                    @forelse($temoignage->commentaires as $commentaire)
                        <div class="border-bottom pb-2 mb-2">
                            <strong class="small">{{ $commentaire->user?->prenom }} {{ $commentaire->user?->nom }}</strong>
                            <span class="text-muted small">· {{ $commentaire->created_at->format('d/m/Y H:i') }}</span>
                            <p class="mb-0 mt-1">{{ $commentaire->contenu }}</p>
                        </div>
                    @empty
                        <p class="text-muted mb-0">Aucun commentaire.</p>
                    @endforelse
                </div>
            </div>

        </div>

        <div class="col-lg-4">

            <div class="panel">
                <div class="panel-header">
                    <div>
                        <h5 class="mb-0">Signalements ({{ $temoignage->signalements->count() }})</h5>
                    </div>
                </div>
                <div class="panel-body">
                    @forelse($temoignage->signalements as $signalement)
                        <div class="border-bottom pb-2 mb-2">
                            <strong class="small">{{ $signalement->motif }}</strong>
                            @if($signalement->statut === 'en_attente')
                                <span class="badge bg-danger ms-1">En attente</span>
                            @else
                                <span class="badge bg-light text-muted ms-1">{{ $signalement->statut }}</span>
                            @endif
                            <div class="text-muted small">
                                {{ $signalement->user?->prenom }} {{ $signalement->user?->nom }}
                            </div>
                            @if($signalement->description)
                                <p class="mb-0 mt-1 small">{{ $signalement->description }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="text-muted mb-0">Aucun signalement.</p>
                    @endforelse
                </div>
            </div>

        </div>

    </div>

</div>

@endsection
