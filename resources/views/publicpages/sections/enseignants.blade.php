{{-- ======================================================
    Section : Nos enseignants
====================================================== --}}

<section id="doctors" class="doctors section">

    <div class="container section-title" data-aos="fade-up">

        <h2>Nos enseignants qualifiés</h2>

        <p>
            K'Educ met à votre disposition des enseignants expérimentés pour les cours d'appui,
            les cours à domicile et les cours en ligne partout au Burkina Faso.
            Chaque enseignant est sélectionné selon ses compétences académiques,
            son expérience pédagogique et sa maîtrise des matières enseignées.
        </p>

    </div>

    <div class="container">

        @if($enseignants->count())

        <div class="row gy-4">

            @foreach($enseignants as $enseignant)

                <div class="col-lg-6"
                     data-aos="fade-up"
                     data-aos-delay="{{ ($loop->iteration * 100) }}">

                    <div class="team-member d-flex align-items-start">

                        <div class="pic">

                            <img
                                src="{{ $enseignant->photo_profil_url }}"
                                class="img-fluid"
                                alt="{{ $enseignant->prenom }} {{ $enseignant->nom }}"
                            >

                        </div>

                        <div class="member-info">

                            <h4>
                                {{ $enseignant->prenom }}
                                {{ $enseignant->nom }}
                            </h4>

                            <span>
                                Enseignant K'Educ
                            </span>

                            <p>

                                @if($enseignant->enseignantProfil)

                                    Diplôme :
                                    <strong>
                                        {{ $enseignant->enseignantProfil->diplome_max }}
                                    </strong>

                                    <br>

                                    Lieu de service :
                                    <strong>
                                        {{ $enseignant->enseignantProfil->lieu_de_service }}
                                    </strong>

                                @endif

                            </p>

                            <div class="mt-2">

                                @forelse(
                                    $enseignant->enseignantProfil?->matieres ?? []
                                    as $matiere
                                )

                                    <span class="badge bg-success me-1 mb-1">

                                        {{ $matiere->nom }}

                                    </span>

                                @empty

                                    <span class="badge bg-secondary">
                                        Matières à venir
                                    </span>

                                @endforelse

                            </div>

                        </div>

                    </div>
                </div>

            @endforeach

        </div>

        @else

            <div class="text-center text-muted py-4">
                <i class="bi bi-people display-5"></i>
                <p class="mt-2 mb-0">La liste de nos enseignants arrive bientôt.</p>
            </div>

        @endif

        {{-- CTA SEO --}}
        <div class="text-center mt-5">

            <h4 class="mb-3">
                Besoin d'un enseignant qualifié pour votre enfant ?
            </h4>

            <p>
                Nous vous aidons à trouver rapidement un enseignant adapté au niveau,
                à la matière et aux objectifs de l'apprenant.
            </p>

            <a href="#appointment"
               class="btn btn-success btn-lg px-5">

                🎓 Demander un cours
            </a>

        </div>

    </div>

</section>