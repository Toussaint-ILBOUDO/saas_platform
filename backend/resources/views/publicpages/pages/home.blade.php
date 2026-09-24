{{-- ======================================================
    Page : Accueil (index)
    Layout : layouts/public
    Assemblage de toutes les sections de la page d'accueil
====================================================== --}}

@extends('publicpages.layouts.public')

@section('title', 'K\'Educ | Cours d\'appui à domicile et en ligne au Burkina Faso')
@section('meta_description',
'K\'Educ est la plateforme de référence pour les cours d\'appui à domicile et en ligne au Burkina Faso. Enseignants qualifiés, soutien scolaire du primaire au supérieur, bibliothèque numérique, librairie scolaire, préparation aux examens et orientation académique partout au Burkina Faso.')
@section('body_class', 'index-page')

@section('content')

    @include('publicpages.sections.hero')

    @include('publicpages.sections.about')

    @include('publicpages.sections.stats')

    @include('publicpages.sections.services')

    @include('publicpages.sections.zones')

    @include('publicpages.sections.appointment')

    @include('publicpages.sections.departments')

    @include('publicpages.sections.enseignants')

    @include('publicpages.sections.faq')

    @include('publicpages.sections.testimonials')

    @include('publicpages.sections.contact')
    



@endsection

@push('scripts')
@php
    $keduc = config('keduc.cabinet');
    $keducName = $keduc['nom'] ?? 'K\'Educ';
    $keducUrl = url('/');
    $siteJsonLd = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'Organization',
                'name' => $keducName,
                'url' => $keducUrl,
                'email' => $keduc['email'] ?? null,
                'telephone' => $keduc['telephone'] ?? null,
            ],
            [
                '@type' => 'WebSite',
                'name' => $keducName,
                'url' => $keducUrl,
                'inLanguage' => 'fr',
            ],
        ],
    ];
@endphp
<script type="application/ld+json">
{!! json_encode($siteJsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endpush
