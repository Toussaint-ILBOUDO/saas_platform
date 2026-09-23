{{-- ======================================================
    Section : Demande de cours
====================================================== --}}

<section id="appointment" class="appointment section">

    <div class="container section-title" data-aos="fade-up">

        <div class="d-flex justify-content-center mb-3">
            <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center"
                 style="width:70px;height:70px;">
                <i class="bi bi-mortarboard-fill fs-2"></i>
            </div>
        </div>

        <h2>Demander un cours d'appui</h2>

        <p>
            Remplissez le formulaire ci-dessous et notre équipe vous contactera
            pour vous proposer un enseignant adapté aux besoins de votre enfant.
        </p>

    </div>

    <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row justify-content-center">

            <div class="col-lg-10">

                <div class="card border-0 shadow-sm rounded-4">

                    <div class="card-body p-4 p-lg-5">

                        <div class="row text-center mb-4">

                            <div class="col-md-4 mb-3">

                                <i class="bi bi-pencil-square text-success fs-1"></i>

                                <h5 class="mt-2">
                                    1. Remplissez la demande
                                </h5>

                                <small class="text-muted">
                                    Informations sur l'élève et les matières concernées.
                                </small>

                            </div>

                            <div class="col-md-4 mb-3">

                                <i class="bi bi-telephone-fill text-primary fs-1"></i>

                                <h5 class="mt-2">
                                    2. Nous vous contactons
                                </h5>

                                <small class="text-muted">
                                    Validation des besoins et définition du programme.
                                </small>

                            </div>

                            <div class="col-md-4 mb-3">

                                <i class="bi bi-person-workspace text-warning fs-1"></i>

                                <h5 class="mt-2">
                                    3. Affectation d'un enseignant
                                </h5>

                                <small class="text-muted">
                                    Mise en relation rapide avec un enseignant qualifié.
                                </small>

                            </div>

                        </div>

                        <div class="text-center mt-4">

                            <a href="{{ route('demande-cours.create') }}"
                               class="btn btn-success btn-lg px-5">

                                <i class="bi bi-mortarboard-fill me-2"></i>

                                Faire une demande de cours

                            </a>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</section>