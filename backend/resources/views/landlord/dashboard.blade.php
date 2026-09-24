@extends('landlord.layouts.app')

@section('title', 'Tableau de bord')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold mb-0">Tableau de bord</h1>
    </div>

    <div class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i>
        <strong>Bienvenue, {{ auth('landlord')->user()->nom }}.</strong>
        La gestion des cabinets arrive avec T2.3, le pipeline de création avec T2.4,
        la facturation avec P7 et le journal avec T2.7.
    </div>
@endsection