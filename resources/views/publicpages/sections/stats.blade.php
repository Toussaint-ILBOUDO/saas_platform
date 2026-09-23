<section id="stats" class="stats section light-background">

    <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row gy-4">

            {{-- Enseignants --}}
            <div class="col-lg-3 col-md-6 d-flex flex-column align-items-center">

                <i class="bi bi-person-workspace"></i>

                <div class="stats-item">

                    <span
                        data-purecounter-start="0"
                        data-purecounter-end="{{ $nb_enseignants }}"
                        data-purecounter-duration="1"
                        class="purecounter">
                    </span>

                    <p>Enseignants</p>

                </div>

            </div>

            {{-- Élèves --}}
            <div class="col-lg-3 col-md-6 d-flex flex-column align-items-center">

                <i class="bi bi-people-fill"></i>

                <div class="stats-item">

                    <span
                        data-purecounter-start="0"
                        data-purecounter-end="{{ $nb_eleves }}"
                        data-purecounter-duration="1"
                        class="purecounter">
                    </span>

                    <p>Élèves</p>

                </div>

            </div>

            {{-- Familles --}}
            <div class="col-lg-3 col-md-6 d-flex flex-column align-items-center">

                <i class="bi bi-house-door-fill"></i>

                <div class="stats-item">

                    <span
                        data-purecounter-start="0"
                        data-purecounter-end="{{ $nb_familles ?? 0 }}"
                        data-purecounter-duration="1"
                        class="purecounter">
                    </span>

                    <p>Familles</p>

                </div>

            </div>

            {{-- Contrats --}}
            <div class="col-lg-3 col-md-6 d-flex flex-column align-items-center">

                <i class="bi bi-journal-bookmark-fill"></i>

                <div class="stats-item">

                    <span
                        data-purecounter-start="0"
                        data-purecounter-end="{{ $nb_contrats ?? 0 }}"
                        data-purecounter-duration="1"
                        class="purecounter">
                    </span>

                    <p>Contrats</p>

                </div>

            </div>

        </div>

    </div>

</section>