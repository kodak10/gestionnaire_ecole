@extends('dashboard.layouts.master')

@section('content')

<!-- Page Header -->
<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
    <div class="my-auto">
        <h3 class="mb-1">Gestion des Transports</h3>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Tableau de Bord</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Gestion Transports</li>
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
    <!-- Colonne de gauche - Sélection Classe/Élève -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-light">
                <h4 class="text-dark">Sélection</h4>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Classe <span class="text-danger">*</span></label>
                    <select class="form-select select2" id="classe_id" name="classe_id" required>
                        <option value="">Sélectionner une classe</option>
                        @foreach($classes as $classe)
                            <option value="{{ $classe->id }}" {{ old('classe_id') == $classe->id ? 'selected' : '' }}>
                                {{ $classe->nom }}
                            </option>
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
    </div>

    <!-- Colonne de droite - Détails + Récapitulatif -->
    <div class="col-md-8">
        <!-- Carte Informations Élève -->
        <div class="card mb-3">
            <div class="card-header bg-light">
                <h4 class="text-dark">Informations de l'Élève</h4>
            </div>
            <div class="card-body">
                <div id="eleve-info" class="alert alert-info d-none">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="mb-1" id="eleve-nom"></h5>
                            <p class="mb-0" id="eleve-details"></p>
                        </div>
                        <div class="col-md-6 text-end">
                            <span class="badge bg-success fs-6" id="statut-transport">Transport actif</span>
                        </div>
                    </div>
                </div>
                <div id="no-eleve-selected" class="text-center text-muted py-3">
                    <i class="ti ti-users fs-1 d-block mb-2"></i>
                    <p>Veuillez sélectionner un élève pour voir ses informations</p>
                </div>
            </div>
        </div>

        <!-- Carte Types de Transport avec Radio Buttons -->
        <div class="card mb-3">
            <div class="card-header bg-light">
                <h4 class="text-dark">Types de Transport</h4>
            </div>
            <div class="card-body">
                <div id="transport-types-container">
                    <div class="text-center text-muted py-3">
                        <i class="ti ti-truck fs-1 d-block mb-2"></i>
                        <p>Sélectionnez un élève pour voir ses types de transport</p>
                    </div>
                </div>
                <div class="mt-3" id="transport-total-container" style="display: none;">
                    <div class="alert alert-info">
                        <div class="row">
                            <div class="col-md-4">
                                <strong>Transport sélectionné:</strong>
                                <span id="selected-transport-name" class="fw-bold fs-5 text-primary">Aucun</span>
                            </div>
                            <div class="col-md-4">
                                <strong>Montant:</strong>
                                <span id="selected-transport-montant" class="fw-bold fs-5 text-success">0 F</span>
                            </div>
                            <div class="col-md-4">
                                <strong>Statut:</strong>
                                <span id="selected-transport-status" class="fw-bold fs-5">-</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bouton Mettre à jour -->
                <div class="mt-3" id="update-button-container" style="display: none;">
                    <button class="btn btn-success w-100" id="update-transport-btn">
                        <i class="ti ti-check me-2"></i>Mettre à jour le type de transport
                    </button>
                </div>
            </div>
        </div>

        <!-- Carte Paiement Transport -->
        <div class="card">
            <div class="card-header bg-light">
                <h4 class="text-dark">Paiement Transport</h4>
            </div>
            <div class="card-body">
                <div id="paiement-info" class="alert alert-info d-none">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Total Transport:</strong>
                            <span id="total-transport" class="fw-bold">0 F</span>
                        </div>
                        <div class="col-md-6">
                            <strong>Reste à payer:</strong>
                            <span id="reste-a-payer" class="fw-bold text-danger">0 F</span>
                        </div>
                    </div>
                </div>

                <form id="paiement-form">
                    @csrf
                    <input type="hidden" id="paiement_inscription_id" name="inscription_id">
                    <input type="hidden" id="paiement_type_frais_id" name="type_frais_id">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Montant à payer <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="montant_paiement" name="montant" min="0" step="100" required>
                            <small class="text-muted" id="reste-a-payer-info">Reste à payer: 0 F</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mode de paiement <span class="text-danger">*</span></label>
                            <select class="form-select" id="mode_paiement" name="mode_paiement" required>
                                <option value="">Sélectionner</option>
                                <option value="especes">Espèces</option>
                                <option value="cheque">Chèque</option>
                                <option value="virement">Virement</option>
                                <option value="mobile_money">Mobile Money</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date de paiement <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="date_paiement" name="date_paiement" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Référence (optionnel)</label>
                            <input type="text" class="form-control" id="reference_paiement" name="reference" placeholder="N° de transaction...">
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary w-100" id="save-paiement-btn" disabled>
                            <i class="ti ti-check me-2"></i>Enregistrer le paiement
                        </button>
                    </div>
                </form>
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

