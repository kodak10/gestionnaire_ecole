@extends('dashboard.layouts.master')
@section('content')
<!-- Page Header -->
<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
    <div class="my-auto">
        <h3 class="mb-1">Paiement Cantine par Mois</h3>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Tableau de Bord</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('cantine.index') }}">Cantine</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Paiement par Mois</li>
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
                    <label class="form-label">Total configuré</label>
                    <input type="text" class="form-control" id="total_configure" readonly value="0 FCFA">
                </div>
                <div class="mb-3">
                    <label class="form-label">Déjà payé</label>
                    <input type="text" class="form-control" id="deja_paye" readonly value="0 FCFA">
                </div>
                <div class="mb-3">
                    <label class="form-label">Reste à payer</label>
                    <input type="text" class="form-control fw-bold text-danger" id="reste_a_payer" readonly value="0 FCFA">
                </div>
                <div class="mb-3">
                    <label class="form-label">Total sélectionné</label>
                    <input type="text" class="form-control fw-bold text-success" id="total_selectionne" readonly value="0 FCFA">
                </div>
            </div>
        </div>
    </div>

    <!-- Colonne de droite - Paiement des mois -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-light">
                <h4 class="text-dark">Paiement des Mois de Cantine</h4>
            </div>
            <div class="card-body">
                <div id="eleve-info" class="alert alert-info d-none">
                    <h5 class="mb-1" id="eleve-nom"></h5>
                    <p class="mb-0" id="eleve-details"></p>
                </div>

                <!-- Sélection des mois à payer -->
                <div class="mb-4" id="mois-section" style="display: none;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">Sélectionnez les mois à payer :</h6>
                        <div>
                            <button class="btn btn-outline-primary btn-sm" id="select-all-btn">
                                <i class="ti ti-checkbox me-1"></i>Tout sélectionner
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" id="deselect-all-btn">
                                <i class="ti ti-square me-1"></i>Tout désélectionner
                            </button>
                        </div>
                    </div>
                    
                    <div id="mois-container" class="row">
                        <!-- Les mois seront chargés ici dynamiquement -->
                    </div>

                    <div class="mt-4 border-top pt-3">
                        <h6 class="mb-3">Détails du Paiement</h6>
                        <form id="paiement-form">
                            @csrf
                            <input type="hidden" id="paiement_inscription_id" name="inscription_id">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Montant total sélectionné</label>
                                        <input type="text" class="form-control fw-bold text-success" id="montant_total" readonly value="0 FCFA">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Montant à encaisser <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="montant_encaisse" name="montant_encaisse" min="0" value="0" required>
                                        <small class="text-muted">Le montant effectivement perçu</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Mode de Paiement <span class="text-danger">*</span></label>
                                        <select class="form-select" id="mode_paiement" name="mode_paiement" required>
                                            <option value="especes">Espèces</option>
                                            <option value="cheque">Chèque</option>
                                            <option value="virement">Virement</option>
                                            <option value="mobile_money">Mobile Money</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Date de paiement <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="date_paiement" name="date_paiement" value="{{ date('Y-m-d') }}" required>
                                    </div>
                                </div>
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary" id="submit-paiement" disabled>
                                    <i class="ti ti-check me-2"></i>Enregistrer Paiement
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div id="aucun-mois-message" class="text-center py-4" style="display: none;">
                    <div class="alert alert-warning">
                        <i class="ti ti-info-circle me-2"></i>
                        Aucun mois en attente de paiement pour cet élève.
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
    transition: all 0.3s ease;
}

.mois-item.selectionne {
    border-color: #007bff;
    background: #e7f3ff;
}

.mois-header {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
}

.mois-title {
    font-weight: 600;
    margin-left: 10px;
    flex-grow: 1;
}

.montant-display {
    font-weight: 600;
    color: #28a745;
}

