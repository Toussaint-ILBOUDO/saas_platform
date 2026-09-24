@extends('panel.layouts.app')

@section('title', 'Mes évaluations')

@section('content')

<div class="container-fluid">

<div class="page-heading">

    <div class="page-heading-copy">

        <div class="page-icon">
            <i class="bi bi-clipboard-check"></i>
        </div>

        <div>

            <h1 class="mb-0">
                Mes évaluations
            </h1>

            <p class="text-muted mb-0">
                Les appréciations portées sur vos cours
            </p>

        </div>

    </div>

</div>

<div class="panel">

    <div class="panel-header">

        <div>

            <h5 class="mb-0">
                Évaluations de mes cours
            </h5>

        </div>

    </div>

    <div class="table-responsive">

        <table class="table align-middle">

            <thead>

                <tr>

                    <th>Date</th>
                    <th>Enseignant</th>
                    <th>Note</th>
                    <th>Commentaire</th>

                </tr>

            </thead>

            <tbody>

            @forelse($evaluations as $evaluation)

                <tr>

                    <td>
                        {{ $evaluation->created_at?->format('d/m/Y') }}
                    </td>

                    <td>

                        {{ $evaluation->enseignant?->user?->nom }}
                        {{ $evaluation->enseignant?->user?->prenom }}

                        @if($evaluation->anonyme)

                            <span class="text-muted">(anonyme)</span>

                        @endif

                    </td>

                    <td>

                        @if($evaluation->note !== null)

                            <span class="badge text-bg-light border">
                                {{ $evaluation->note }}/5
                            </span>

                        @else

                            <span class="text-muted">-</span>

                        @endif

                    </td>

                    <td>
                        {{ $evaluation->commentaire ?: '-' }}
                    </td>

                </tr>

            @empty

                <tr>

                    <td colspan="4">

                        <div class="blank-panel">

                            <div class="blank-state">

                                <i class="bi bi-clipboard-check display-4 text-muted"></i>

                                <p class="mt-3 text-muted">
                                    Aucune évaluation enregistrée pour le moment.
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
        {{ $evaluations->links() }}
    </div>

</div>

</div>

@endsection