.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 36px;
}

.fw-bold {
    font-weight: 600;
}

.transport-type-card {
    border-left: 4px solid #0d6efd;
    transition: all 0.3s ease;
    cursor: pointer;
}

.transport-type-card:hover {
    background-color: #f8f9fa;
}

.transport-type-card .form-check-input {
    cursor: pointer;
}

.transport-type-card .form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.transport-type-card.selected {
    border-left-color: #198754;
    background-color: #f0fff4;
}

.transport-type-card.unavailable {
    border-left-color: #dc3545;
    opacity: 0.6;
}

.badge-status {
    font-size: 14px;
    padding: 5px 12px;
}

#update-transport-btn:disabled,
#save-paiement-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
// Configuration de toastr
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

    // Variables globales
    let currentInscriptionId = null;
    let currentTransportData = null;
    let selectedTypeId = null;
    let selectedTypeNom = null;
    let selectedTypeMontant = null;
    let selectedTarifId = null;

    // Charger les élèves quand une classe est sélectionnée
    $('#classe_id').change(function() {
        const classeId = $(this).val();
        $('#inscription_id').empty().append('<option value="">Sélectionner un élève</option>');
        resetTransportDisplay();
        
        if (classeId) {
            $('#inscription_id').prop('disabled', false);
            
            $.ajax({
                url: '{{ route("eleves.by_classe_transport") }}',
                type: 'GET',
                data: { classe_id: classeId },
                success: function(response) {
                    if (response.length > 0) {
                        $.each(response, function(index, eleve) {
                            $('#inscription_id').append(`<option value="${eleve.id}">${eleve.nom_complet}</option>`);
                        });
                    } else {
                        $('#inscription_id').append('<option value="">Aucun élève avec transport actif</option>');
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
        $('#save-paiement-btn').prop('disabled', true);
    });

    // Activer le bouton de chargement quand un élève est sélectionné
    $('#inscription_id').change(function() {
        $('#load-btn').prop('disabled', !$(this).val());
        $('#save-paiement-btn').prop('disabled', true);
    });

    // Charger les données de l'élève
    $('#load-btn').click(function() {
        const inscriptionId = $('#inscription_id').val();
        
        if (inscriptionId) {
            currentInscriptionId = inscriptionId;
            loadTransportData(inscriptionId);
        }
    });

    // Fonction pour charger les données de transport
    function loadTransportData(inscriptionId) {
        $.ajax({
            url: '{{ route("reglements.eleve_transport_data") }}',
            type: 'GET',
            data: { inscription_id: inscriptionId },
            beforeSend: function() {
                $('#transport-types-container').html('<div class="text-center py-3"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Chargement...</span></div><br><span class="text-muted">Chargement des données...</span></div>');
                $('#transport-total-container').hide();
                $('#update-button-container').hide();
                $('#paiement-info').addClass('d-none');
                $('#save-paiement-btn').prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    currentTransportData = response;
                    selectedTypeId = response.selected_transport_id;
                    
                    // Afficher les infos de l'élève
                    $('#eleve-nom').text(response.eleve.nom_complet);
                    $('#eleve-details').text(`Matricule: ${response.eleve.matricule} | Classe: ${response.eleve.classe}`);
                    $('#eleve-info').removeClass('d-none');
                    $('#no-eleve-selected').addClass('d-none');
                    
                    // Afficher les types de transport avec radio buttons
                    displayTransportTypes(response.transports);
                    
                    // Afficher le récapitulatif du transport sélectionné
                    updateSelectedTransport(response.transports);
                    $('#transport-total-container').show();
                    $('#update-button-container').show();
                    
                    // Afficher les infos de paiement
                    updatePaiementInfo(response);
                    $('#paiement-info').removeClass('d-none');
                    $('#save-paiement-btn').prop('disabled', false);
                } else {
                    toastr.error(response.message);
                    $('#transport-types-container').html('<div class="alert alert-danger">' + response.message + '</div>');
                }
            },
            error: function() {
                toastr.error('Erreur lors du chargement des données');
                $('#transport-types-container').html('<div class="alert alert-danger">Erreur de chargement</div>');
            }
        });
    }

    // Afficher les types de transport avec radio buttons
    function displayTransportTypes(transports) {
        if (!transports || transports.length === 0) {
            $('#transport-types-container').html(`
                <div class="alert alert-warning">
                    <i class="ti ti-alert-circle me-2"></i>
                    Aucun type de transport disponible pour cet élève.
                </div>
            `);
            return;
        }

        let html = '<div class="row g-3">';
        transports.forEach(function(transport) {
            const isDisabled = transport.montant == 0 || !transport.est_actif;
            const isSelected = transport.est_selectionne || false;
            
            let cardClass = 'transport-type-card';
            if (isDisabled) {
                cardClass += ' unavailable';
            }
            if (isSelected) {
                cardClass += ' selected';
            }
            
            const statusText = isDisabled ? 'Non disponible' : (isSelected ? '✓ Sélectionné' : 'Disponible');
            const statusClass = isDisabled ? 'bg-secondary' : (isSelected ? 'bg-success' : 'bg-primary');
            
            html += `
                <div class="col-md-6">
                    <div class="card ${cardClass}">
                        <div class="card-body">
                            <div class="form-check">
                                <input class="form-check-input transport-radio" type="radio" 
                                       name="transport_type"
                                       id="transport_${transport.type_id}" 
                                       value="${transport.type_id}"
                                       data-montant="${transport.montant}"
                                       data-nom="${transport.type_nom}"
                                       data-tarif-id="${transport.tarif_id || ''}"
                                       ${isSelected ? 'checked' : ''}
                                       ${isDisabled ? 'disabled' : ''}>
                                <label class="form-check-label fw-bold" for="transport_${transport.type_id}">
                                    ${transport.type_nom}
                                    <span class="badge ${isDisabled ? 'bg-secondary' : (isSelected ? 'bg-success' : 'bg-primary')} ms-2">
                                        ${isDisabled ? 'Non disponible' : formatMoney(transport.montant)}
                                    </span>
                                </label>
                                <br>
                                <span class="badge ${statusClass} badge-status mt-1">${statusText}</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        
        $('#transport-types-container').html(html);
        
        // Événement sur les radio buttons
        $('.transport-radio').on('change', function() {
            if ($(this).is(':checked')) {
                selectedTypeId = $(this).val();
                selectedTypeNom = $(this).data('nom');
                selectedTypeMontant = $(this).data('montant');
                selectedTarifId = $(this).data('tarif-id');
                
                // Mettre à jour l'affichage
                updateSelectedTransportDisplay(selectedTypeNom, selectedTypeMontant);
                
                // Mettre à jour les cartes
                $('.transport-type-card').removeClass('selected');
                $(this).closest('.col-md-6').find('.transport-type-card').addClass('selected');
                
                // Mettre à jour les infos de paiement
                updatePaiementInfoWithMontant(selectedTypeMontant);
            }
        });
    }

// Mettre à jour les informations de paiement
function updatePaiementInfo(response) {
    if (response.transports && response.transports.length > 0) {
        let selected = response.transports.find(t => t.est_selectionne);
        if (!selected) {
            selected = response.transports.find(t => t.est_actif && t.montant_mensuel > 0);
        }
        
        if (selected) {
            const totalMois = parseInt(selected.total_mois) || 0;
            const moisEcoules = parseInt(selected.mois_ecoules) || 0;
            const moisPayes = parseInt(selected.mois_payes) || 0;
            const moisRestants = parseInt(selected.mois_restants) || 0;
            const montantTotal = parseFloat(selected.montant_total) || 0;
            const montantPaye = parseFloat(selected.montant_paye) || 0;
            const montantReste = parseFloat(selected.montant_reste) || 0;
            const montantMensuel = parseFloat(selected.montant_mensuel) || 0;
            
            $('#total-transport').text(formatMoney(montantTotal));
            $('#reste-a-payer').text(formatMoney(montantReste));
            $('#reste-a-payer-info').text(
                'Reste à payer: ' + formatMoney(montantReste) + 
                ' (' + moisRestants + ' mois restants sur ' + moisEcoules + ' écoulés)'
            );
            $('#montant_paiement').attr('max', montantReste);
            $('#montant_paiement').attr('placeholder', 'Max: ' + formatMoney(montantReste));
            
            // Afficher les détails du transport
            $('#selected-transport-name').text(selected.type_nom);
            $('#selected-transport-montant').text(formatMoney(montantMensuel) + '/mois');
            $('#selected-transport-status').text(
                'Mois: ' + moisPayes + '/' + moisEcoules + ' payés | Total: ' + totalMois + ' mois'
            );
            
            $('#paiement_inscription_id').val(currentInscriptionId);
            $('#paiement_type_frais_id').val(selected.type_id);
        }
    }
}

    function updatePaiementInfoWithMontant(montant) {
    const montantNum = parseFloat(montant) || 0;
    $('#total-transport').text(formatMoney(montantNum));
    $('#reste-a-payer').text(formatMoney(montantNum));
    $('#reste-a-payer-info').text('Reste à payer: ' + formatMoney(montantNum));
    $('#montant_paiement').attr('max', montantNum);
    $('#montant_paiement').attr('placeholder', 'Max: ' + formatMoney(montantNum));
}

// Mettre à jour l'affichage du transport sélectionné
function updateSelectedTransport(transports) {
    let selected = null;
    transports.forEach(function(transport) {
        if (transport.est_selectionne) {
            selected = transport;
        }
    });
    
    if (selected) {
        selectedTypeId = selected.type_id;
        selectedTypeNom = selected.type_nom;
        selectedTypeMontant = selected.montant_mensuel;
        updateSelectedTransportDisplay(selected.type_nom, selected.montant_mensuel);
        
        // Mettre à jour les informations de paiement
        if (selected.montant_total > 0) {
            updatePaiementInfoWithMontant(selected.montant_reste);
        }
    } else {
        // Si aucun n'est sélectionné, prendre le premier disponible
        const firstAvailable = transports.find(t => t.est_actif && t.montant_mensuel > 0);
        if (firstAvailable) {
            selectedTypeId = firstAvailable.type_id;
            selectedTypeNom = firstAvailable.type_nom;
            selectedTypeMontant = firstAvailable.montant_mensuel;
            updateSelectedTransportDisplay(firstAvailable.type_nom, firstAvailable.montant_mensuel);
            // Cocher automatiquement le premier disponible
            $(`input[name="transport_type"][value="${firstAvailable.type_id}"]`).prop('checked', true);
            $(`input[name="transport_type"][value="${firstAvailable.type_id}"]`).closest('.col-md-6').find('.transport-type-card').addClass('selected');
            
            // Mettre à jour les informations de paiement
            if (firstAvailable.montant_total > 0) {
                updatePaiementInfoWithMontant(firstAvailable.montant_reste);
            }
        } else {
            $('#selected-transport-name').text('Aucun disponible');
            $('#selected-transport-montant').text('0 F');
            $('#selected-transport-status').text('Non disponible').removeClass('text-success text-danger').addClass('text-secondary');
            selectedTypeId = null;
        }
    }
}

    // Mettre à jour l'affichage du transport sélectionné
    function updateSelectedTransportDisplay(nom, montant) {
        $('#selected-transport-name').text(nom);
        $('#selected-transport-montant').text(formatMoney(montant));
        $('#selected-transport-status').text('Actif').removeClass('text-secondary text-danger').addClass('text-success');
    }

    // Bouton Mettre à jour
    $('#update-transport-btn').click(function() {
        if (!currentInscriptionId) {
            toastr.error('Veuillez sélectionner un élève');
            return;
        }

        if (!selectedTypeId) {
            toastr.error('Veuillez sélectionner un type de transport');
            return;
        }

        const button = $(this);
        button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Mise à jour...');

        $.ajax({
            url: '{{ route("transport.update_transport_type") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                inscription_id: currentInscriptionId,
                transport_type_id: selectedTypeId,
                tarif_id: selectedTarifId
            },
            success: function(response) {
                if (response.success) {
                    toastr.success('Type de transport mis à jour avec succès');
                    
                    // Mettre à jour le badge de l'élément sélectionné
                    $('.transport-radio').each(function() {
                        const badge = $(this).closest('.form-check').find('.badge-status');
                        if ($(this).is(':checked')) {
                            badge.text('✓ Sélectionné').removeClass('bg-primary bg-secondary').addClass('bg-success');
                        } else if ($(this).is(':disabled')) {
                            badge.text('Non disponible').removeClass('bg-primary bg-success').addClass('bg-secondary');
                        } else {
                            badge.text('Disponible').removeClass('bg-success bg-secondary').addClass('bg-primary');
                        }
                    });
                    
                    // Mettre à jour le statut
                    $('#selected-transport-status').text('Actif').removeClass('text-secondary text-danger').addClass('text-success');
                } else {
                    toastr.error(response.message || 'Erreur lors de la mise à jour');
                }
            },
            error: function(xhr) {
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    toastr.error(xhr.responseJSON.message);
                } else {
                    toastr.error('Erreur lors de la mise à jour du type de transport');
                }
            },
            complete: function() {
                button.prop('disabled', false).html('<i class="ti ti-check me-2"></i>Mettre à jour le type de transport');
            }
        });
    });

    // Soumission du formulaire de paiement
    $('#paiement-form').submit(function(e) {
        e.preventDefault();

        if (!currentInscriptionId) {
            toastr.error('Veuillez sélectionner un élève');
            return;
        }

        const montant = $('#montant_paiement').val();
        const modePaiement = $('#mode_paiement').val();
        const datePaiement = $('#date_paiement').val();
        const typeFraisId = $('#paiement_type_frais_id').val();

        if (!montant || parseFloat(montant) <= 0) {
            toastr.error('Veuillez saisir un montant valide');
            return;
        }

        if (!modePaiement) {
            toastr.error('Veuillez sélectionner un mode de paiement');
            return;
        }

        if (!typeFraisId) {
            toastr.error('Veuillez sélectionner un type de transport');
            return;
        }

        const button = $('#save-paiement-btn');
        button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement...');

        $.ajax({
            url: '{{ route("reglements.store_paiement_transport") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                inscription_id: currentInscriptionId,
                montant_transport: montant,
                mode_paiement: modePaiement,
                date_paiement: datePaiement,
                type_transport_id: typeFraisId,
                reference: $('#reference_paiement').val()
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#montant_paiement').val('');
                    $('#mode_paiement').val('');
                    $('#reference_paiement').val('');
                    // Recharger les données
                    loadTransportData(currentInscriptionId);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    toastr.error(xhr.responseJSON.message);
                } else {
                    toastr.error('Erreur lors de l\'enregistrement du paiement');
                }
            },
            complete: function() {
                button.prop('disabled', false).html('<i class="ti ti-check me-2"></i>Enregistrer le paiement');
            }
        });
    });

    // Réinitialiser l'affichage
    function resetTransportDisplay() {
        $('#transport-types-container').html('<div class="text-center text-muted py-3"><i class="ti ti-truck fs-1 d-block mb-2"></i><p>Sélectionnez un élève pour voir ses types de transport</p></div>');
        $('#eleve-info').addClass('d-none');
        $('#no-eleve-selected').removeClass('d-none');
        $('#transport-total-container').hide();
        $('#update-button-container').hide();
        $('#paiement-info').addClass('d-none');
        $('#selected-transport-name').text('Aucun');
        $('#selected-transport-montant').text('0 F');
        $('#selected-transport-status').text('-').removeClass('text-success text-danger').addClass('text-secondary');
        $('#total-transport').text('0 F');
        $('#reste-a-payer').text('0 F');
        $('#reste-a-payer-info').text('Reste à payer: 0 F');
        selectedTypeId = null;
        selectedTypeNom = null;
        selectedTypeMontant = null;
        selectedTarifId = null;
        $('#save-paiement-btn').prop('disabled', true);
    }

    function formatMoney(amount) {
        return new Intl.NumberFormat('fr-FR', { 
            style: 'currency', 
            currency: 'XOF',
            minimumFractionDigits: 0
        }).format(amount || 0);
    }
});
</script>
@endsection