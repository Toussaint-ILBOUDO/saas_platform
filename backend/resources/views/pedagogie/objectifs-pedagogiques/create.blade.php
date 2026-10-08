@extends('panel.layouts.app')

@section('title', 'Nouvel objectif pédagogique')

@section('content')

<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon">
                <i class="bi bi-bullseye"></i>
            </div>
            <div>
                <h1 class="mb-0">Nouvel objectif pédagogique</h1>
                <p class="text-muted mb-0">
                    Définir les objectifs pour un élève
                </p>
            </div>
        </div>
    </div>

    <form action="{{ route('objectifs-pedagogiques.store') }}" method="POST">
        @csrf

        {{-- Élève et Période --}}
        <div class="panel mb-4">
            <div class="panel-header">
                <h5 class="mb-0">Élève et Période</h5>
            </div>
            <div class="p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Élève</label>
                        <select name="eleve_id" id="eleve_id" class="form-select @error('eleve_id') is-invalid @enderror" required>
                            <option value="">Sélectionner un élève...</option>
                            @foreach($eleves as $eleve)
                                <option value="{{ $eleve->id }}"
                                    {{ old('eleve_id') == $eleve->id ? 'selected' : '' }}>
                                    {{ $eleve->user->nom }} {{ $eleve->user->prenom }}
                                    — {{ $eleve->classe?->nom ?? 'Sans classe' }}
                                </option>
                            @endforeach
                        </select>
                        @error('eleve_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Période comptable</label>
                        <select name="periode_id" class="form-select @error('periode_id') is-invalid @enderror" required>
                            <option value="">Sélectionner une période...</option>
                            @foreach($periodes as $periode)
                                <option value="{{ $periode->id }}" @selected((int) old('periode_id') === $periode->id)>
                                    {{ $periode->label }}
                                    ({{ $periode->date_debut->format('d/m/Y') }}
                                    — {{ $periode->date_fin->format('d/m/Y') }})
                                </option>
                            @endforeach
                        </select>
                        @error('periode_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        @if($periodes->isEmpty())
                            <div class="form-text text-danger">
                                Aucune période comptable ouverte : créez-en une avant de saisir des objectifs.
                            </div>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Moyenne générale visée</label>
                        <input type="number" step="0.01" min="0" max="20"
                               name="moyenne_visee"
                               value="{{ old('moyenne_visee') }}"
                               class="form-control" placeholder="Ex: 12.00">
                    </div>
                </div>
            </div>
        </div>

        {{-- Matériel --}}
        <div class="panel mb-4">
            <div class="panel-header">
                <h5 class="mb-0">Matériel pédagogique</h5>
            </div>
            <div class="p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Matériel disponible</label>
                        <textarea name="materiel_disponible" rows="3"
                                  class="form-control"
                                  placeholder="Livres, cahiers, matériel fourni...">{{ old('materiel_disponible') }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Matériel manquant</label>
                        <textarea name="materiel_manquant" rows="3"
                                  class="form-control"
                                  placeholder="Matériel à acquisitionner (alerte admin)...">{{ old('materiel_manquant') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- Matières --}}
        <div class="panel mb-4">
            <div class="panel-header">
                <h5 class="mb-0">
                    Matières et objectifs
                    <button type="button" class="btn btn-sm btn-outline-primary ms-2" id="addMatiere">
                        <i class="bi bi-plus"></i> Ajouter
                    </button>
                </h5>
            </div>
            <div class="p-4" id="matieresContainer">
                @php
                    $oldMatieres = old('matieres', [['matiere_id' => '', 'moyenne_visee' => '']]);
                @endphp

                @foreach($oldMatieres as $index => $oldMat)
                    <div class="row g-3 mb-3 matiere-row">
                        <div class="col-md-6">
                            <label class="form-label">Matière</label>
                            <select name="matieres[{{ $index }}][matiere_id]" class="form-select" required>
                                <option value="">Sélectionner...</option>
                                @foreach($matieres as $matiere)
                                    <option value="{{ $matiere->id }}"
                                        {{ ($oldMat['matiere_id'] ?? '') == $matiere->id ? 'selected' : '' }}>
                                        {{ $matiere->nom }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Moyenne visée</label>
                            <input type="number" step="0.01" min="0" max="20"
                                   name="matieres[{{ $index }}][moyenne_visee]"
                                   value="{{ $oldMat['moyenne_visee'] ?? '' }}"
                                   class="form-control" placeholder="Ex: 12.00">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-danger btn-sm removeMatiere">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
            @error('matieres')
                <div class="text-danger small px-4">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('objectifs-pedagogiques.index') }}" class="btn btn-light">
                <i class="bi bi-x-circle"></i>
                Annuler
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-circle"></i>
                Enregistrer
            </button>
        </div>
    </form>

</div>

@endsection

@push('scripts')
<script>
    let matiereIndex = {{ count($oldMatieres) }};

    document.getElementById('addMatiere').addEventListener('click', function() {
        const container = document.getElementById('matieresContainer');
        const matieresOptions = @json($matieres->map(fn($m) => ['id' => $m->id, 'nom' => $m->nom])->toArray());

        let optionsHtml = '<option value="">Sélectionner...</option>';
        matieresOptions.forEach(function(m) {
            optionsHtml += `<option value="${m.id}">${m.nom}</option>`;
        });

        const row = document.createElement('div');
        row.className = 'row g-3 mb-3 matiere-row';
        row.innerHTML = `
            <div class="col-md-6">
                <label class="form-label">Matière</label>
                <select name="matieres[${matiereIndex}][matiere_id]" class="form-select" required>
                    ${optionsHtml}
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Moyenne visée</label>
                <input type="number" step="0.01" min="0" max="20"
                       name="matieres[${matiereIndex}][moyenne_visee]"
                       class="form-control" placeholder="Ex: 12.00">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="button" class="btn btn-danger btn-sm removeMatiere">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        `;

        container.appendChild(row);
        matiereIndex++;

        row.querySelector('.removeMatiere').addEventListener('click', function() {
            row.remove();
        });
    });

    document.querySelectorAll('.removeMatiere').forEach(function(btn) {
        btn.addEventListener('click', function() {
            this.closest('.matiere-row').remove();
        });
    });
</script>
@endpush
