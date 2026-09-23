{{-- ======================================================
    Témoignages — Cartes (fragment réutilisable : page + "Voir plus")
    Reçoit : $temoignages (collection)
====================================================== --}}

<div class="row g-4">

    @foreach($temoignages as $temoignage)

        <article class="col-md-6 col-lg-4">
            <div class="temo-card panel h-100 d-flex flex-column">

                <div class="temo-card-head">
                    <span class="temo-avatar">
                        <i class="bi bi-quote"></i>
                    </span>
                    <div class="temo-meta">
                        <span class="temo-author">{{ $temoignage->auteur_display }}</span>
                        @if(! $temoignage->anonyme)
                            <span class="temo-role">{{ $temoignage->role_label }}</span>
                        @endif
                    </div>
                    <span class="temo-score" title="Score : {{ $temoignage->score }} points">
                        <i class="bi bi-stars me-1"></i>{{ $temoignage->score }}
                    </span>
                </div>

                <blockquote class="temo-text flex-grow-1">
                    <i class="bi bi-quote quote-icon-left"></i>
                    {{ Str::limit($temoignage->contenu, 180) }}
                    <i class="bi bi-quote quote-icon-right"></i>
                </blockquote>

                <div class="temo-card-foot">
                    <time datetime="{{ $temoignage->published_at?->toIso8601String() }}">
                        <i class="bi bi-calendar3 me-1"></i>
                        {{ $temoignage->published_at?->format('d/m/Y') ?? $temoignage->created_at->format('d/m/Y') }}
                    </time>
                    <a href="{{ route('temoignages.show', $temoignage->slug) }}" class="temo-more">
                        Lire <i class="bi bi-arrow-right"></i>
                    </a>
                </div>

            </div>
        </article>

    @endforeach

</div>
