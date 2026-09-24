@php
    $iconMap = [
        'Cours' => ['icon' => 'bi-book', 'color' => '#2563eb'],
        'Exercice' => ['icon' => 'bi-pencil-square', 'color' => '#7c3aed'],
        'Évaluation' => ['icon' => 'bi-clipboard-check', 'color' => '#dc2626'],
        'Support de cours' => ['icon' => 'bi-easel', 'color' => '#0891b2'],
        'Méthodologie' => ['icon' => 'bi-lightbulb', 'color' => '#d97706'],
        'Synthèse' => ['icon' => 'bi-journal-richtext', 'color' => '#059669'],
        'Fiche de révision' => ['icon' => 'bi-card-text', 'color' => '#db2777'],
        'Travaux pratiques' => ['icon' => 'bi-tools', 'color' => '#4f46e5'],
    ];

    $typeName = $document->typeDocument?->nom ?? 'Autre';
    $iconData = $iconMap[$typeName] ?? ['icon' => 'bi-file-earmark', 'color' => '#6b7280'];
@endphp

<div class="col-md-6 col-lg-4">
    <div class="doc-card">
        <div class="d-flex align-items-start justify-content-between">
            <div class="doc-card-icon" style="background: {{ $iconData['color'] }}">
                <i class="bi {{ $iconData['icon'] }}"></i>
            </div>
            <div class="d-flex align-items-center gap-1">
                @auth
                    <button type="button"
                            class="btn btn-sm btn-link p-0 favori-toggle"
                            data-doc-id="{{ $document->id }}"
                            data-url="{{ route('bibliotheque.toggle-favori-ajax', $document->id) }}"
                            title="{{ $document->is_favori ? 'Retirer des favoris' : 'Ajouter aux favoris' }}">
                        <i class="bi {{ $document->is_favori ? 'bi-heart-fill text-danger' : 'bi-heart text-muted' }}"></i>
                    </button>
                @endauth
                @if($document->is_public)
                    <span class="badge bg-success-subtle text-success" style="font-size: 0.7rem;">Public</span>
                @else
                    <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.7rem;">Privé</span>
                @endif
            </div>
        </div>

        <div class="doc-card-title mt-2">
            <a href="{{ route('bibliothequepub.show', $document->slug) }}">
                {{ Str::limit($document->titre, 60) }}
            </a>
        </div>

        <div class="doc-card-meta">
            @if($document->matiere)
                <span class="me-2"><i class="bi bi-book me-1"></i>{{ $document->matiere->nom }}</span>
            @endif
            @if($document->classe)
                <span><i class="bi bi-mortarboard me-1"></i>{{ $document->classe->nom }}</span>
            @endif
        </div>

        @if($document->description)
            <p class="text-muted" style="font-size: 0.82rem; margin-bottom: 8px;">
                {{ Str::limit($document->description, 100) }}
            </p>
        @endif

        <div class="doc-card-meta">
            <i class="bi bi-person me-1"></i>
            {{ $document->auteur?->prenom }} {{ $document->auteur?->nom }}
            <span class="ms-2">{{ $document->created_at->diffForHumans() }}</span>
        </div>

        <div class="doc-card-stats">
            <span><i class="bi bi-eye"></i>{{ number_format($document->nb_vues) }}</span>
            <span><i class="bi bi-download"></i>{{ number_format($document->nb_telechargements) }}</span>
            @if($document->nombre_favoris > 0)
                <span><i class="bi bi-heart-fill text-danger"></i>{{ number_format($document->nombre_favoris) }}</span>
            @endif
            @if($document->nb_notes > 0)
                <span class="doc-stars">
                    @for($i = 1; $i <= 5; $i++)
                        @if($i <= round($document->note_moyenne))
                            <i class="bi bi-star-fill"></i>
                        @else
                            <i class="bi bi-star"></i>
                        @endif
                    @endfor
                    <span class="ms-1">({{ number_format($document->note_moyenne, 1) }})</span>
                </span>
            @endif
        </div>

        @if($document->getFirstMedia('document'))
            <div class="doc-card-actions mt-2 d-flex gap-2">
                <a href="{{ route('bibliothequepub.view', $document->id) }}"
                   target="_blank"
                   class="btn btn-sm btn-outline-primary flex-fill">
                    <i class="bi bi-eye me-1"></i>Aperçu
                </a>
                <a href="{{ route('bibliothequepub.download', $document->id) }}"
                   class="btn btn-sm btn-outline-success flex-fill">
                    <i class="bi bi-download me-1"></i>Télécharger
                </a>
            </div>
        @endif
    </div>
</div>
