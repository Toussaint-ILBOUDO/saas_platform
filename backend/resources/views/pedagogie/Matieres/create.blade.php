@extends('panel.layouts.app')

@section('title', 'Nouvelle matière')

@section('content')

<div class="container-fluid">

    <div class="row justify-content-center">

        <div class="col-lg-8">

            <div class="card border-0 shadow-sm">

                <div class="card-header bg-transparent">

                    <h4 class="mb-0">
                        Nouvelle matière
                    </h4>

                </div>

                <div class="card-body">

                    <form method="POST"
                          action="{{ route('matieres.store') }}">

                        @csrf

                        <div class="mb-3">

                            <label class="form-label">
                                Nom
                            </label>

                            <input type="text"
                                   name="nom"
                                   value="{{ old('nom') }}"
                                   class="form-control @error('nom') is-invalid @enderror">

                            @error('nom')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                Sigle
                            </label>

                            <input type="text"
                                   name="sigle"
                                   value="{{ old('sigle') }}"
                                   class="form-control @error('sigle') is-invalid @enderror">

                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                Description
                            </label>

                            <textarea name="description"
                                      rows="4"
                                      class="form-control">{{ old('description') }}</textarea>

                        </div>

                        <div class="form-check mb-4">

                            <input class="form-check-input"
                                   type="checkbox"
                                   name="actif"
                                   value="1"
                                   checked>

                            <label class="form-check-label">
                                Matière active
                            </label>

                        </div>

                        <div class="d-flex justify-content-end gap-2">

                            <a href="{{ route('matieres.index') }}"
                               class="btn btn-light">

                                Annuler

                            </a>

                            <button type="submit"
                                    class="btn btn-primary">

                                <i class="bi bi-check-circle me-1"></i>

                                Enregistrer

                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection