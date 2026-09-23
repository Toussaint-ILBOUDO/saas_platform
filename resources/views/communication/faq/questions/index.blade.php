@extends('panel.layouts.app')

@section('title', 'Questions — ' . $section->title)

@section('content')

<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon"><i class="bi bi-chat-square-text"></i></div>
            <div>
                <h1 class="mb-0">Questions — {{ $section->title }}</h1>
                <p class="text-muted mb-0">Gestion des questions de la section</p>
            </div>
        </div>
        <div class="heading-actions">
            <a href="{{ route('admin.faq.sections.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Retour
            </a>
            <a href="{{ route('admin.faq.questions.create', $section) }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Nouvelle question
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="panel">
        <div class="panel-header">
            <div>
                <h5 class="mb-0">Liste des questions</h5>
                <p class="text-muted mb-0">Ordre et visibilité des questions de la section « {{ $section->title }} »</p>
            </div>
        </div>
        <div class="p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Ordre</th>
                            <th>Question</th>
                            <th class="text-center">Statut</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($questions as $question)
                            <tr>
                                <td class="text-muted">{{ $question->order_index }}</td>
                                <td>
                                    <span class="fw-semibold">{{ $question->question }}</span>
                                    <div class="text-muted" style="font-size: 0.8rem;">{{ Str::limit(strip_tags($question->answer), 80) }}</div>
                                </td>
                                <td class="text-center">
                                    @if($question->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <a href="{{ route('admin.faq.questions.edit', [$section, $question]) }}"
                                           class="btn btn-sm btn-light" title="Modifier">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('admin.faq.questions.toggle', [$section, $question]) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-light" title="{{ $question->is_active ? 'Désactiver' : 'Activer' }}">
                                                <i class="bi {{ $question->is_active ? 'bi-eye-slash' : 'bi-eye' }}"></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.faq.questions.destroy', [$section, $question]) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Supprimer cette question ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light text-danger" title="Supprimer">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-5">
                                    <div class="blank-state">
                                        <i class="bi bi-chat-square-text display-4 text-muted"></i>
                                        <p class="text-muted mt-2">Aucune question dans cette section.</p>
                                        <a href="{{ route('admin.faq.questions.create', $section) }}" class="btn btn-primary btn-sm">
                                            Ajouter une question
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($questions->hasPages())
            <div class="panel-footer">
                {{ $questions->links() }}
            </div>
        @endif
    </div>

</div>

@endsection
