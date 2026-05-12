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

    <!-- Tableau d'Honneur Annuel avec sélection des mois -->
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
                        <label class="form-label">Mois / Périodes à inclure <span class="text-danger">*</span></label>
                        <div class="alert alert-info">
                            <i class="ti ti-info-circle"></i> 
                            Sélectionnez les mois à inclure dans le calcul de la moyenne annuelle
                        </div>
                        <select name="mois_ids[]" id="mois_annuel_select2" class="form-select select2" multiple="multiple" required>
                            @foreach($moisScolaire as $mois)
                                <option value="{{ $mois->id }}">{{ $mois->nom }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Vous pouvez sélectionner plusieurs mois</small>
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

    <!-- Certificat de Major avec sélection multiple des classes et des mois -->
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
                        <label class="form-label">Classes à inclure <span class="text-danger">*</span></label>
                        <div class="alert alert-info">
                            <i class="ti ti-info-circle"></i> 
                            Sélectionnez les classes pour lesquelles vous voulez générer le certificat
                        </div>
                        <select name="classe_ids[]" id="classe_major_select2" class="form-select select2" multiple="multiple" required>
                            @foreach($classes as $classe)
                                <option value="{{ $classe->id }}">{{ $classe->nom }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Vous pouvez sélectionner plusieurs classes</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Mois / Périodes à inclure <span class="text-danger">*</span></label>
                        <div class="alert alert-info">
                            <i class="ti ti-info-circle"></i> 
                            Sélectionnez les mois à inclure dans le calcul de la moyenne
                        </div>
                        <select name="mois_ids[]" id="mois_major_select2" class="form-select select2" multiple="multiple" required>
                            @foreach($moisScolaire as $mois)
                                <option value="{{ $mois->id }}">{{ $mois->nom }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Vous pouvez sélectionner plusieurs mois</small>
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
<!-- jQuery (si pas déjà inclus) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Select2 CSS et JS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

<script>
$(document).ready(function() {
    // Configuration commune pour Select2
    var select2Config = {
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Sélectionnez une ou plusieurs options',
        allowClear: true,
        language: {
            noResults: function() {
                return "Aucun résultat trouvé";
            }
        }
    };
    
    // Initialiser Select2 pour le tableau d'honneur annuel
    if ($('#mois_annuel_select2').length) {
        $('#mois_annuel_select2').select2(select2Config);
    }
    
    // Initialiser Select2 pour les classes du major
    if ($('#classe_major_select2').length) {
        $('#classe_major_select2').select2(select2Config);
    }
    
    // Initialiser Select2 pour les mois du major
    if ($('#mois_major_select2').length) {
        $('#mois_major_select2').select2(select2Config);
    }
    
    // Validation avant soumission pour le formulaire annuel du tableau d'honneur
    $('form[action*="generer-annuel"]').on('submit', function(e) {
        var moisCount = $('#mois_annuel_select2').val() ? $('#mois_annuel_select2').val().length : 0;
        
        if (moisCount === 0) {
            e.preventDefault();
            var errorMsg = "Veuillez sélectionner au moins un mois pour le tableau d'honneur annuel";
            if (typeof toastr !== 'undefined') {
                toastr.error(errorMsg);
            } else {
                alert(errorMsg);
            }
            return false;
        }
        return true;
    });
    
    // Validation avant soumission pour le formulaire major
    $('form[action*="generer-major"]').on('submit', function(e) {
        // Vérifier qu'au moins une classe est sélectionnée
        var classeCount = $('#classe_major_select2').val() ? $('#classe_major_select2').val().length : 0;
        
        if (classeCount === 0) {
            e.preventDefault();
            var errorMsg = "Veuillez sélectionner au moins une classe pour le certificat du major";
            if (typeof toastr !== 'undefined') {
                toastr.error(errorMsg);
            } else {
                alert(errorMsg);
            }
            return false;
        }
        
        // Vérifier qu'au moins un mois est sélectionné
        var moisCount = $('#mois_major_select2').val() ? $('#mois_major_select2').val().length : 0;
        
        if (moisCount === 0) {
            e.preventDefault();
            var errorMsg = "Veuillez sélectionner au moins un mois pour le certificat du major";
            if (typeof toastr !== 'undefined') {
                toastr.error(errorMsg);
            } else {
                alert(errorMsg);
            }
            return false;
        }
        
        if (typeof toastr !== 'undefined') {
            toastr.success("Génération des certificats en cours...");
        }
        
        return true;
    });
});
</script>
@endpush