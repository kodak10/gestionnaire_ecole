@extends('dashboard.layouts.master')

@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
    <div class="my-auto mb-2">
        <h3 class="page-title mb-1">Fiches de Présence</h3>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Tableau de bord</a></li>
                <li class="breadcrumb-item active" aria-current="page">Fiches Présence</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Cards des classes -->
<div class="row">
    @foreach($classes as $classe)
    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="flex-grow-1">
                        <h5 class="card-title mb-1">{{ $classe->nom }}</h5>
                        <p class="text-muted mb-0">{{ $classe->niveau->nom }}</p>
                        <small class="text-muted">
                            @php
                                $elevesCount = \App\Models\Inscription::where('classe_id', $classe->id)
                                    ->where('statut', 'active')
                                    ->count();
                            @endphp
                            {{ $elevesCount }} élève(s)
                        </small>
                    </div>
                    <div class="flex-shrink-0">
                        <span class="badge bg-primary rounded-pill">{{ $classe->niveau->code }}</span>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="{{ route('documents.generer-fiche-presence', $classe) }}" 
                       class="btn btn-outline-primary btn-sm w-100" target="_blank">
                        <i class="ti ti-calendar me-1"></i>Générer Fiche
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<!-- Information -->
<div class="card mt-4">
    <div class="card-body">
        <div class="alert alert-info mb-0">
            <h6 class="alert-heading"><i class="ti ti-info-circle me-2"></i>Information</h6>
            <p class="mb-0">Les fiches de présence sont générées par classe. Chaque fiche contient la liste des élèves avec des cases à cocher pour suivre la présence quotidienne.</p>
        </div>
    </div>
</div>
@endsection