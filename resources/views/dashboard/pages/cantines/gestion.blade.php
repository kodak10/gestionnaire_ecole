@extends('dashboard.layouts.master')
@section('content')
<!-- Page Header -->
<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
    <div class="my-auto">
        <h3 class="mb-1">Gestion des Cantine par Mois</h3>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Tableau de Bord</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('cantine.index') }}">Cantine</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Gestion par Mois</li>
            </ol>
        </nav>
    </div>
</div>
<!-- /Page Header -->

<div class="mb-5">
    @if ($errors->any())
        <div class="alert alert-danger mt-4 w-100">
            <h5 class="mb-2">Erreurs de validation :</h5>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger mt-4 w-100">
            {{ session('error') }}
        </div>
    @endif

    @if (session('success'))
        <div class="alert alert-success mt-4 w-100">
            {{ session('success') }}
        </div>
    @endif
</div>

<div class="row">
    <!-- Colonne de gauche - Sélection -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-light">
                <h4 class="text-dark">Sélection de l'Élève</h4>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Classe <span class="text-danger">*</span></label>
                    <select class="form-select select2" id="classe_id" name="classe_id" required>
                        <option value="">Sélectionner une classe</option>
                        @foreach($classes as $classe)
                            <option value="{{ $classe->id }}">{{ $classe->nom }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Élève <span class="text-danger">*</span></label>
                    <select class="form-select select2" id="inscription_id" name="inscription_id" required disabled>
                        <option value="">Sélectionner un élève</option>
                    </select>
                </div>

                <button class="btn btn-primary w-100" id="load-btn" disabled>
                    <i class="ti ti-search me-2"></i>Charger les données
                </button>
            </div>
        </div>

        <!-- Carte Récapitulatif -->
        <div class="card mt-3">
            <div class="card-header bg-light">
                <h4 class="text-dark">Récapitulatif Cantine</h4>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Total à payer</label>
                    <input type="text" class="form-control fw-bold text-success" id="total_a_payer" readonly value="0 FCFA">
                </div>
                <div class="mb-3">
                    <label class="form-label">Déjà payé</label>
                    <input type="text" class="form-control" id="deja_paye" readonly value="0 FCFA">
                </div>
                <div class="mb-3">
                    <label class="form-label">Reste à payer</label>
                    <input type="text" class="form-control fw-bold text-danger" id="reste_a_payer" readonly value="0 FCFA">
                </div>
            </div>
        </div>
    </div>

    <!-- Colonne de droite - Gestion des mois -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-light">
                <h4 class="text-dark">Configuration des Mois de Cantine</h4>
            </div>
            <div class="card-body">
                <div id="eleve-info" class="alert alert-info d-none">
                    <h5 class="mb-1" id="eleve-nom"></h5>
                    <p class="mb-0" id="eleve-details"></p>
                </div>

                <!-- Configuration des mois -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">Configuration des mois :</h6>
                        <div>
                            <button class="btn btn-outline-primary btn-sm" id="select-all-btn">
                                <i class="ti ti-checkbox me-1"></i>Tout cocher
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" id="deselect-all-btn">
                                <i class="ti ti-square me-1"></i>Tout décocher
                            </button>
                        </div>
                    </div>
                    
                    <div id="mois-container" class="row">
                        <!-- Les mois seront chargés ici dynamiquement -->
                    </div>
                </div>

                <!-- Bouton de sauvegarde -->
                <div class="border-top pt-3">
                    <div class="text-end">
                        <button type="button" class="btn btn-success" id="save-config-btn" disabled>
                            <i class="ti ti-device-floppy me-2"></i>Sauvegarder la Configuration
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<style>
.select2-container--default .select2-selection--single {
    height: 38px;
    padding: 5px 10px;
}

.mois-item {
    margin-bottom: 15px;
    padding: 15px;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    background: #f8f9fa;
}

.mois-item.paye {
    opacity: 0.6;
    background: #e9ecef;
}

.mois-header {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
}

.mois-title {
    font-weight: 600;
    margin-left: 10px;
}

.montant-input {
    max-width: 150px;
}

.mois-item.paye .form-check-input,
.mois-item.paye .montant-input {
    opacity: 0.6;
    pointer-events: none;
}
</style>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
toastr.options = {
    "closeButton": true,
    "progressBar": true,
    "positionClass": "toast-top-right",
    "timeOut": "5000"
};

$(document).ready(function() {
    // Initialisation des select2
    $('.select2').select2({
        placeholder: 'Sélectionner une option',
        allowClear: true
    });

    let currentEleveId = null;
    let configurations = {};

    // Charger les élèves quand une classe est sélectionnée
    $('#classe_id').change(function() {
        const classeId = $(this).val();
        $('#inscription_id').empty().append('<option value="">Sélectionner un élève</option>');

        if (classeId) {
            $('#inscription_id').prop('disabled', false);

            $.ajax({
                url: '{{ route("cantine.eleves_by_classe_gestion") }}',
                type: 'GET',
                data: { classe_id: classeId },
                success: function(response) {
                    if (response.length > 0) {
                        $.each(response, function(index, eleve) {
                            $('#inscription_id').append(`<option value="${eleve.id}">${eleve.nom_complet}</option>`);
                        });
                    } else {
                        $('#inscription_id').append('<option value="">Aucun élève avec cantine dans cette classe</option>');
                    }
                },
                error: function() {
                    toastr.error('Erreur lors du chargement des élèves');
                }
            });
        } else {
            $('#inscription_id').prop('disabled', true);
        }
        $('#load-btn').prop('disabled', true);
    });

    // Activer le bouton de chargement quand un élève est sélectionné
    $('#inscription_id').change(function() {
        $('#load-btn').prop('disabled', !$(this).val());
    });

    // Charger les données de l'élève
    $('#load-btn').click(function() {
        const inscriptionId = $('#inscription_id').val();

        if (inscriptionId) {
            currentEleveId = inscriptionId;
            loadEleveCantineData(inscriptionId);
        }
    });

    // Charger les données d'un élève pour la cantine
    function loadEleveCantineData(inscriptionId) {
        $.ajax({
            url: '{{ route("cantine.eleve_mois_data") }}',
            type: 'GET',
            data: { inscription_id: inscriptionId },
            beforeSend: function() {
                $('#mois-container').html('<div class="col-12 text-center">Chargement en cours...</div>');
            },
            success: function(response) {
                if (response.success) {
                    updateEleveInfo(response);
                    updateMoisConfiguration(response);
                    updateSummary(response);
                } else {
                    toastr.error(response.message);
                    resetInterface();
                }
            },
            error: function() {
                toastr.error('Erreur lors du chargement des données');
                resetInterface();
            }
        });
    }

    function updateEleveInfo(data) {
        $('#eleve-nom').text(data.eleve.nom_complet);
        $('#eleve-details').text(`Matricule: ${data.eleve.matricule} | Classe: ${data.eleve.classe}`);
        $('#eleve-info').removeClass('d-none');
    }

    function updateMoisConfiguration(data) {
        configurations = {};
        let html = '';
        
        data.mois.forEach(function(mois) {
            const isPaye = mois.est_paye;
            const peutModifier = mois.peut_modifier;
            const montantDisplay = isPaye ? mois.montant_personnalise : mois.montant_personnalise;
            
            html += `
            <div class="col-md-6">
                <div class="mois-item ${isPaye ? 'paye' : ''}">
                    <div class="mois-header">
                        <input class="form-check-input mois-checkbox" type="checkbox" 
                               data-mois-id="${mois.mois_id}"
                               ${mois.est_coche ? 'checked' : ''}
                               ${!peutModifier ? 'disabled' : ''}>
                        <span class="mois-title">${mois.mois_nom}</span>
                    </div>
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <label class="form-label small">Tarif de base:</label>
                            <input type="text" class="form-control form-control-sm" 
                                   value="${formatMoney(mois.montant_base)}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Montant à payer:</label>
                            <input type="number" class="form-control form-control-sm montant-input" 
                                   data-mois-id="${mois.mois_id}"
                                   value="${montantDisplay}" 
                                   min="0" 
                                   ${!peutModifier ? 'readonly' : ''}>
                        </div>
                    </div>
                    ${isPaye ? '<small class="text-success mt-2 d-block"><i class="ti ti-check"></i> Déjà payé</small>' : ''}
                </div>
            </div>
            `;

            // Stocker la configuration
            configurations[mois.mois_id] = {
                est_coche: mois.est_coche,
                montant: mois.montant_personnalise,
                peut_modifier: peutModifier
            };
        });
        
        $('#mois-container').html(html);
        
        // Activer les événements
        $('.mois-checkbox').change(function() {
            const moisId = $(this).data('mois-id');
            configurations[moisId].est_coche = $(this).is(':checked');
            updateTotal();
            $('#save-config-btn').prop('disabled', false);
        });

        $('.montant-input').on('input', function() {
            const moisId = $(this).data('mois-id');
            const nouvelleValeur = parseFloat($(this).val()) || 0;
            configurations[moisId].montant = nouvelleValeur;
            updateTotal();
            $('#save-config-btn').prop('disabled', false);
        });

        $('#save-config-btn').prop('disabled', true);
    }

    function updateTotal() {
        let total = 0;
        
        Object.keys(configurations).forEach(moisId => {
            if (configurations[moisId].est_coche && configurations[moisId].peut_modifier) {
                total += configurations[moisId].montant;
            }
        });
        
        $('#total_a_payer').val(formatMoney(total));
    }

    function updateSummary(data) {
        $('#total_a_payer').val(formatMoney(data.total_montant));
        $('#deja_paye').val(formatMoney(data.total_paye));
        $('#reste_a_payer').val(formatMoney(data.reste_a_payer));
    }

    // Tout sélectionner
    $('#select-all-btn').click(function(e) {
        e.preventDefault();
        $('.mois-checkbox:not(:disabled)').prop('checked', true).trigger('change');
    });

    // Tout désélectionner
    $('#deselect-all-btn').click(function(e) {
        e.preventDefault();
        $('.mois-checkbox:not(:disabled)').prop('checked', false).trigger('change');
    });

    // Sauvegarder la configuration
    $('#save-config-btn').click(function() {
        if (!currentEleveId) {
            toastr.error('Veuillez sélectionner un élève');
            return;
        }

        const configs = [];
        Object.keys(configurations).forEach(moisId => {
            configs.push({
                mois_id: parseInt(moisId),
                montant: parseFloat(configurations[moisId].montant) || 0,
                est_coche: configurations[moisId].est_coche === true || configurations[moisId].est_coche === 'true' || configurations[moisId].est_coche === 1
            });
        });

        $.ajax({
            url: '{{ route("cantine.save_configuration") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                inscription_id: currentEleveId,
                configurations: configs
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#save-config-btn').prop('disabled', true);
                    // Recharger les données pour mettre à jour les totaux
                    loadEleveCantineData(currentEleveId);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    $.each(xhr.responseJSON.errors, function(key, value) {
                        toastr.error(value[0]);
                    });
                } else {
                    toastr.error('Une erreur est survenue');
                }
            }
        });
    });

    function formatMoney(amount) {
        return new Intl.NumberFormat('fr-FR', { 
            style: 'currency', 
            currency: 'XOF', 
            minimumFractionDigits: 0 
        }).format(amount);
    }

    function resetInterface() {
        $('#eleve-info').addClass('d-none');
        $('#mois-container').html('');
        $('#save-config-btn').prop('disabled', true);
        $('#total_a_payer').val('0 FCFA');
        $('#deja_paye').val('0 FCFA');
        $('#reste_a_payer').val('0 FCFA');
    }
});
</script>
@endsection