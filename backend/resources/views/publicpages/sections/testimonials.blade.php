{{-- ======================================================
    Section : Témoignages (Accueil) — contenu dynamique
    Source : HomeController → $temoignages (top 3, score)
====================================================== --}}

<section id="testimonials" class="testimonials section">

    <div class="container">

        <div class="row align-items-center">

            {{-- Texte intro --}}
            <div class="col-lg-5 info" data-aos="fade-up" data-aos-delay="100">
                <h3>Témoignages</h3>
                <p>
                    Découvrez ce que les parents, enseignants et élèves disent de K'Educ :
                    des progrès réels, un accompagnement humain et des résultats concrets.
                </p>
                <a href="{{ route('temoignages.index') }}" class="btn btn-outline-primary mt-2">
                    Voir tous les témoignages <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>

            {{-- Slider --}}
            <div class="col-lg-7" data-aos="fade-up" data-aos-delay="200">

                <div class="swiper init-swiper">
                    <script type="application/json" class="swiper-config">
                        {
                            "loop": true,
                            "speed": 600,
                            "autoplay": { "delay": 5000 },
                            "slidesPerView": "auto",
                            "pagination": {
                                "el": ".swiper-pagination",
                                "type": "bullets",
                                "clickable": true
                            }
                        }
                    </script>

                    <div class="swiper-wrapper">

                        @forelse($temoignages as $temoignage)
                            <div class="swiper-slide">
                                <div class="testimonial-item">
                                    <div class="d-flex">
                                        <div class="testimonial-img flex-shrink-0 home-temo-avatar">
                                            <i class="bi bi-quote"></i>
                                        </div>
                                        <div>
                                            <h3>{{ $temoignage->auteur_display }}</h3>
                                            <h4>
                                                @if(! $temoignage->anonyme)
                                                    {{ $temoignage->role_label }}
                                                @else
                                                    Membre de la communauté
                                                @endif
                                            </h4>
                                            <div class="stars">
                                                <span class="badge bg-warning-subtle text-warning">
                                                    <i class="bi bi-stars me-1"></i>{{ $temoignage->score }} pts
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <p>
                                        <i class="bi bi-quote quote-icon-left"></i>
                                        <span>{{ $temoignage->contenu }}</span>
                                        <i class="bi bi-quote quote-icon-right"></i>
                                    </p>
                                    <a href="{{ route('temoignages.show', $temoignage->slug) }}"
                                       class="small fw-semibold text-decoration-none">
                                        Lire ce témoignage <i class="bi bi-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        @empty
                            <div class="swiper-slide">
                                <div class="testimonial-item">
                                    <p class="mb-0">
                                        <i class="bi bi-quote quote-icon-left"></i>
                                        <span>Les témoignages de nos familles arrivent bientôt. Partagez dès maintenant votre expérience !</span>
                                        <i class="bi bi-quote quote-icon-right"></i>
                                    </p>
                                    <a href="{{ route('temoignages.index') }}"
                                       class="small fw-semibold text-decoration-none">
                                        En savoir plus <i class="bi bi-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        @endforelse

                    </div>
                    <div class="swiper-pagination"></div>
                </div>

            </div>

        </div>

    </div>

</section>

@push('styles')
<style>
    .home-temo-avatar {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: linear-gradient(135deg, #2563eb, #1e40af);
        color: #fff;
        font-size: 1.4rem;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
    }
    .testimonials .testimonial-item {
        height: 100%;
    }
</style>
@endpush
