@extends('panel.layouts.app')

@section('title', 'Modifier mon témoignage')

@section('content')

<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon"><i class="bi bi-chat-quote"></i></div>
            <div>
                <h1 class="mb-0">Modifier mon témoignage</h1>
                <p class="text-muted mb-0">Mettez à jour le contenu de votre témoignage</p>
            </div>
        </div>
        <div class="heading-actions">
            <a href="{{ route('temoignages.mes.index') }}" class="btn btn-sm btn-light">
                <i class="bi bi-arrow-left me-1"></i>Retour
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
        <div class="panel-body">
            <form method="POST" action="{{ route('temoignages.update', $temoignage) }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="contenu" class="form-label">Votre témoignage <span class="text-danger">*</span></label>
                    <textarea name="contenu" id="contenu" rows="7" class="form-control"
                              placeholder="Décrivez votre expérience avec K'Educ (20 à 2000 caractères)"
                              required minlength="20" maxlength="2000">{{ old('contenu', $temoignage->contenu) }}</textarea>
                    <div class="form-text text-muted text-end">
                        <span id="contenu-count">0</span>/2000
                    </div>
                </div>

                <div class="form-check mb-4">
                    <input type="checkbox" class="form-check-input" id="anonyme" name="anonyme" value="1"
                           @checked(old('anonyme', $temoignage->anonyme))>
                    <label for="anonyme" class="form-check-label">
                        Publier anonymement (le public verra « Témoignage anonyme »)
                    </label>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Enregistrer
                    </button>
                    <a href="{{ route('temoignages.mes.index') }}" class="btn btn-light">Annuler</a>
                </div>
            </form>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
    (function () {
        var input = document.getElementById('contenu');
        var count = document.getElementById('contenu-count');
        function update() {
            count.textContent = input.value.length;
        }
        input.addEventListener('input', update);
        update();
    })();
</script>
@endpush
