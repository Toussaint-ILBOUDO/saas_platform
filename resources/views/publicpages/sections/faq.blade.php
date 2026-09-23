{{-- ======================================================
    Section : FAQ (dynamique)
    Fichier : sections/home/faq.blade.php
    Source : app/Modules/Communication + $faqSections
====================================================== --}}

<section id="faq" class="faq section light-background">

    <div class="container section-title" data-aos="fade-up">
        <h2>Questions fréquentes</h2>
        <p>Tout ce qu'il faut savoir sur nos services, l'inscription et le déroulement des cours</p>
    </div>

    <div class="container">

        <div class="row justify-content-center">

            <div class="col-lg-10" data-aos="fade-up" data-aos-delay="100">

                @forelse($faqSections as $section)

                    <div class="faq-group">

                        <h3 class="faq-group-title">
                            <i class="bi bi-tag me-1"></i>
                            {{ $section->title }}
                        </h3>

                        @if($section->description)
                            <p class="faq-group-description text-muted">{{ $section->description }}</p>
                        @endif

                        <div class="faq-container mb-4">

                            @forelse($section->questions as $question)

                                <div class="faq-item {{ $loop->first && $loop->parent->first ? 'faq-active' : '' }}">
                                    <h3>
                                        <span class="num">{{ $loop->parent->iteration }}.{{ $loop->iteration }}</span>
                                        {{ $question->question }}
                                    </h3>
                                    <div class="faq-content">
                                        <p>{{ $question->answer }}</p>
                                    </div>
                                    <i class="faq-toggle bi bi-chevron-right"></i>
                                </div>

                            @empty

                                <p class="text-muted mb-0">Aucune question dans cette rubrique pour le moment.</p>

                            @endforelse

                        </div>

                    </div>

                @empty

                    <div class="text-center text-muted py-4">
                        <i class="bi bi-chat-square-text display-5"></i>
                        <p class="mt-2 mb-0">Les réponses à vos questions arrivent bientôt.</p>
                        <p>En attendant, n'hésitez pas à nous contacter.</p>
                    </div>

                @endforelse

            </div>

        </div>

    </div>

</section>

@php
    $faqJsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => collect($faqSections ?? [])
            ->flatMap(fn ($section) => $section->questions)
            ->map(fn ($question) => [
                '@type' => 'Question',
                'name' => $question->question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $question->answer,
                ],
            ])
            ->values()
            ->all(),
    ];
@endphp

@push('scripts')
@if(!empty($faqJsonLd['mainEntity']))
<script type="application/ld+json">
{!! json_encode($faqJsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endif
@endpush
