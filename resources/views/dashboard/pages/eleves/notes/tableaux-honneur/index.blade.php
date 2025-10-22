@extends('dashboard.layouts.master')

@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
    <div class="my-auto mb-2">
        <h3 class="page-title mb-1">Tableaux d'Honneur</h3>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Tableau de bord</a></li>
                <li class="breadcrumb-item active" aria-current="page">Tableaux d'Honneur</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <!-- Tableau d'Honneur Mensuel -->
    <div class="col-xl-4 col-lg-6 col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="ti ti-trophy text-warning me-2"></i>
                    Tableau d'Honneur Mensuel
                </h5>
            </div>
            <div class="card-body">
                <form action="{{ route('tableaux-honneur.generer-mensuel') }}" method="GET" target="_blank">
                    <div class="mb-3">
                        <label class="form-label">Classe</label>
                        <select name="classe_id" class="form-select">
                            <option value="">Toutes les classes (Général)</option>
                            @foreach($classes as $classe)
                                <option value="{{ $classe->id }}">{{ $classe->nom }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mois</label>
                        <select name="mois_id" class="form-select" required>
                            <option value="">Sélectionner un mois</option>
                            @foreach($moisScolaire as $mois)
                                <option value="{{ $mois->id }}">{{ $mois->nom }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nombre d'élèves à honorer</label>
                        <select name="nombre_eleves" class="form-select" required>
                            @for($i = 1; $i <= 10; $i++)
                                <option value="{{ $i }}" {{ $i == 3 ? 'selected' : '' }}>{{ $i }} élève(s)</option>
                            @endfor
                        </select>
                    </div>
                    <button type="submit" class="btn btn-warning w-100">
                        <i class="ti ti-download me-2"></i>Générer le Tableau
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Tableau d'Honneur Annuel -->
    <div class="col-xl-4 col-lg-6 col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="ti ti-crown text-success me-2"></i>
                    Tableau d'Honneur Annuel
                </h5>
            </div>
            <div class="card-body">
                <form action="{{ route('tableaux-honneur.generer-annuel') }}" method="GET" target="_blank">
                    <div class="mb-3">
                        <label class="form-label">Classe</label>
                        <select name="classe_id" class="form-select">
                            <option value="">Toutes les classes (Général)</option>
                            @foreach($classes as $classe)
                                <option value="{{ $classe->id }}">{{ $classe->nom }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nombre d'élèves à honorer</label>
                        <select name="nombre_eleves" class="form-select" required>
                            @for($i = 1; $i <= 10; $i++)
                                <option value="{{ $i }}" {{ $i == 5 ? 'selected' : '' }}>{{ $i }} élève(s)</option>
                            @endfor
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success w-100">
                        <i class="ti ti-download me-2"></i>Générer le Tableau
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Certificat de Major -->
    <div class="col-xl-4 col-lg-6 col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="ti ti-medal text-primary me-2"></i>
                    Certificat de Major
                </h5>
            </div>
            <div class="card-body">
                <form action="{{ route('tableaux-honneur.generer-major') }}" method="GET" target="_blank">
                    <div class="mb-3">
                        <label class="form-label">Type de Major</label>
                        <select name="type" class="form-select" required>
                            <option value="classe">Major de Classe</option>
                            <option value="general">Major Général</option>
                        </select>
                    </div>
                    <div class="mb-3" id="classe-field">
                        <label class="form-label">Classe</label>
                        <select name="classe_id" class="form-select">
                            <option value="">Sélectionner une classe</option>
                            @foreach($classes as $classe)
                                <option value="{{ $classe->id }}">{{ $classe->nom }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Période</label>
                        <select name="periode" class="form-select" required>
                            <option value="mois">Mensuel</option>
                            <option value="annee">Annuel</option>
                        </select>
                    </div>
                    <div class="mb-3" id="mois-field">
                        <label class="form-label">Mois</label>
                        <select name="mois_id" class="form-select">
                            <option value="">Sélectionner un mois</option>
                            @foreach($moisScolaire as $mois)
                                <option value="{{ $mois->id }}">{{ $mois->nom }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="ti ti-certificate me-2"></i>Générer le Certificat
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Information -->
<div class="card mt-4">
    <div class="card-body">
        <div class="alert alert-info mb-0">
            <h6 class="alert-heading"><i class="ti ti-info-circle me-2"></i>Information</h6>
            <p class="mb-0">
                Les tableaux d'honneur et certificats sont générés automatiquement en fonction des moyennes calculées.
                Chaque élève honoré recevra un certificat individuel sur une page séparée.
            </p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion de l'affichage conditionnel des champs
    const typeSelect = document.querySelector('select[name="type"]');
    const classeField = document.getElementById('classe-field');
    const periodeSelect = document.querySelector('select[name="periode"]');
    const moisField = document.getElementById('mois-field');

    function toggleFields() {
        // Gestion du champ classe
        if (typeSelect.value === 'general') {
            classeField.style.display = 'none';
            classeField.querySelector('select').required = false;
        } else {
            classeField.style.display = 'block';
            classeField.querySelector('select').required = true;
        }

        // Gestion du champ mois
        if (periodeSelect.value === 'annee') {
            moisField.style.display = 'none';
            moisField.querySelector('select').required = false;
        } else {
            moisField.style.display = 'block';
            moisField.querySelector('select').required = true;
        }
    }

    typeSelect.addEventListener('change', toggleFields);
    periodeSelect.addEventListener('change', toggleFields);
    
    // Initialisation
    toggleFields();
});
</script>
@endpush