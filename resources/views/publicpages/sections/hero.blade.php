<section id="hero" class="hero section position-relative">

    {{-- IMAGE DE FOND --}}
    <img src="{{ asset('templates/publicpages/assets/img/hero-bg.png') }}"
         alt="Keduc background"
         data-aos="fade-in"
         class="hero-bg">

    {{-- OVERLAY POUR LISIBILITÉ --}}
    <div class="hero-overlay"></div>

    <div class="container position-relative">

        {{-- ================= HERO TEXT ================= --}}
        <div class="welcome text-center text-white position-relative"
             data-aos="fade-down"
             data-aos-delay="100">

            <h1 class="fw-bold" style="font-size: 42px; line-height: 1.2;">
                BIENVENUE SUR K'EDUC
            </h1>

            <p class="mt-3 fs-5 opacity-75">
                L'école pour tous les âges — accompagnement scolaire du primaire au supérieur partout au Burkina Faso
            </p>

            {{-- CTA --}}
            <div class="mt-4 d-flex justify-content-center flex-wrap gap-2">

                <a href="{{ route('demande-cours.create') }}"
                   class="btn btn-success btn-lg px-4 shadow">
                    🎓 Demander un cours d’appui
                </a>

                <a href="#services"
                   class="btn btn-outline-light btn-lg px-4">
                    Voir nos services
                </a>

            </div>
        </div>

        {{-- ================= QUICK STATS HERO ================= --}}
        <div class="row mt-5 gy-3">

            <div class="col-lg-3 col-md-6">
                <div class="p-3 text-center text-white rounded-3"
                     style="background: rgba(255,255,255,0.08); backdrop-filter: blur(6px);">

                    <i class="bi bi-person-workspace fs-2 text-warning"></i>

                    <h3 class="mt-2 mb-0 fw-bold">100+</h3>
                    <small>Enseignants qualifiés</small>

                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="p-3 text-center text-white rounded-3"
                     style="background: rgba(255,255,255,0.08); backdrop-filter: blur(6px);">

                    <i class="bi bi-people fs-2 text-info"></i>

                    <h3 class="mt-2 mb-0 fw-bold">Familles</h3>
                    <small>accompagnées partout au Burkina Faso</small>

                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="p-3 text-center text-white rounded-3"
                     style="background: rgba(255,255,255,0.08); backdrop-filter: blur(6px);">

                    <i class="bi bi-journal-bookmark-fill fs-2 text-success"></i>

                    <h3 class="mt-2 mb-0 fw-bold">Bibliothèque</h3>
                    <small>de devoirs et exercices</small>

                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="p-3 text-center text-white rounded-3"
                     style="background: rgba(255,255,255,0.08); backdrop-filter: blur(6px);">

                    <i class="bi bi-globe2 fs-2 text-primary"></i>

                    <h3 class="mt-2 mb-0 fw-bold">En ligne</h3>
                    <small>Google Meet • WhatsApp • Teams</small>

                </div>
            </div>

        </div>

    </div>

    {{-- ================= INLINE STYLE (OBLIGATOIRE POUR LISSIBILITÉ) ================= --}}
    <style>
        .hero {
            min-height: 90vh;
            display: flex;
            align-items: center;
            overflow: hidden;
        }

        .hero-bg {
            position: absolute;
            width: 100%;
            height: 100%;
            object-fit: cover;
            top: 0;
            left: 0;
            z-index: 1;
            filter: brightness(0.75) saturate(1.1);
        }

        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 2;

            /* correction du vert + lisibilité */
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(2px);
        }

        .hero .container {
            z-index: 3;
        }
    </style>

</section>