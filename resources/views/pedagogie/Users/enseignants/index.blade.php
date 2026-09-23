@extends('panel.layouts.app')

@section('title', 'Enseignants')

@section('content')

<div class="container-fluid">

    <div class="page-heading">

        <div class="page-heading-copy">

            <div class="page-icon">
                <i class="bi bi-person-workspace"></i>
            </div>

            <div>
                <h1 class="mb-0">Enseignants</h1>
                <p class="text-muted mb-0">
                    Gestion des enseignants du cabinet
                </p>
            </div>

        </div>

        <div class="heading-actions">

            <a href="{{ route('enseignants.create') }}"
               class="btn btn-primary">

                <i class="bi bi-plus-lg"></i>
                Nouvel enseignant

            </a>

        </div>

    </div>

    <div class="panel">

        <div class="panel-header">

            <div>
                <h5 class="mb-0">Liste des enseignants</h5>
                <p class="text-muted mb-0">
                    Tous les enseignants enregistrés
                </p>
            </div>

        </div>

        <div class="table-responsive">

            <table class="table align-middle">

                <thead>
                <tr>
                    <th>Enseignant</th>
                    <th>Téléphone</th>
                    <th>Email</th>
                    <th>Matières</th>
                    <th>Diplôme</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>

                <tbody>

                @forelse($enseignants as $enseignant)

                    <tr>

                        <td>
                            <strong>
                                {{ $enseignant->nom }}
                                {{ $enseignant->prenom }}
                            </strong>
                        </td>

                        <td>
                            {{ $enseignant->telephone_whatsapp ?? $enseignant->telephone_appel ?? '-' }}
                        </td>

                        <td>
                            {{ $enseignant->email ?: '-' }}
                        </td>

                        <td>

                            @if($enseignant->enseignantProfil && $enseignant->enseignantProfil->matieres->count())
                                @foreach($enseignant->enseignantProfil->matieres->take(3) as $matiere)
                                    <span class="badge text-bg-primary me-1">
                                        {{ $matiere->sigle }}
                                    </span>
                                @endforeach

                                @if($enseignant->enseignantProfil->matieres->count() > 3)
                                    <span class="badge text-bg-secondary">
                                        +{{ $enseignant->enseignantProfil->matieres->count() - 3 }}
                                    </span>
                                @endif
                            @else
                                <span class="text-muted">-</span>
                            @endif

                        </td>

                        <td>
                            {{ $enseignant->enseignantProfil?->diplome_max ?? '-' }}
                        </td>

                        <td class="text-end">

                            <a href="{{ route('enseignants.show', $enseignant) }}"
                               class="btn btn-sm btn-light">

                                <i class="bi bi-eye"></i>

                            </a>

                            <a href="{{ route('enseignants.edit', $enseignant) }}"
                               class="btn btn-sm btn-light">

                                <i class="bi bi-pencil-square"></i>

                            </a>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="6">

                            <div class="blank-panel">

                                <div class="blank-state">

                                    <i class="bi bi-inbox display-5 text-muted"></i>

                                    <p class="mt-2 text-muted">
                                        Aucun enseignant enregistré
                                    </p>

                                </div>

                            </div>

                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

        <div class="mt-3">
            {{ $enseignants->links() }}
        </div>

    </div>

</div>

@endsection
