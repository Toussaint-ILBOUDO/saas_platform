@extends('landlord.layouts.app')

@section('title', $titre)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold mb-0">{{ $titre }}</h1>
    </div>

    <div class="alert alert-secondary">
        <i class="bi bi-tools me-2"></i>
        {{ $description }}
    </div>
@endsection