{{-- ======================================================
    Section : Services (Premium UI Self-contained)
====================================================== --}}

<style>
/* =========================
   SERVICES - PREMIUM UI
========================= */

.service-card {
    background: #fff;
    border-radius: 18px;
    padding: 28px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.06);
    transition: all 0.35s ease;
    height: 100%;
    position: relative;
    overflow: hidden;
}

.service-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 18px 45px rgba(0,0,0,0.12);
}

/* spotlight (cours d'appui) */
.service-card.spotlight {
    border: 2px solid #198754;
}

/* ICON */
.icon-box {
    width: 58px;
    height: 58px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 18px;
    color: #fff;
    font-size: 22px;
}

/* gradients */
.gradient-blue { background: linear-gradient(135deg,#4facfe,#00f2fe); }
.gradient-green { background: linear-gradient(135deg,#43e97b,#38f9d7); }
.gradient-purple { background: linear-gradient(135deg,#667eea,#764ba2); }
.gradient-orange { background: linear-gradient(135deg,#f7971e,#ffd200); }
.gradient-indigo { background: linear-gradient(135deg,#5f2c82,#49a09d); }
.gradient-dark { background: linear-gradient(135deg,#232526,#414345); }

/* title */
.service-card h3 {
    font-size: 20px;
    font-weight: 700;
    margin-bottom: 12px;
    color: #1f2937;
}

/* text */
.service-card p {
    color: #6b7280;
    font-size: 14.5px;
    line-height: 1.6;
}

/* button */
.service-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-top: 15px;
    font-weight: 600;
    color: #198754;
    text-decoration: none;
    transition: 0.3s ease;
}

.service-btn:hover {
    gap: 12px;
    color: #146c43;
}

/* small shine effect */
.service-card::before {
    content: "";
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.25), transparent 60%);
    transform: rotate(25deg);
    opacity: 0;
    transition: 0.4s;
}

.service-card:hover::before {
    opacity: 1;
}
</style>

<section id="services" class="services section">

    <div class="container section-title" data-aos="fade-up">
        <h2>Nos services</h2>
        <p>
            Une plateforme complète pour accompagner la réussite scolaire et l’épanouissement des élèves
        </p>
    </div>

    <div class="container">

        <div class="row gy-4">

            {{-- COURS (SPOTLIGHT) --}}
            <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">

                <div class="service-card spotlight">

                    <div class="icon-box gradient-blue">
                        <i class="bi bi-person-workspace"></i>
                    </div>

                    <h3>Cours d’appui</h3>

                    <p>
                        Cours à domicile, en ligne ou en groupe avec des enseignants qualifiés
                        pour tous les niveaux scolaires.
                    </p>

                    <a href="{{ route('demande-cours.create') }}" class="service-btn">
                        Demander un cours
                        <i class="bi bi-arrow-right"></i>
                    </a>

                </div>
            </div>

            {{-- LIBRAIRIE --}}
            <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">

                <div class="service-card">

                    <div class="icon-box gradient-green">
                        <i class="bi bi-shop"></i>
                    </div>

                    <h3>Librairie scolaire</h3>

                    <p>
                        Fournitures scolaires et équipements didactiques :
                        cahiers, livres, stylos et matériels pédagogiques.
                    </p>

                    <a href="{{ route('librairie.produits') }}" class="service-btn">
                        Voir les produits
                        <i class="bi bi-arrow-right"></i>
                    </a>

                </div>
            </div>

            {{-- BIBLIOTHÈQUE --}}
            <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">

                <div class="service-card">

                    <div class="icon-box gradient-purple">
                        <i class="bi bi-journal-bookmark-fill"></i>
                    </div>

                    <h3>Bibliothèque numérique</h3>

                    <p>
                        Devoirs, sujets d’examens et documents pédagogiques
                        issus de plusieurs établissements prestigieux.
                    </p>

                    <a href="{{ route('bibliothequepub.index') }}" class="service-btn">
                        Accéder
                        <i class="bi bi-arrow-right"></i>
                    </a>

                </div>
            </div>

            {{-- CONFÉRENCES --}}
            <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="400">

                <div class="service-card">

                    <div class="icon-box gradient-orange">
                        <i class="bi bi-mic-fill"></i>
                    </div>

                    <h3>Conférences éducatives</h3>

                    <p>
                        Orientation scolaire et motivation pour réussir son parcours académique
                        et faire les bons choix d’avenir.
                    </p>

                    <a href="#contact" class="service-btn">
                        Participer
                        <i class="bi bi-arrow-right"></i>
                    </a>

                </div>
            </div>

            {{-- FORMATIONS --}}
            <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="500">

                <div class="service-card">

                    <div class="icon-box gradient-indigo">
                        <i class="bi bi-mortarboard-fill"></i>
                    </div>

                    <h3>Formations</h3>

                    <p>
                        Formations pour élèves et enseignants :
                        pédagogie, renforcement et outils numériques.
                    </p>

                    <a href="#contact" class="service-btn">
                        Découvrir
                        <i class="bi bi-arrow-right"></i>
                    </a>

                </div>
            </div>

            {{-- AUTRES --}}
            <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="600">

                <div class="service-card">

                    <div class="icon-box gradient-dark">
                        <i class="bi bi-stars"></i>
                    </div>

                    <h3>Autres services</h3>

                    <p>
                        Accompagnement scolaire, suivi personnalisé
                        et solutions adaptées à chaque élève.
                    </p>

                    <a href="#contact" class="service-btn">
                        En savoir plus
                        <i class="bi bi-arrow-right"></i>
                    </a>

                </div>
            </div>

        </div>

    </div>

</section>