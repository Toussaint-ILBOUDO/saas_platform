{{-- ======================================================
    Section : Nos solutions éducatives
    Fichier : sections/home/departments.blade.php
====================================================== --}}

<section id="departments" class="departments section">

    <div class="container section-title" data-aos="fade-up">
        <h2>Nos solutions éducatives</h2>

        <p>
            K'Educ accompagne les élèves, étudiants et familles partout au Burkina Faso
            grâce à des services éducatifs complets : cours d'appui, cours à domicile,
            cours en ligne, bibliothèque numérique, librairie scolaire et orientation académique.
        </p>
    </div>

    <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row">

            {{-- MENU ONGLETS --}}
            <div class="col-lg-3">

                <ul class="nav nav-tabs flex-column">

                    <li class="nav-item">
                        <a class="nav-link active show"
                           data-bs-toggle="tab"
                           href="#departments-tab-1">

                            <i class="bi bi-mortarboard-fill me-2"></i>
                            Cours d'appui
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link"
                           data-bs-toggle="tab"
                           href="#departments-tab-2">

                            <i class="bi bi-house-door-fill me-2"></i>
                            Cours à domicile
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link"
                           data-bs-toggle="tab"
                           href="#departments-tab-3">

                            <i class="bi bi-book-fill me-2"></i>
                            Bibliothèque en ligne
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link"
                           data-bs-toggle="tab"
                           href="#departments-tab-4">

                            <i class="bi bi-shop me-2"></i>
                            Librairie scolaire
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link"
                           data-bs-toggle="tab"
                           href="#departments-tab-5">

                            <i class="bi bi-mic-fill me-2"></i>
                            Formations & Conférences
                        </a>
                    </li>

                </ul>

            </div>

            {{-- CONTENU --}}
            <div class="col-lg-9 mt-4 mt-lg-0">

                <div class="tab-content">

                    {{-- COURS D'APPUI --}}
                    <div class="tab-pane active show" id="departments-tab-1">

                        <div class="row align-items-center">

                            <div class="col-lg-8 details order-2 order-lg-1">

                                <h3>Cours d'appui au Burkina Faso</h3>

                                <p class="fst-italic">
                                    Un accompagnement scolaire personnalisé pour améliorer les résultats et renforcer la confiance des apprenants.
                                </p>

                                <p>
                                    K'Educ met à disposition des enseignants qualifiés pour des cours d'appui
                                    destinés aux élèves du primaire, collège, lycée et enseignement supérieur.
                                    Nos programmes couvrent les matières scientifiques, littéraires,
                                    techniques et professionnelles.
                                </p>

                                <p>
                                    Nous intervenons dans plusieurs villes du Burkina Faso afin d'aider les élèves
                                    à préparer leurs devoirs, examens et concours dans les meilleures conditions.
                                </p>

                            </div>

                            <div class="col-lg-4 text-center order-1 order-lg-2">
                                <img src="{{ asset('templates/publicpages/assets/img/cours-appui.png') }}"
                                     class="img-fluid rounded shadow"
                                     alt="Cours d'appui Burkina Faso">
                            </div>

                        </div>

                    </div>

                    {{-- COURS A DOMICILE --}}
                    <div class="tab-pane" id="departments-tab-2">

                        <div class="row align-items-center">

                            <div class="col-lg-8 details order-2 order-lg-1">

                                <h3>Cours à domicile et cours en ligne</h3>

                                <p class="fst-italic">
                                    Des enseignants disponibles selon votre emploi du temps.
                                </p>

                                <p>
                                    Nos cours à domicile permettent aux élèves de bénéficier d'un suivi personnalisé
                                    directement chez eux avec des enseignants expérimentés.
                                </p>

                                <p>
                                    Pour les apprenants éloignés ou souhaitant plus de flexibilité,
                                    K'Educ propose également des cours en ligne via Google Meet,
                                    Microsoft Teams et WhatsApp partout au Burkina Faso.
                                </p>

                            </div>

                            <div class="col-lg-4 text-center order-1 order-lg-2">
                                <img src="{{ asset('templates/publicpages/assets/img/cours-domicile.png') }}"
                                     class="img-fluid rounded shadow"
                                     alt="Cours à domicile Burkina Faso">
                            </div>

                        </div>

                    </div>

                    {{-- BIBLIOTHEQUE --}}
                    <div class="tab-pane" id="departments-tab-3">

                        <div class="row align-items-center">

                            <div class="col-lg-8 details order-2 order-lg-1">

                                <h3>Bibliothèque en ligne Burkina Faso</h3>

                                <p class="fst-italic">
                                    Une importante base documentaire pour apprendre efficacement.
                                </p>

                                <p>
                                    Accédez à des livres numériques, sujets d'examens,
                                    annales corrigées, devoirs, exercices pratiques et ressources pédagogiques.
                                </p>

                                <p>
                                    Notre bibliothèque est conçue pour aider les élèves,
                                    étudiants et enseignants à trouver rapidement les documents nécessaires à leur réussite.
                                </p>

                            </div>

                            <div class="col-lg-4 text-center order-1 order-lg-2">
                                <img src="{{ asset('templates/publicpages/assets/img/bibliotheque.png') }}"
                                     class="img-fluid rounded shadow"
                                     alt="Bibliothèque en ligne Burkina Faso">
                            </div>

                        </div>

                    </div>

                    {{-- LIBRAIRIE --}}
                    <div class="tab-pane" id="departments-tab-4">

                        <div class="row align-items-center">

                            <div class="col-lg-8 details order-2 order-lg-1">

                                <h3>Librairie scolaire</h3>

                                <p class="fst-italic">
                                    Tout le nécessaire pour accompagner la réussite scolaire.
                                </p>

                                <p>
                                    K'Educ propose une librairie scolaire spécialisée dans la vente
                                    de fournitures scolaires, manuels, cahiers, stylos,
                                    équipements pédagogiques et matériels didactiques.
                                </p>

                                <p>
                                    Parents, élèves et enseignants peuvent trouver rapidement les outils indispensables
                                    pour l'apprentissage et la préparation des examens.
                                </p>

                            </div>

                            <div class="col-lg-4 text-center order-1 order-lg-2">
                                <img src="{{ asset('templates/publicpages/assets/img/librairie.png') }}"
                                     class="img-fluid rounded shadow"
                                     alt="Librairie scolaire Burkina Faso">
                            </div>

                        </div>

                    </div>

                    {{-- FORMATIONS --}}
                    <div class="tab-pane" id="departments-tab-5">

                        <div class="row align-items-center">

                            <div class="col-lg-8 details order-2 order-lg-1">

                                <h3>Formations et conférences éducatives</h3>

                                <p class="fst-italic">
                                    Développer les compétences pour réussir à l'école et dans la vie professionnelle.
                                </p>

                                <p>
                                    K'Educ organise régulièrement des conférences,
                                    ateliers et formations sur la réussite scolaire,
                                    les méthodes de travail, l'orientation académique,
                                    le leadership et le développement personnel.
                                </p>

                                <p>
                                    Ces activités permettent aux élèves, étudiants,
                                    parents et enseignants de mieux préparer leur avenir.
                                </p>

                            </div>

                            <div class="col-lg-4 text-center order-1 order-lg-2">
                                <img src="{{ asset('templates/publicpages/assets/img/conference.png') }}"
                                     class="img-fluid rounded shadow"
                                     alt="Conférences éducatives Burkina Faso">
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</section>