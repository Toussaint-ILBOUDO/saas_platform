@extends('panel.layouts.app')

@section('title', 'Contester le bulletin ' . $bulletin->numero)

@section('content')
<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon"><i class="bi bi-exclamation-triangle"></i></div>
            <div>
                <h1 class="mb-0">Contester le bulletin {{ $bulletin->numero }}</h1>
                <p class="text-muted mb-0">
                    {{ $bulletin->periode?->label }}
                </p>
            </div>
        </div>

        <div class="heading-actions">
            <a href="{{ route('mes-bulletins.show', $bulletin) }}" class="btn btn-light">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="panel">
        <div class="panel-header">
            <h5 class="mb-0">Motif de la contestation</h5>
        </div>
        <div class="panel-body">
            <form method="POST" action="{{ route('mes-bulletins.contester', $bulletin) }}">
                @csrf

                <p class="text-muted">
                    Indiquez ce que vous contestez, puis précisez. Votre
                    contestation est transmise à l'administration, qui corrige
                    le bulletin et vous le resoumet.
                </p>

                <div class="mb-3">
                    <label for="motif_contestation" class="form-label">
                        Motif de la contestation <span class="text-danger">*</span>
                    </label>

                    <select name="motif_contestation"
                            id="motif_contestation"
                            class="form-select @error('motif_contestation') is-invalid @enderror"
                            required>

                        <option value="">— Choisissez ce que vous contestez —</option>

                        @foreach(\App\Models\BulletinPaie::MOTIFS_CONTESTATION as $cle => $libelle)
                            <option value="{{ $cle }}"
                                @selected(old('motif_contestation', $bulletin->motif_contestation) === $cle)>
                                {{ $libelle }}
                            </option>
                        @endforeach

                    </select>

                    @error('motif_contestation')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="commentaire_enseignant" class="form-label">
                        Détail de la contestation <span class="text-danger">*</span>
                    </label>

                    <textarea name="commentaire_enseignant"
                              id="commentaire_enseignant"
                              class="form-control @error('commentaire_enseignant') is-invalid @enderror"
                              rows="5"
                              minlength="20"
                              maxlength="1000"
                              required
                              placeholder="La séance du 12 mars (2 h) ne figure pas sur mon cahier de texte, ou le taux appliqué n'est pas celui de mon contrat.">{{ old('commentaire_enseignant', $bulletin->commentaire_enseignant) }}</textarea>

                    <div class="form-text">
                        20 caractères minimum : l'administration doit comprendre
                        précisément ce qui est contesté.
                    </div>

                    @error('commentaire_enseignant')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-send"></i> Soumettre la contestation
                    </button>
                    <a href="{{ route('mes-bulletins.show', $bulletin) }}" class="btn btn-light">
                        Annuler
                    </a>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
