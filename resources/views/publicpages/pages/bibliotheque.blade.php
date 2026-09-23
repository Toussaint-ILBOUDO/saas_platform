@extends('publicpages.layouts.public')

@section('title', 'Bibliothèque numérique | K\'Educ')
@section('meta_description', 'Découvrez notre bibliothèque numérique de ressources pédagogiques. Cours, exercices, évaluations et plus encore, partagés par nos enseignants et élèves.')
@section('body_class', 'index-page')

@section('content')

<section id="biblio-hero" class="biblio-hero section" style="background: linear-gradient(135deg, var(--heading-color) 0%, #1a365d 100%); padding: 80px 0 60px;">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8" data-aos="fade-up">
                <h1 style="color: #fff; font-weight: 700; margin-bottom: 16px;">
                    <i class="bi bi-book me-2"></i>Bibliothèque Numérique
                </h1>
                <p class="lead" style="color: rgba(255,255,255,0.85); margin-bottom: 32px;">
                    Accédez à des milliers de ressources pédagogiques partagées par notre communauté d'enseignants et d'élèves.
                </p>

                <form action="{{ route('bibliothequepub.index') }}" method="GET" class="biblio-search-bar">
                    <div class="input-group input-group-lg shadow-lg" style="border-radius: 12px; overflow: hidden;">
                        <span class="input-group-text bg-white border-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" name="search" id="biblio-search" class="form-control border-0 py-3"
                               placeholder="Rechercher un cours, exercice, évaluation..."
                               aria-label="Rechercher un document"
                               value="{{ request('search') }}">
                        <button type="submit" class="btn btn-success px-4 px-lg-5">
                            Rechercher
                        </button>
                    </div>
                </form>

                <div class="d-flex gap-3 mt-4 flex-wrap">
                    @if($typesDocument->count())
                        @foreach($typesDocument->take(4) as $type)
                            <a href="{{ route('bibliothequepub.index', ['type_document_id' => $type->id]) }}"
                               class="badge bg-light text-dark px-3 py-2 text-decoration-none" style="border-radius: 20px; font-size: 0.85rem;">
                                {{ $type->nom }}
                            </a>
                        @endforeach
                    @endif
                </div>
            </div>
            <div class="col-lg-4 text-center mt-4 mt-lg-0" data-aos="fade-left" data-aos-delay="200">
                <div class="biblio-hero-stats">
                    <div class="biblio-stat-card">
                        <div class="biblio-stat-icon"><i class="bi bi-file-earmark-text"></i></div>
                        <div class="biblio-stat-number">{{ number_format($stats['total_documents']) }}</div>
                        <div class="biblio-stat-label">Documents</div>
                    </div>
                    <div class="biblio-stat-card">
                        <div class="biblio-stat-icon"><i class="bi bi-eye"></i></div>
                        <div class="biblio-stat-number">{{ number_format($stats['total_vues']) }}</div>
                        <div class="biblio-stat-label">Vues</div>
                    </div>
                    <div class="biblio-stat-card">
                        <div class="biblio-stat-icon"><i class="bi bi-download"></i></div>
                        <div class="biblio-stat-number">{{ number_format($stats['total_telechargements']) }}</div>
                        <div class="biblio-stat-label">Téléchargements</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="biblio-filtres" class="section" style="padding: 30px 0; background: var(--background-color);">
    <div class="container">
        <form action="{{ route('bibliothequepub.index') }}" method="GET" id="filtreForm">
            <div class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label for="filtre-type" class="form-label small fw-semibold">Type</label>
                    <select name="type_document_id" id="filtre-type" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Tous les types</option>
                        @foreach($typesDocument as $type)
                            <option value="{{ $type->id }}" {{ (request('type_document_id') == $type->id) ? 'selected' : '' }}>
                                {{ $type->nom }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="filtre-classe" class="form-label small fw-semibold">Classe</label>
                    <select name="classe_id" id="filtre-classe" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Toutes les classes</option>
                        @foreach($classes as $classe)
                            <option value="{{ $classe->id }}" {{ (request('classe_id') == $classe->id) ? 'selected' : '' }}>
                                {{ $classe->nom }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="filtre-matiere" class="form-label small fw-semibold">Matière</label>
                    <select name="matiere_id" id="filtre-matiere" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Toutes les matières</option>
                        @foreach($matieres as $matiere)
                            <option value="{{ $matiere->id }}" {{ (request('matiere_id') == $matiere->id) ? 'selected' : '' }}>
                                {{ $matiere->nom }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="filtre-periode" class="form-label small fw-semibold">Période</label>
                    <select name="periode_id" id="filtre-periode" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Toutes les périodes</option>
                        @foreach($periodes as $periode)
                            <option value="{{ $periode->id }}" {{ (request('periode_id') == $periode->id) ? 'selected' : '' }}>
                                {{ $periode->nom }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="filtre-tri" class="form-label small fw-semibold">Tri</label>
                    <select name="sort" id="filtre-tri" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="recents" {{ (request('sort') == 'recents') ? 'selected' : '' }}>Plus récents</option>
                        <option value="populaires" {{ (request('sort') == 'populaires') ? 'selected' : '' }}>Plus populaires</option>
                        <option value="telecharges" {{ (request('sort') == 'telecharges') ? 'selected' : '' }}>Plus téléchargés</option>
                        <option value="notes" {{ (request('sort') == 'notes') ? 'selected' : '' }}>Mieux notés</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="hidden" name="search" value="{{ request('search') }}">
                    <button type="submit" class="btn btn-outline-success btn-sm w-100">
                        <i class="bi bi-funnel me-1"></i>Filtrer
                    </button>
                </div>
                @if(request()->hasAny(['search', 'type_document_id', 'classe_id', 'matiere_id', 'sort', 'tag']))
                    <div class="col-md-2">
                        <a href="{{ route('bibliothequepub.index') }}" class="btn btn-outline-secondary btn-sm w-100">
                            <i class="bi bi-x-circle me-1"></i>Réinitialiser
                        </a>
                    </div>
                @endif
            </div>
        </form>
    </div>
</section>

@if(!request()->hasAny(['search', 'type_document_id', 'classe_id', 'matiere_id', 'sort', 'tag']))

@if($documentsRecents->count())
<section id="biblio-recents" class="section" style="padding: 40px 0;">
    <div class="container">
        <div class="section-title" data-aos="fade-up">
            <h2><i class="bi bi-clock-history me-2"></i>Documents récents</h2>
            <p>Les dernières ressources ajoutées par notre communauté</p>
        </div>
        <div class="row g-4" data-aos="fade-up" data-aos-delay="100">
            @foreach($documentsRecents as $doc)
                @include('publicpages.partials.document-card', ['document' => $doc])
            @endforeach
        </div>
    </div>
</section>
@endif

@if($documentsPopulaires->count())
<section id="biblio-populaires" class="section light-background" style="padding: 40px 0;">
    <div class="container">
        <div class="section-title" data-aos="fade-up">
            <h2><i class="bi bi-fire me-2"></i>Documents populaires</h2>
            <p>Les ressources les plus consultées</p>
        </div>
        <div class="row g-4" data-aos="fade-up" data-aos-delay="100">
            @foreach($documentsPopulaires as $doc)
                @include('publicpages.partials.document-card', ['document' => $doc])
            @endforeach
        </div>
    </div>
</section>
@endif

@if($documentsMieuxNotes->count())
<section id="biblio-notes" class="section" style="padding: 40px 0;">
    <div class="container">
        <div class="section-title" data-aos="fade-up">
            <h2><i class="bi bi-star me-2"></i>Mieux notés</h2>
            <p>Les ressources les mieux évaluées par les utilisateurs</p>
        </div>
        <div class="row g-4" data-aos="fade-up" data-aos-delay="100">
            @foreach($documentsMieuxNotes as $doc)
                @include('publicpages.partials.document-card', ['document' => $doc])
            @endforeach
        </div>
    </div>
</section>
@endif

@if($tags->count())
<section id="biblio-tags" class="section light-background" style="padding: 40px 0;">
    <div class="container">
        <div class="section-title" data-aos="fade-up">
            <h2><i class="bi bi-tags me-2"></i>Tags populaires</h2>
            <p>Parcourez les documents par thème</p>
        </div>
        <div class="text-center" data-aos="fade-up" data-aos-delay="100">
            @foreach($tags as $tag)
                <a href="{{ route('bibliothequepub.index', ['tag' => $tag->slug]) }}"
                   class="badge me-2 mb-2 text-decoration-none px-3 py-2"
                   style="background: var(--accent-color); color: #fff; font-size: 0.9rem; border-radius: 20px;">
                    {{ $tag->nom }}
                    <span class="ms-1 opacity-75">({{ $tag->usage_count }})</span>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

@if(!$documentsRecents->count() && !$documentsPopulaires->count() && !$documentsMieuxNotes->count() && !$tags->count())
    <section class="section" style="padding: 40px 0;">
        <div class="container">
            <div class="text-center py-5">
                <i class="bi bi-inbox display-1 text-muted"></i>
                <p class="text-muted mt-3">La bibliothèque se remplit bientôt avec de nouvelles ressources.</p>
            </div>
        </div>
    </section>
@endif

@else

<section id="biblio-results" class="section" style="padding: 40px 0;">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">
                @if(request('search'))
                    Résultats pour « {{ request('search') }} »
                @else
                    Documents
                @endif
                <span class="text-muted ms-2" style="font-size: 0.85rem;">
                    ({{ $documents->total() }} résultat{{ $documents->total() > 1 ? 's' : '' }})
                </span>
            </h4>
        </div>

        @if($documents->count())
            <div class="row g-4">
                @foreach($documents as $doc)
                    @include('publicpages.partials.document-card', ['document' => $doc])
                @endforeach
            </div>

            <div class="d-flex justify-content-center mt-5">
                {{ $documents->withQueryString()->links() }}
            </div>
        @else
            <div class="text-center py-5">
                <i class="bi bi-inbox display-1 text-muted"></i>
                <p class="text-muted mt-3">Aucun document trouvé pour cette recherche.</p>
                <a href="{{ route('bibliothequepub.index') }}" class="btn btn-success mt-2">
                    <i class="bi bi-arrow-left me-1"></i>Retour à la bibliothèque
                </a>
            </div>
        @endif
    </div>
</section>

@endif

@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assetBiblio/css/bibliotheque.css') }}">
@endpush

@push('scripts')
<script>window.BIBLIO_NOTE_URL = '{{ route("bibliothequepub.note", "__ID__") }}';</script>
<script src="{{ asset('assetBiblio/js/bibliotheque.js') }}"></script>
<script>
document.querySelectorAll('.favori-toggle').forEach(btn => {
    btn.addEventListener('click', async function(e) {
        e.preventDefault();
        e.stopPropagation();
        const url = this.dataset.url;
        const icon = this.querySelector('i');
        const title = this.getAttribute('title');

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const data = await response.json();
            if (data.is_favori) {
                icon.classList.remove('bi-heart', 'text-muted');
                icon.classList.add('bi-heart-fill', 'text-danger');
                this.setAttribute('title', 'Retirer des favoris');
            } else {
                icon.classList.remove('bi-heart-fill', 'text-danger');
                icon.classList.add('bi-heart', 'text-muted');
                this.setAttribute('title', 'Ajouter aux favoris');
            }
        } catch (err) {
            console.error(err);
        }
    });
});
</script>
@endpush
