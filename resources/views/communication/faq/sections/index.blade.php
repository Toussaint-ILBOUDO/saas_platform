@extends('panel.layouts.app')

@section('title', 'FAQ — Administration')

@section('content')

<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon"><i class="bi bi-question-circle"></i></div>
            <div>
                <h1 class="mb-0">Sections FAQ</h1>
                <p class="text-muted mb-0">Gestion des sections de la foire aux questions</p>
            </div>
        </div>
        <div class="heading-actions">
            <a href="{{ route('admin.faq.sections.create') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Nouvelle section
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
                <h5 class="mb-0">Liste des sections</h5>
                <p class="text-muted mb-0">Ordre et visibilité des sections affichées sur la page d'accueil</p>
            </div>
        </div>
        <div class="p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Ordre</th>
                            <th>Titre</th>
                            <th class="text-center">Questions</th>
                            <th class="text-center">Statut</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sections as $section)
                            <tr>
                                <td class="text-muted">{{ $section->order_index }}</td>
                                <td>
                                    <span class="fw-semibold">{{ $section->title }}</span>
                                    @if($section->description)
                                        <div class="text-muted" style="font-size: 0.8rem;">{{ Str::limit($section->description, 60) }}</div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('admin.faq.questions.index', $section) }}"
                                       class="badge bg-primary-subtle text-primary text-decoration-none">
                                        {{ $section->questions_count }} question(s)
                                    </a>
                                </td>
                                <td class="text-center">
                                    @if($section->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <a href="{{ route('admin.faq.questions.index', $section) }}"
                                           class="btn btn-sm btn-light" title="Voir les questions">
                                            <i class="bi bi-list-ul"></i>
                                        </a>
                                        <a href="{{ route('admin.faq.sections.edit', $section) }}"
                                           class="btn btn-sm btn-light" title="Modifier">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('admin.faq.sections.toggle', $section) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-light" title="{{ $section->is_active ? 'Désactiver' : 'Activer' }}">
                                                <i class="bi {{ $section->is_active ? 'bi-eye-slash' : 'bi-eye' }}"></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.faq.sections.destroy', $section) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Supprimer cette section et toutes ses questions ?');">
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
                                <td colspan="5" class="text-center py-5">
                                    <div class="blank-state">
                                        <i class="bi bi-question-circle display-4 text-muted"></i>
                                        <p class="text-muted mt-2">Aucune section FAQ.</p>
                                        <a href="{{ route('admin.faq.sections.create') }}" class="btn btn-primary btn-sm">
                                            Créer une section
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($sections->hasPages())
            <div class="panel-footer">
                {{ $sections->links() }}
            </div>
        @endif
    </div>

</div>

@endsection
