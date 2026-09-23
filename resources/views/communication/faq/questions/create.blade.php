@extends('panel.layouts.app')

@section('title', 'Nouvelle question FAQ')

@section('content')

<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon"><i class="bi bi-chat-square-text"></i></div>
            <div>
                <h1 class="mb-0">Nouvelle question</h1>
                <p class="text-muted mb-0">Ajout d'une question à la section « {{ $section->title }} »</p>
            </div>
        </div>
        <div class="heading-actions">
            <a href="{{ route('admin.faq.questions.index', $section) }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Retour
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <form action="{{ route('admin.faq.questions.store', $section) }}" method="POST">
                @csrf

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label for="faq_section_id" class="form-label fw-semibold">Section <span class="text-danger">*</span></label>
                            <select class="form-select @error('faq_section_id') is-invalid @enderror"
                                    id="faq_section_id" name="faq_section_id" required>
                                @foreach($sections as $option)
                                    <option value="{{ $option->id }}"
                                        {{ old('faq_section_id', $section->id) == $option->id ? 'selected' : '' }}>
                                        {{ $option->title }}
                                    </option>
                                @endforeach
                            </select>
                            @error('faq_section_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="question" class="form-label fw-semibold">Question <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('question') is-invalid @enderror"
                                   id="question" name="question" value="{{ old('question') }}"
                                   placeholder="Ex: Comment inscrire mon enfant ?" required autofocus>
                            @error('question')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="answer" class="form-label fw-semibold">Réponse <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('answer') is-invalid @enderror"
                                      id="answer" name="answer" rows="6"
                                      placeholder="Rédigez une réponse claire et complète">{{ old('answer') }}</textarea>
                            @error('answer')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="order_index" class="form-label fw-semibold">Ordre d'affichage</label>
                                <input type="number" class="form-control @error('order_index') is-invalid @enderror"
                                       id="order_index" name="order_index" value="{{ old('order_index', 0) }}" min="0">
                                @error('order_index')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Statut</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                                        {{ old('is_active', 1) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">Active</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Enregistrer la question
                    </button>
                    <a href="{{ route('admin.faq.questions.index', $section) }}" class="btn btn-outline-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>

</div>

@endsection
