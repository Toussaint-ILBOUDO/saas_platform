@extends('panel.layouts.app')

@section('title', 'Modifier une matière')

@section('content')

<div class="container-fluid">

    <div class="row justify-content-center">

        <div class="col-lg-8">

            <div class="card border-0 shadow-sm">

                <div class="card-header bg-transparent">

                    <h4 class="mb-0">

                        Modifier la matière

                    </h4>

                </div>

                <div class="card-body">

                    <form method="POST"
                          action="{{ route('matieres.update', $matiere) }}">

                        @csrf
                        @method('PUT')

                        <div class="mb-3">

                            <label class="form-label">
                                Nom
                            </label>

                            <input type="text"
                                   name="nom"
                                   value="{{ old('nom', $matiere->nom) }}"
                                   class="form-control">

                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                Sigle
                            </label>

                            <input type="text"
                                   name="sigle"
                                   value="{{ old('sigle', $matiere->sigle) }}"
                                   class="form-control">

                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                Description
                            </label>

                            <textarea name="description"
                                      rows="4"
                                      class="form-control">{{ old('description', $matiere->description) }}</textarea>

                        </div>

                        <div class="form-check mb-4">

                            <input class="form-check-input"
                                   type="checkbox"
                                   name="actif"
                                   value="1"
                                   {{ old('actif', $matiere->actif) ? 'checked' : '' }}>

                            <label class="form-check-label">
                                Matière active
                            </label>

                        </div>

                        <div class="d-flex justify-content-end gap-2">

                            <a href="{{ route('matieres.index') }}"
                               class="btn btn-light">

                                Retour

                            </a>

                            <button type="submit"
                                    class="btn btn-primary">

                                <i class="bi bi-save me-1"></i>

                                Mettre à jour

                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection