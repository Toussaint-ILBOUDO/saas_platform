@extends('panel.layouts.app')

@section('title', 'Créer un contrat')

@section('content')

<div class="container-fluid">

    <div class="page-heading mb-4">
        <h1>Créer un contrat</h1>
        <p class="text-muted">Créer un contrat et affecter des enseignants</p>
    </div>

    <form method="POST" action="{{ route('contrats.store') }}">
        @csrf

        <div class="row g-3">

            {{-- =====================
                ÉLÈVE
            ====================== --}}
            <div class="col-md-6">
                <label class="form-label">Élève</label>

                <select name="eleve_id" class="form-select select2">
                    <option value="">-- choisir élève --</option>
                    @foreach($eleves as $eleve)
                        <option value="{{ $eleve->id }}">
                            {{ $eleve->user->nom }} {{ $eleve->user->prenom }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- TYPE COURS --}}
            <div class="col-md-6">
                <label class="form-label">Type de cours</label>

                <select name="type_cours_id" class="form-select">
                    @foreach($typesCours ?? [] as $type)
                        <option value="{{ $type->id }}">
                            {{ $type->libelle }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- DATES --}}
            <div class="col-md-6">
                <label class="form-label">Date début</label>
                <input type="date" name="date_debut" class="form-control">
            </div>

            <div class="col-md-6">
                <label class="form-label">Date fin</label>
                <input type="date" name="date_fin" class="form-control">
            </div>

            {{-- NOTES --}}
            <div class="col-12">
                <label class="form-label">Notes administratives</label>
                <textarea name="notes_admin" class="form-control" rows="3"></textarea>
            </div>

            {{-- =====================
                AFFECTATIONS
            ====================== --}}
            <div class="col-12">
                <hr>
                <h5>Affectations enseignants</h5>

                <div id="affectations-wrapper"></div>

                <button type="button"
                        class="btn btn-outline-primary mt-3"
                        onclick="addAffectation()">
                    + Ajouter une affectation
                </button>
            </div>

            {{-- SUBMIT --}}
            <div class="col-12 text-end mt-4">
                <button class="btn btn-success">
                    Créer le contrat
                </button>
            </div>

        </div>
    </form>
</div>

{{-- =====================
    JS INLINE (OK COMME TU VEUX)
===================== --}}
@push('scripts')
<script>
let index = 0;

function addAffectation() {

    const wrapper = document.getElementById('affectations-wrapper');

    const html = `
    <div class="card p-3 mb-3 border">

        <div class="row g-3">

            {{-- MATIÈRE --}}
            <div class="col-md-4">
                <label class="form-label">Matière</label>
                <select name="affectations[${index}][matiere_id]" class="form-select" onchange="loadEnseignants(this, ${index})">
                    @foreach($matieres as $matiere)
                        <option value="{{ $matiere->id }}">
                            {{ $matiere->nom }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- ENSEIGNANT --}}
            <div class="col-md-4">
                <label class="form-label">Enseignant</label>
                <select name="affectations[${index}][enseignant_id]" class="form-select">
                    <option value="">-- choisir enseignant --</option>
                </select>
            </div>

            {{-- HEURES --}}
            <div class="col-md-2">
                <label class="form-label">Heures</label>
                <input type="number"
                       name="affectations[${index}][nombre_heures_prevues]"
                       class="form-control">
            </div>

            {{-- TAUX --}}
            <div class="col-md-2">
                <label class="form-label">Taux</label>
                <input type="number"
                       name="affectations[${index}][taux_horaire_enseignant]"
                       class="form-control">
            </div>

        </div>

        <div class="text-end mt-2">
            <button type="button"
                    class="btn btn-sm btn-danger"
                    onclick="this.closest('.card').remove()">
                supprimer
            </button>
        </div>

    </div>`;

    wrapper.insertAdjacentHTML('beforeend', html);

    index++;
}

async function loadEnseignants(select, index) {

    const matiereId = select.value;

    const response = await fetch(`/matieres/${matiereId}/enseignants`);

    const enseignants = await response.json();

    const selectEnseignant = document.querySelector(
        `select[name="affectations[${index}][enseignant_id]"]`
    );

    selectEnseignant.innerHTML = '';

    enseignants.forEach(ens => {
        const option = document.createElement('option');
        option.value = ens.id;
        option.textContent = ens.nom + ' ' + ens.prenom;
        selectEnseignant.appendChild(option);
    });
}
</script>
@endpush

@endsection