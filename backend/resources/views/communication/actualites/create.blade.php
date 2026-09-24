@extends('panel.layouts.app')

@section('title', 'Nouvelle actualité')

@section('content')

<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon"><i class="bi bi-megaphone"></i></div>
            <div>
                <h1 class="mb-0">Nouvelle actualité</h1>
                <p class="text-muted mb-0">Rédigez le contenu, puis publiez-le depuis la liste</p>
            </div>
        </div>
        <div class="heading-actions">
            <a href="{{ route('admin.actualites.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Retour
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-9">
            <form action="{{ route('admin.actualites.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">

                        <div class="mb-3">
                            <label for="titre" class="form-label fw-semibold">Titre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('titre') is-invalid @enderror"
                                   id="titre" name="titre" value="{{ old('titre') }}"
                                   placeholder="Ex: Ouverture des inscriptions 2026-2027" required autofocus>
                            @error('titre')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="slug" class="form-label fw-semibold">Slug</label>
                            <input type="text" class="form-control @error('slug') is-invalid @enderror"
                                   id="slug" name="slug" value="{{ old('slug') }}"
                                   placeholder="Laissez vide pour générer automatiquement">
                            @error('slug')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="resume" class="form-label fw-semibold">Résumé</label>
                            <textarea class="form-control @error('resume') is-invalid @enderror"
                                      id="resume" name="resume" rows="2"
                                      placeholder="Court résumé affiché sur la page d'accueil et dans les emails">{{ old('resume') }}</textarea>
                            @error('resume')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="contenu" class="form-label fw-semibold">Contenu <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('contenu') is-invalid @enderror"
                                      id="contenu" name="contenu" rows="10"
                                      placeholder="Le contenu complet de l'actualité...">{{ old('contenu') }}</textarea>
                            @error('contenu')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr>

                        <h6 class="fw-bold mb-3">Médias</h6>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="image_principale" class="form-label fw-semibold">Image principale</label>
                                <input type="file" class="form-control @error('image_principale') is-invalid @enderror"
                                       id="image_principale" name="image_principale"
                                       accept=".jpg,.jpeg,.png,.webp,.gif">
                                <div class="form-text">JPEG, PNG, WEBP ou GIF — 10 Mo max.</div>
                                @error('image_principale')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="document" class="form-label fw-semibold">Document (PDF)</label>
                                <input type="file" class="form-control @error('document') is-invalid @enderror"
                                       id="document" name="document"
                                       accept=".pdf,.doc,.docx,.ppt,.pptx">
                                <div class="form-text">PDF, Word ou PowerPoint — 10 Mo max.</div>
                                @error('document')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="galerie" class="form-label fw-semibold">Galerie d'images</label>
                            <input type="file" class="form-control @error('galerie') is-invalid @enderror"
                                   id="galerie" name="galerie[]" multiple
                                   accept=".jpg,.jpeg,.png,.webp,.gif">
                            <div class="form-text">Plusieurs images possibles — 10 Mo max par fichier.</div>
                            @error('galerie.*')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="video_url" class="form-label fw-semibold">Vidéo (URL)</label>
                                <input type="url" class="form-control @error('video_url') is-invalid @enderror"
                                       id="video_url" name="video_url" value="{{ old('video_url') }}"
                                       placeholder="https://www.youtube.com/watch?v=...">
                                <div class="form-text">Lien YouTube, Vimeo ou fichier MP4 direct.</div>
                                @error('video_url')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="lien_externe" class="form-label fw-semibold">Lien externe</label>
                                <input type="url" class="form-control @error('lien_externe') is-invalid @enderror"
                                       id="lien_externe" name="lien_externe" value="{{ old('lien_externe') }}"
                                       placeholder="https://exemple.com">
                                <div class="form-text">Lien vers une page ou ressource externe.</div>
                                @error('lien_externe')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                                    {{ old('is_active', 1) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">Activer (affichage sur le site public)</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Enregistrer
                    </button>
                    <a href="{{ route('admin.actualites.index') }}" class="btn btn-outline-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>

</div>

@endsection
