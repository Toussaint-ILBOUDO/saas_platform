@extends('panel.layouts.app')

@section('title', 'Nouveau document — Bibliothèque')

@section('content')

<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon"><i class="bi bi-plus-circle"></i></div>
            <div>
                <h1 class="mb-0">Nouveau document</h1>
                <p class="text-muted mb-0">Ajoutez une ressource pédagogique</p>
            </div>
        </div>
    </div>

    <form action="{{ route('bibliotheque.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="panel p-4 mb-4">
                    <h5 class="mb-3">Informations</h5>

                    <div class="mb-3">
                        <label class="form-label">Titre <span class="text-danger">*</span></label>
                        <input type="text" name="titre" class="form-control @error('titre') is-invalid @enderror"
                               value="{{ old('titre') }}" required>
                        @error('titre')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" rows="3"
                                  class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Résumé</label>
                        <textarea name="resume" rows="5"
                                  class="form-control @error('resume') is-invalid @enderror">{{ old('resume') }}</textarea>
                        @error('resume')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Type de document <span class="text-danger">*</span></label>
                            <select name="type_document_id" class="form-select @error('type_document_id') is-invalid @enderror" required>
                                <option value="">Sélectionner...</option>
                                @foreach($typesDocument as $type)
                                    <option value="{{ $type->id }}" {{ old('type_document_id') == $type->id ? 'selected' : '' }}>
                                        {{ $type->nom }}
                                    </option>
                                @endforeach
                            </select>
                            @error('type_document_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Classe</label>
                            <select name="classe_id" class="form-select @error('classe_id') is-invalid @enderror">
                                <option value="">Toutes</option>
                                @foreach($classes as $classe)
                                    <option value="{{ $classe->id }}" {{ old('classe_id') == $classe->id ? 'selected' : '' }}>
                                        {{ $classe->nom }}
                                    </option>
                                @endforeach
                            </select>
                            @error('classe_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Matière</label>
                            <select name="matiere_id" class="form-select @error('matiere_id') is-invalid @enderror">
                                <option value="">Toutes</option>
                                @foreach($matieres as $matiere)
                                    <option value="{{ $matiere->id }}" {{ old('matiere_id') == $matiere->id ? 'selected' : '' }}>
                                        {{ $matiere->nom }}
                                    </option>
                                @endforeach
                            </select>
                            @error('matiere_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-md-4">
                            <label class="form-label">Période</label>
                            <select name="periode_id" class="form-select">
                                <option value="">Toutes</option>
                                @foreach($periodes as $periode)
                                    <option value="{{ $periode->id }}" {{ old('periode_id') == $periode->id ? 'selected' : '' }}>
                                        {{ $periode->nom }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tags</label>
                            <input type="text" name="tags_input" id="tagsInput" class="form-control"
                                   placeholder="Séparer par des virgules" value="{{ old('tags_input') }}">
                            <input type="hidden" name="tags" id="tagsHidden">
                        </div>
                    </div>
                </div>

                <div class="panel p-4 mb-4">
                    <h5 class="mb-3">Fichier <span class="text-danger">*</span></h5>
                    <div class="mb-3">
                        <input type="file" name="fichier" id="fichierInput"
                               class="form-control @error('fichier') is-invalid @enderror"
                               accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.odt,.ods,.odp,.txt,.zip" required>
                        @error('fichier')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Formats acceptés : PDF, images, Office, TXT, ZIP. Max 50 Mo.</div>
                    </div>
                    <div id="fichierPreview" class="d-none mt-3">
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-file-earmark me-2"></i>
                            <span id="fichierName"></span>
                            <span id="fichierSize" class="text-muted ms-2"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="panel p-4 mb-4">
                    <h5 class="mb-3">Paramètres</h5>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_public" value="0">
                            <input type="checkbox" name="is_public" value="1" id="isPublic"
                                   class="form-check-input" {{ old('is_public', 0) ? 'checked' : '' }}>
                            <label class="form-check-label" for="isPublic">Document public</label>
                        </div>
                        <div class="form-text">Visible par tous les visiteurs du site.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Statut</label>
                        <select name="statut" class="form-select">
                            <option value="brouillon" {{ old('statut') === 'brouillon' ? 'selected' : '' }}>Brouillon</option>
                            <option value="en_attente" {{ old('statut') === 'en_attente' ? 'selected' : '' }}>Soumettre à validation</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <a href="{{ route('bibliotheque.index') }}" class="btn btn-light flex-fill">
                        <i class="bi bi-x-circle me-1"></i>Annuler
                    </a>
                    <button type="submit" class="btn btn-primary flex-fill">
                        <i class="bi bi-check-circle me-1"></i>Enregistrer
                    </button>
                </div>
            </div>
        </div>
    </form>

</div>

@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assetBiblio/css/bibliotheque.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('assetBiblio/js/bibliotheque.js') }}"></script>
@endpush
