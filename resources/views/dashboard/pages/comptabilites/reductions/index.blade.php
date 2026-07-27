@extends('dashboard.layouts.master')

@section('content')
<!-- Page Header -->
<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
    <div class="my-auto">
        <h3 class="mb-1">Gestion des Réductions</h3>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Tableau de Bord</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Réductions</li>
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
                        @foreach(\App\Models\Classe::forEcoleAndAnnee(session('current_ecole_id'), session('current_annee_scolaire_id'))->ordered()->get() as $classe)
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

       
    </div>

    <!-- Colonne de droite - Gestion des réductions -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-light">
                <h4 class="text-dark">Gestion des Réductions</h4>
            </div>
            <div class="card-body">
                <div id="eleve-info" class="alert alert-info d-none">
                    <h5 class="mb-1" id="eleve-nom"></h5>
                    <p class="mb-0" id="eleve-details"></p>
                </div>

                <div id="reductions-container">
                    <div class="text-center text-muted py-4">
                        <i class="ti ti-discount-2 fs-1 d-block mb-2"></i>
                        <p>Sélectionnez un élève pour gérer ses réductions</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cartes de sélection des tarifs Transport et Cantine -->
        <div class="row mt-3">
            <!-- Transport -->
            <div class="col-md-6" id="transport-tarif-card" style="display:none;">
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0 text-dark">
                            <i class="ti ti-truck me-2"></i>Sélection du Tarif Transport
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Choisir le tarif de transport</label>
                            <select class="form-select" id="transport_tarif_select">
                                <option value="">-- Sélectionner un tarif --</option>
                            </select>
                        </div>
                        <button class="btn btn-primary w-100" id="save-transport-tarif-btn">
                            <i class="ti ti-check me-2"></i>Appliquer le tarif de transport
                        </button>
                    </div>
                </div>
            </div>

            <!-- Cantine -->
            <div class="col-md-6" id="cantine-tarif-card" style="display:none;">
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0 text-dark">
                            <i class="ti ti-restaurant me-2"></i>Sélection du Tarif Cantine
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Choisir le tarif de cantine</label>
                            <select class="form-select" id="cantine_tarif_select">
                                <option value="">-- Sélectionner un tarif --</option>
                            </select>
                        </div>
                        <button class="btn btn-primary w-100" id="save-cantine-tarif-btn">
                            <i class="ti ti-check me-2"></i>Appliquer le tarif de cantine
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Confirmation -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Voulez-vous vraiment supprimer cette réduction ?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" id="confirm-delete-btn">Supprimer</button>
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

.reduction-item {
    border-left: 4px solid #0d6efd;
    transition: all 0.3s ease;
}

.reduction-item:hover {
    background-color: #f8f9fa;
}

.reduction-item .form-control-sm {
    max-width: 150px;
}

.tarif-row-transport {
    border-left: 4px solid #fd7e14 !important;
}

.tarif-row-cantine {
    border-left: 4px solid #198754 !important;
}

.tarif-row-default {
    border-left: 4px solid #0d6efd !important;
}

.badge-transport {
    background-color: #fd7e14;
    color: white;
}

.badge-cantine {
    background-color: #198754;
    color: white;
}

.badge-frais {
    background-color: #0d6efd;
    color: white;
}

#reductions-container .table td strong {
    font-weight: 700;
    font-size: 0.95rem;
}

#reductions-container .table td:nth-child(2) strong,
#reductions-container .table td:nth-child(4) strong {
    font-weight: 700;
}

#reductions-container .table td:nth-child(4) .text-success {
    color: #198754 !important;
}

#reductions-container .table td:nth-child(2) strong {
    color: #212529;
}