#montant_total {
    font-size: 1.1em;
    font-weight: bold;
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
    let moisSelectionnes = [];
    let totalResteAPayer = 0;

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
            $('#paiement_inscription_id').val(inscriptionId);
            loadMoisAPayer(inscriptionId);
        }
    });

    // Charger les mois à payer pour l'élève
    function loadMoisAPayer(inscriptionId) {
        $.ajax({
            url: '{{ route("cantine.mois_a_payer") }}',
            type: 'GET',
            data: { inscription_id: inscriptionId },
            beforeSend: function() {
                $('#mois-container').html('<div class="col-12 text-center">Chargement en cours...</div>');
            },
            success: function(response) {
                if (response.success) {
                    updateEleveInfo(response);
                    updateMoisSelection(response);
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

    function updateMoisSelection(data) {
        moisSelectionnes = [];
        let html = '';
        
        if (data.mois_a_payer.length > 0) {
            data.mois_a_payer.forEach(function(mois) {
                html += `
                <div class="col-md-6">
                    <div class="mois-item" data-mois-id="${mois.mois_id}">
                        <div class="mois-header">
                            <input class="form-check-input mois-checkbox" type="checkbox" 
                                   data-mois-id="${mois.mois_id}"
                                   data-montant="${mois.montant}">
                            <span class="mois-title">${mois.mois_nom}</span>
                            <span class="montant-display">${formatMoney(mois.montant)}</span>
                        </div>
                    </div>
                </div>
                `;
            });
            
            $('#mois-container').html(html);
            $('#mois-section').show();
            $('#aucun-mois-message').hide();
            
            // Activer les événements sur les checkboxes
            // Dans la fonction updateMoisSelection, modifiez la partie des événements
$('.mois-checkbox').change(function() {
    const moisId = $(this).data('mois-id');
    let montant = $(this).data('montant');
    
    // S'assurer que le montant est un nombre
    if (typeof montant === 'string') {
        montant = parseFloat(montant.replace(/[^\d.]/g, '')) || 0;
    }
    
    const isChecked = $(this).is(':checked');
    
    if (isChecked) {
        moisSelectionnes.push({
            mois_id: moisId,
            montant: montant // Maintenant c'est un nombre
        });
        $(this).closest('.mois-item').addClass('selectionne');
    } else {
        moisSelectionnes = moisSelectionnes.filter(m => m.mois_id !== moisId);
        $(this).closest('.mois-item').removeClass('selectionne');
    }
    
    updateTotalSelectionne();
});
            
        } else {
            $('#mois-container').html('');
            $('#mois-section').hide();
            $('#aucun-mois-message').show();
        }
    }

    function updateTotalSelectionne() {
    console.log('moisSelectionnes avant calcul:', moisSelectionnes);
    
    let total = 0;
    moisSelectionnes.forEach((mois, index) => {
        let montant = mois.montant;
        
        // Debug
        console.log(`Mois ${index}:`, {
            valeur_originale: montant,
            type: typeof montant
        });
        
        // Conversion robuste
        if (typeof montant === 'string') {
            // Remplacer les virgules par des points si nécessaire
            montant = montant.replace(',', '.');
            // Supprimer tous les caractères non numériques sauf le point
            montant = montant.replace(/[^\d.]/g, '');
        }
        
        const montantNumber = parseFloat(montant) || 0;
        console.log(`Mois ${index} converti:`, montantNumber);
        
        total += montantNumber;
    });
    
    console.log('Total calculé:', total);
    
    $('#montant_total').val(formatMoney(total));
    $('#montant_encaisse').val(total).attr('max', total);
    $('#total_selectionne').val(formatMoney(total));
    
    $('#submit-paiement').prop('disabled', moisSelectionnes.length === 0);
}

    function updateSummary(data) {
        $('#total_configure').val(formatMoney(data.total_configure));
        $('#deja_paye').val(formatMoney(data.total_paye));
        $('#reste_a_payer').val(formatMoney(data.reste_a_payer));
        totalResteAPayer = data.reste_a_payer;
    }

    // Tout sélectionner
    $('#select-all-btn').click(function(e) {
        e.preventDefault();
        $('.mois-checkbox').prop('checked', true).trigger('change');
    });

    // Tout désélectionner
    $('#deselect-all-btn').click(function(e) {
        e.preventDefault();
        $('.mois-checkbox').prop('checked', false).trigger('change');
    });

    // Validation du montant encaissé
    // Validation du montant encaissé
$('#montant_encaisse').on('input', function() {
    const montantEncaisse = parseFloat($(this).val()) || 0;
    const totalSelectionne = moisSelectionnes.reduce((sum, mois) => {
        return sum + (parseFloat(mois.montant) || 0);
    }, 0);
    
    if (montantEncaisse > totalSelectionne) {
        toastr.error('Le montant encaissé ne peut pas dépasser le total sélectionné');
        $(this).val(totalSelectionne);
    }
});

    // Soumission du formulaire de paiement
    // Soumission du formulaire de paiement (méthode JSON)
$('#paiement-form').submit(function(e) {
    e.preventDefault();
    if (!currentEleveId) { 
        toastr.error('Veuillez sélectionner un élève'); 
        return; 
    }
    
    if (moisSelectionnes.length === 0) {
        toastr.error('Veuillez sélectionner au moins un mois');
        return;
    }

    const montantEncaisse = parseFloat($('#montant_encaisse').val()) || 0;
    if (montantEncaisse === 0) {
        toastr.error('Veuillez saisir un montant à encaisser');
        return;
    }

    const data = {
        _token: '{{ csrf_token() }}',
        inscription_id: currentEleveId,
        montant_encaisse: montantEncaisse,
        mode_paiement: $('#mode_paiement').val(),
        date_paiement: $('#date_paiement').val(),
        mois_selectionnes: moisSelectionnes
    };

    $.ajax({
        url: '{{ route("cantine.store_paiement_mensuel") }}',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                $('#paiement-form')[0].reset();
                $('#date_paiement').val('{{ date("Y-m-d") }}');
                loadMoisAPayer(currentEleveId);
                
                if (response.paiement_id) {
                    window.open(`{{ url('cantine/receipt') }}/${response.paiement_id}`, '_blank');
                }
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
        $('#mois-section').hide();
        $('#aucun-mois-message').hide();
        $('#submit-paiement').prop('disabled', true);
        $('#total_configure').val('0 FCFA');
        $('#deja_paye').val('0 FCFA');
        $('#reste_a_payer').val('0 FCFA');
        $('#total_selectionne').val('0 FCFA');
        $('#montant_total').val('0 FCFA');
    }
});
</script>
@endsection