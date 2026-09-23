@php
    use App\Modules\Pedagogie\Enums\PlanningJourSemaine;

    $jourSemaine = $creneau->jour_semaine ?? old('jour_semaine');
    $heureDebut = $creneau->heure_debut ?? old('heure_debut');
    $heureFin = $creneau->heure_fin ?? old('heure_fin');
@endphp

<form method="POST" action="{{ $formAction }}">

    @csrf
    @if(isset($creneau)) @method('PUT') @endif

    <div class="mb-3">

        <label for="affectation_enseignant_id" class="form-label">
            Cours concerné
        </label>

        <select
            name="affectation_enseignant_id"
            id="affectation_enseignant_id"
            class="form-select @error('affectation_enseignant_id') is-invalid @enderror"
            required
        >
            @if($affectations->isEmpty())

                <option value="">
                    Aucun cours actif — contactez l'administration
                </option>

            @else

                <option value="">
                    — Choisir un cours —
                </option>

                @foreach($affectations as $affectation)

                    <option
                        value="{{ $affectation->id }}"
                        @selected((int) old('affectation_enseignant_id', $creneau->affectation_enseignant_id ?? '') === (int) $affectation->id)
                    >
                        {{ $affectation->contrat->eleve?->user?->prenom }}
                        {{ $affectation->contrat->eleve?->user?->nom }}
                        — {{ $affectation->matiere->nom }}
                    </option>

                @endforeach

            @endif
        </select>

        @error('affectation_enseignant_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror

    </div>

    <div class="mb-3">

        <label for="jour_semaine" class="form-label">
            Jour (toutes les semaines)
        </label>

        <select
            name="jour_semaine"
            id="jour_semaine"
            class="form-select @error('jour_semaine') is-invalid @enderror"
            required
        >
            <option value="">— Choisir un jour —</option>

            @foreach(PlanningJourSemaine::VALIDES as $jour)

                <option
                    value="{{ $jour }}"
                    @selected((int) old('jour_semaine', $jourSemaine ?? '') === $jour)
                >
                    {{ PlanningJourSemaine::label($jour) }}
                </option>

            @endforeach
        </select>

        @error('jour_semaine')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror

    </div>

    <div class="row g-2 mb-3">

        <div class="col-6">

            <label for="heure_debut" class="form-label">
                De
            </label>

            <input
                type="time"
                step="60"
                name="heure_debut"
                id="heure_debut"
                value="{{ $heureDebut }}"
                class="form-control @error('heure_debut') is-invalid @enderror"
                required
            >

            @error('heure_debut')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror

        </div>

        <div class="col-6">

            <label for="heure_fin" class="form-label">
                À
            </label>

            <input
                type="time"
                step="60"
                name="heure_fin"
                id="heure_fin"
                value="{{ $heureFin }}"
                class="form-control @error('heure_fin') is-invalid @enderror"
                required
            >

            @error('heure_fin')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror

        </div>

    </div>

    @if($affectations->isEmpty() && !isset($creneau))

        <button type="submit" class="btn btn-primary w-100" disabled>
            <i class="bi bi-calendar-plus me-1"></i>
            Enregistrer le créneau
        </button>

    @else

        <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-calendar-plus me-1"></i>
            {{ isset($creneau) ? 'Mettre à jour' : 'Enregistrer le créneau' }}
        </button>

    @endif

</form>