#reductions-container .table td:nth-child(4) strong {
    font-weight: 700;
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

    let currentInscriptionId = null;
    let currentReductionToDelete = null;

    // Charger les élèves quand une classe est sélectionnée
    $('#classe_id').change(function() {
        const classeId = $(this).val();
        $('#inscription_id').empty().append('<option value="">Sélectionner un élève</option>');

        if (classeId) {
            $('#inscription_id').prop('disabled', false);

            $.ajax({
                url: '{{ route("eleves.by_classe") }}',
                type: 'GET',
                data: { classe_id: classeId },
                success: function(response) {
                    if (response.length > 0) {
                        $.each(response, function(index, eleve) {
                            $('#inscription_id').append(`<option value="${eleve.inscription_id}">${eleve.nom_complet}</option>`);
                        });
                    } else {
                        $('#inscription_id').append('<option value="">Aucun élève dans cette classe</option>');
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
            currentInscriptionId = inscriptionId;
            loadEleveData(inscriptionId);
        }
    });

    function loadEleveData(inscriptionId) {
        $.ajax({
            url: '{{ route("reductions.get_eleve_data") }}',
            type: 'GET',
            data: { inscription_id: inscriptionId },
            beforeSend: function() {
                $('#reductions-container').html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Chargement...</span></div></div>');
                $('#recap-card').hide();
                $('#transport-tarif-card').hide();
                $('#cantine-tarif-card').hide();
            },
            success: function(response) {
                if (response.success) {
                    updateEleveInfo(response);
                    displayReductions(response);
                    updateRecap(response);
                    displayTarifsSelection(response);
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

    function displayReductions(data) {
        // UNIQUEMENT les frais (Scolarité, Inscription, etc.)
        let allTarifs = [];

        if (data.frais && data.frais.length > 0) {
            allTarifs = allTarifs.concat(data.frais);
        }

        if (allTarifs.length === 0) {
            $('#reductions-container').html(`
                <div class="alert alert-warning">
                    <i class="ti ti-alert-circle me-2"></i>
                    Aucun frais trouvé pour cet élève.
                </div>
            `);
            return;
        }

        let html = `
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Libellé du Tarif</th>
                            <th>Montant Total</th>
                            <th>Réduction</th>
                            <th>Montant à Payer</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        allTarifs.forEach(function(item) {
            const reductionId = item.reduction_id || '';
            const reductionMontant = item.reduction_actuelle || 0;
            const libelle = item.libelle || '-';
            const typeFraisNom = item.type_frais_nom || 'Frais';
            const montantTotal = parseFloat(item.montant_total) || 0;
            const montantAPayer = montantTotal - reductionMontant;

            // Déterminer la classe CSS et l'icône selon le type
            let rowClass = 'tarif-row-default';
            let badgeClass = 'badge-frais';
            let icon = '📋';

            if (typeFraisNom.includes('Transport')) {
                rowClass = 'tarif-row-transport';
                badgeClass = 'badge-transport';
                icon = '🚚';
            } else if (typeFraisNom.includes('Cantine')) {
                rowClass = 'tarif-row-cantine';
                badgeClass = 'badge-cantine';
                icon = '🍽️';
            }

            // Format: "Type de Frais - Nom du Tarif"
            const displayLibelle = `${icon} ${typeFraisNom} - ${libelle}`;

            // Couleur du montant à payer
            let montantAPayerClass = 'fw-bold';
            if (montantAPayer === 0) {
                montantAPayerClass = 'fw-bold text-success';
            }

            html += `
                <tr class="${rowClass}">
                    <td><span class="${badgeClass}">${displayLibelle}</span></td>
                    <td><strong>${formatMoney(montantTotal)}</strong></td>
                    <td>
                        <input type="number" class="form-control form-control-sm reduction-input" 
                               data-tarif-id="${item.tarif_id}"
                               value="${reductionMontant}" 
                               min="0" 
                               max="${montantTotal}"
                               step="100"
                               style="max-width: 120px;">
                    </td>
                    <td>
                        <strong class="${montantAPayerClass}" id="montant-a-payer-${item.tarif_id}">
                            ${formatMoney(montantAPayer)}
                        </strong>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-success save-reduction" 
                                data-tarif-id="${item.tarif_id}"
                                data-reduction-id="${reductionId}">
                            <i class="ti ti-check"></i>
                        </button>
                        ${reductionId ? `
                            <button class="btn btn-sm btn-danger delete-reduction" 
                                    data-reduction-id="${reductionId}">
                                <i class="ti ti-trash"></i>
                            </button>
                        ` : ''}
                    </td>
                </tr>
            `;
        });

        html += `
                    </tbody>
                </table>
            </div>
            <div class="text-end mt-3">
                <small class="text-muted">* La réduction ne peut pas dépasser le montant total</small>
            </div>
        `;

        $('#reductions-container').html(html);

        // Événements
        $('.save-reduction').click(function() {
            const tarifId = $(this).data('tarif-id');
            const reductionId = $(this).data('reduction-id');
            const montant = $(this).closest('tr').find('.reduction-input').val();
            const maxMontant = parseFloat($(this).closest('tr').find('.reduction-input').attr('max')) || 0;

            if (!montant || parseFloat(montant) < 0) {
                toastr.error('Veuillez saisir un montant valide');
                return;
            }

            if (parseFloat(montant) > maxMontant) {
                toastr.error('La réduction ne peut pas dépasser le montant total');
                return;
            }

            saveReduction(currentInscriptionId, tarifId, montant, reductionId);
        });

        $('.delete-reduction').click(function() {
            const reductionId = $(this).data('reduction-id');
            currentReductionToDelete = reductionId;
            $('#confirmModal').modal('show');
        });

        // Validation des montants et mise à jour dynamique du "Montant à Payer"
        $('.reduction-input').on('input', function() {
            const max = parseFloat($(this).attr('max')) || 0;
            const value = parseFloat($(this).val()) || 0;
            const tarifId = $(this).data('tarif-id');
            const montantTotal = max;
            const nouveauMontantAPayer = montantTotal - value;

            if (value > max) {
                $(this).val(max);
                toastr.warning('La réduction ne peut pas dépasser le montant total');
            }

            // Mettre à jour le montant à payer dynamiquement
            const montantAPayerElement = $(`#montant-a-payer-${tarifId}`);
            if (montantAPayerElement.length) {
                montantAPayerElement.text(formatMoney(nouveauMontantAPayer));
                
                // Changer la couleur en fonction du montant
                montantAPayerElement.removeClass('text-success');
                if (nouveauMontantAPayer === 0) {
                    montantAPayerElement.addClass('text-success');
                }
            }
        });
    }

    function displayTarifsSelection(data) {
        // Transport - Affiche la carte si transport actif ET qu'il y a des tarifs
        if (data.transport_tarifs && data.transport_tarifs.length > 0) {
            $('#transport-tarif-card').show();
            const select = $('#transport_tarif_select');
            select.empty();
            select.append('<option value="">-- Sélectionner un tarif --</option>');
            
            data.transport_tarifs.forEach(function(tarif) {
                const selected = (data.selected_transport_tarif == tarif.tarif_id) ? 'selected' : '';
                const libelle = tarif.libelle ? ` - ${tarif.libelle}` : '';
                const typeFraisNom = tarif.type_frais_nom || 'Transport';
                
                select.append(`<option value="${tarif.tarif_id}" ${selected}>
                    ${typeFraisNom}${libelle} (${formatMoney(tarif.montant_total)})
                </option>`);
            });

            // Pré-sélectionner le tarif actuel
            if (data.selected_transport_tarif) {
                $('#transport_tarif_select').val(data.selected_transport_tarif);
            }
        } else {
            $('#transport-tarif-card').hide();
        }

        // Cantine - Affiche la carte si cantine active ET qu'il y a des tarifs
        if (data.cantine_tarifs && data.cantine_tarifs.length > 0) {
            $('#cantine-tarif-card').show();
            const select = $('#cantine_tarif_select');
            select.empty();
            select.append('<option value="">-- Sélectionner un tarif --</option>');
            
            data.cantine_tarifs.forEach(function(tarif) {
                const selected = (data.selected_cantine_tarif == tarif.tarif_id) ? 'selected' : '';
                const libelle = tarif.libelle ? ` - ${tarif.libelle}` : '';
                const typeFraisNom = tarif.type_frais_nom || 'Cantine';
                
                select.append(`<option value="${tarif.tarif_id}" ${selected}>
                    ${typeFraisNom}${libelle} (${formatMoney(tarif.montant_total)})
                </option>`);
            });

            // Pré-sélectionner le tarif actuel (même si NULL, l'option vide sera sélectionnée)
            if (data.selected_cantine_tarif) {
                $('#cantine_tarif_select').val(data.selected_cantine_tarif);
            }
        } else {
            $('#cantine-tarif-card').hide();
        }
    }

    function updateRecap(data) {
        // Récupérer les données
        let scolarite = null;
        let inscription = null;
        let transport = null;
        let cantine = null;

        // Parcourir les frais pour trouver Scolarité et Inscription
        if (data.frais && data.frais.length > 0) {
            data.frais.forEach(function(item) {
                if (item.type_frais_nom && item.type_frais_nom.includes('Scolarité')) {
                    scolarite = item;
                } else if (item.type_frais_nom && item.type_frais_nom.includes('inscription')) {
                    inscription = item;
                }
            });
        }

        // Récupérer le transport sélectionné
        if (data.selected_transport_tarif && data.transport_tarifs && data.transport_tarifs.length > 0) {
            const selectedTransport = data.transport_tarifs.find(t => t.tarif_id == data.selected_transport_tarif);
            if (selectedTransport) {
                transport = selectedTransport;
            }
        }

        // Récupérer la cantine sélectionnée
        if (data.selected_cantine_tarif && data.cantine_tarifs && data.cantine_tarifs.length > 0) {
            const selectedCantine = data.cantine_tarifs.find(t => t.tarif_id == data.selected_cantine_tarif);
            if (selectedCantine) {
                cantine = selectedCantine;
            }
        }

        // Calcul des totaux
        let totalGeneral = 0;
        let totalRestant = 0;

        // ---- Scolarité ----
        if (scolarite) {
            const montant = parseFloat(scolarite.montant_total) || 0;
            const reduction = parseFloat(scolarite.reduction_actuelle) || 0;
            const reste = montant - reduction;

            $('#card-scolarite').show();
            $('#montant_scolarite').val(formatMoney(montant));
            $('#reduction_scolarite').val(formatMoney(reduction));
            $('#reste_scolarite').val(formatMoney(reste));

            totalGeneral += montant;
            totalRestant += reste;
        } else {
            $('#card-scolarite').hide();
        }

        // ---- Inscription ----
        if (inscription) {
            const montant = parseFloat(inscription.montant_total) || 0;
            const reduction = parseFloat(inscription.reduction_actuelle) || 0;
            const reste = montant - reduction;

            $('#card-inscription').show();
            $('#montant_inscription').val(formatMoney(montant));
            $('#reduction_inscription').val(formatMoney(reduction));
            $('#reste_inscription').val(formatMoney(reste));

            totalGeneral += montant;
            totalRestant += reste;
        } else {
            $('#card-inscription').hide();
        }

        // ---- Transport ----
        if (transport) {
            const montant = parseFloat(transport.montant_total) || 0;

            $('#card-transport').show();
            $('#montant_transport').val(formatMoney(montant));
            $('#statut_transport').val(`Sélectionné - ${transport.libelle}`);
            $('#reste_transport').val(formatMoney(montant));

            totalGeneral += montant;
            totalRestant += montant;
        } else {
            $('#card-transport').hide();
        }

        // ---- Cantine ----
        if (cantine) {
            const montant = parseFloat(cantine.montant_total) || 0;

            $('#card-cantine').show();
            $('#montant_cantine').val(formatMoney(montant));
            $('#statut_cantine').val(`Sélectionné - ${cantine.libelle}`);
            $('#reste_cantine').val(formatMoney(montant));

            totalGeneral += montant;
            totalRestant += montant;
        } else {
            $('#card-cantine').hide();
        }

        // ---- Total Général ----
        if (totalGeneral > 0) {
            $('#card-total-general').show();
            $('#total_general').val(formatMoney(totalGeneral));
            $('#total_restant').val(formatMoney(totalRestant));
        } else {
            $('#card-total-general').hide();
        }

        // Afficher la carte récapitulative principale
        $('#recap-card').show();
    }

    function saveReduction(inscriptionId, tarifId, montant, reductionId) {
        if (!montant || parseFloat(montant) < 0) {
            toastr.error('Veuillez saisir un montant valide');
            return;
        }

        $.ajax({
            url: '{{ route("reductions.store") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                inscription_id: inscriptionId,
                tarif_id: tarifId,
                montant: montant,
                raison: 'Réduction manuelle'
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    loadEleveData(inscriptionId);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    toastr.error(xhr.responseJSON.message);
                } else {
                    toastr.error('Erreur lors de l\'enregistrement');
                }
            }
        });
    }

    // Sauvegarder le tarif de transport
    $('#save-transport-tarif-btn').click(function() {
        const tarifId = $('#transport_tarif_select').val();
        if (!tarifId) {
            toastr.error('Veuillez sélectionner un tarif de transport');
            return;
        }

        const button = $(this);
        button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>');

        $.ajax({
            url: '{{ route("reductions.update_transport_tarif") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                inscription_id: currentInscriptionId,
                tarif_id: tarifId
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    loadEleveData(currentInscriptionId);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    toastr.error(xhr.responseJSON.message);
                } else {
                    toastr.error('Erreur lors de la sélection du tarif');
                }
            },
            complete: function() {
                button.prop('disabled', false).html('<i class="ti ti-check me-2"></i>Appliquer le tarif de transport');
            }
        });
    });

    // Sauvegarder le tarif de cantine
    $('#save-cantine-tarif-btn').click(function() {
        const tarifId = $('#cantine_tarif_select').val();
        if (!tarifId) {
            toastr.error('Veuillez sélectionner un tarif de cantine');
            return;
        }

        const button = $(this);
        button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>');

        $.ajax({
            url: '{{ route("reductions.update_cantine_tarif") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                inscription_id: currentInscriptionId,
                tarif_id: tarifId
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    loadEleveData(currentInscriptionId);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    toastr.error(xhr.responseJSON.message);
                } else {
                    toastr.error('Erreur lors de la sélection du tarif');
                }
            },
            complete: function() {
                button.prop('disabled', false).html('<i class="ti ti-check me-2"></i>Appliquer le tarif de cantine');
            }
        });
    });

    // Confirmer la suppression
    $('#confirm-delete-btn').click(function() {
        if (!currentReductionToDelete) return;

        $.ajax({
            url: '{{ route("reductions.destroy", ["id" => ":id"]) }}'.replace(':id', currentReductionToDelete),
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    loadEleveData(currentInscriptionId);
                } else {
                    toastr.error(response.message);
                }
                $('#confirmModal').modal('hide');
                currentReductionToDelete = null;
            },
            error: function() {
                toastr.error('Erreur lors de la suppression');
                $('#confirmModal').modal('hide');
            }
        });
    });

    function formatMoney(amount) {
        return new Intl.NumberFormat('fr-FR', { 
            style: 'currency', 
            currency: 'XOF', 
            minimumFractionDigits: 0 
        }).format(amount || 0);
    }

    function resetInterface() {
        $('#eleve-info').addClass('d-none');
        $('#reductions-container').html(`
            <div class="text-center text-muted py-4">
                <i class="ti ti-discount-2 fs-1 d-block mb-2"></i>
                <p>Sélectionnez un élève pour gérer ses réductions</p>
            </div>
        `);
        $('#recap-card').hide();
        $('#transport-tarif-card').hide();
        $('#cantine-tarif-card').hide();
    }
});
</script>
@endsection