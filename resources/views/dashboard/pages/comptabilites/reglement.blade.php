@extends('dashboard.layouts.master')
@section('content')
<!-- Page Header -->
<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
    <div class="my-auto">
        <h3 class="mb-1">Gestion des Règlements</h3>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Tableau de Bord</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Règlements</li>
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

        

        <!-- Carte Récapitulatif - Scolarité -->
        <div class="card mt-3">
            <div class="card-header bg-light">
                <h4 class="text-dark">Récapitulatif - Scolarité</h4>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Montant Scolarité</label>
                    <input type="text" class="form-control" id="montant_scolarite" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Total Payé</label>
                    <input type="text" class="form-control" id="total_paye_scolarite" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Reste à Payer</label>
                    <input type="text" class="form-control fw-bold text-danger" id="reste_a_payer_scolarite" readonly>
                </div>
            </div>
        </div>
    </div>

    <!-- Colonne de droite - Détails des Paiements -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-light">
                <h4 class="text-dark">Détails des Paiements</h4>
            </div>
            <div class="card-body">
                <div id="eleve-info" class="alert alert-info d-none">
                    <h5 class="mb-1" id="eleve-nom"></h5>
                    <p class="mb-0" id="eleve-details"></p>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover" id="paiements-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Montant</th>
                                <th>Mode de Paiement</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="6" class="text-center">Veuillez sélectionner un élève pour voir les paiements</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Formulaire de nouveau paiement -->
                <div class="mt-4 border-top pt-3">
                    <h5 class="mb-3">Nouveau Paiement</h5>
                    <form id="paiement-form">
                        @csrf
                        <input type="hidden" id="paiement_inscription_id" name="inscription_id">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">Inscription</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">Montant à payer</label>
                                            <input type="number" class="form-control" id="montant_inscription_input" name="montant_inscription" min="0" value="0">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Reste à payer: <span id="reste_inscription_label" class="fw-bold">0 FCFA</span></label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">Scolarité</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">Montant à payer</label>
                                            <input type="number" class="form-control" id="montant_scolarite_input" name="montant_scolarite" min="0" value="0">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Reste à payer: <span id="reste_scolarite_label" class="fw-bold">0 FCFA</span></label>
                                        </div>
                                    </div>
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
                                    <label class="form-label">Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="date_paiement" name="date_paiement" value="{{ date('Y-m-d') }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-check me-2"></i>Enregistrer Paiement
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour confirmation de suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer ce paiement?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" id="confirm-delete">Supprimer</button>
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

.table-hover tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.02);
}

.fw-bold {
    font-weight: 600;
}

#save-reduction-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.reduction-loading {
    background-color: #f8f9fa;
    border-color: #007bff;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23007bff' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M12 2v4'/%3E%3Cpath d='m16.24 7.76 2.83-2.83'/%3E%3Cpath d='M17 12h4'/%3E%3Cpath d='m16.24 16.24 2.83 2.83'/%3E%3Cpath d='M12 17v4'/%3E%3Cpath d='m7.76 16.24-2.83 2.83'/%3E%3Cpath d='M7 12H3'/%3E%3Cpath d='m7.76 7.76-2.83-2.83'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 8px center;
    background-size: 16px;
    padding-right: 32px;
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
    let paiementToDelete = null;
    let currentResteInscription = 0;
    let currentResteScolarite = 0;
    

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
                            $('#inscription_id').append(`<option value="${eleve.inscription_id}">${eleve.nom_complet} (${eleve.matricule})</option>`);
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
            currentEleveId = inscriptionId;

            $('#paiement_inscription_id').val(inscriptionId);

            loadEleveData(inscriptionId);
        }
    });

    // Charger les données d'un élève
    function loadEleveData(inscriptionId) {
        $.ajax({
            url: '{{ route("reglements.eleve_data") }}',
            type: 'GET',
            data: { 
                inscription_id: inscriptionId,
            },
            beforeSend: function() {
                $('#paiements-table tbody').html('<tr><td colspan="6" class="text-center">Chargement en cours...</td></tr>');
            },
            success: function(response) {
                if (response.success) {
                    updateSummary(response);
                    updatePaiementsTable(response.paiements);
                } else {
                    toastr.error(response.message);
                    $('#paiements-table tbody').html('<tr><td colspan="6" class="text-center">Aucun paiement trouvé</td></tr>');
                }
            },
            error: function() {
                toastr.error('Erreur lors du chargement des données');
                $('#paiements-table tbody').html('<tr><td colspan="6" class="text-center">Erreur de chargement</td></tr>');
            }
        });
    }

    function updateSummary(data) {
        // Mise à jour des informations de l'élève
        $('#eleve-nom').text(data.eleve.nom_complet);
        $('#eleve-details').text(`Matricule: ${data.eleve.matricule} | Classe: ${data.eleve.classe}`);
        $('#eleve-info').removeClass('d-none');

        // Mise à jour des informations d'inscription
        $('#montant_inscription').val(formatMoney(data.frais.inscription));
        $('#total_paye_inscription').val(formatMoney(data.total_paye.inscription));
        $('#reste_a_payer_inscription').val(formatMoney(data.reste_a_payer.inscription));
        
        // Stocker les valeurs pour validation
        currentResteInscription = data.reste_a_payer.inscription;
        $('#reste_inscription_label').text(formatMoney(data.reste_a_payer.inscription));

        // Mise à jour des informations de scolarité
        $('#montant_scolarite').val(formatMoney(data.frais.scolarite));
        $('#total_paye_scolarite').val(formatMoney(data.total_paye.scolarite));
        $('#reste_a_payer_scolarite').val(formatMoney(data.reste_a_payer.scolarite));
        
        // Stocker les valeurs pour validation
        currentResteScolarite = data.reste_a_payer.scolarite;
        $('#reste_scolarite_label').text(formatMoney(data.reste_a_payer.scolarite));
        
        // Réinitialiser les champs de saisie
        $('#montant_inscription_input').val(0).attr('max', currentResteInscription);
        $('#montant_scolarite_input').val(0).attr('max', currentResteScolarite);
    }

    function updatePaiementsTable(paiements) {
    let html = '';
    if (paiements.length > 0) {
        $.each(paiements, function(index, paiement) {
            // Calculer le total du paiement
            let totalMontant = paiement.details.reduce((sum, detail) => sum + parseFloat(detail.montant), 0);

            // Construire la chaîne des types
            let types = paiement.details.map(detail => detail.type_frais.nom).join(' + ');

            html += `
            <tr>
                <td>${formatDate(paiement.created_at)}</td>
                <td>${types}</td>
                <td>${formatMoney(totalMontant)}</td>
                <td>${formatModePaiement(paiement.mode_paiement)}</td>
                <td>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-success btn-recu" data-id="${paiement.id}">
                            <i class="ti ti-printer"></i>
                        </button>
                        <button class="btn btn-sm btn-danger btn-delete" data-id="${paiement.id}">
                            <i class="ti ti-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
            `;
        });


    } else {
        html = '<tr><td colspan="6" class="text-center">Aucun paiement trouvé</td></tr>';
    }
    $('#paiements-table tbody').html(html);

    $('.btn-recu').click(function() {
        generateReceipt($(this).data('id'));
    });
    $('.btn-delete').click(function() {
        showDeleteModal($(this).data('id'));
    });
}



    function formatMoney(amount) {
        return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(amount);
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('fr-FR');
    }

    function formatModePaiement(mode) {
        const modes = { 'especes':'Espèces', 'cheque':'Chèque', 'virement':'Virement', 'mobile_money':'Mobile Money' };
        return modes[mode] || mode;
    }

    // Validation des montants saisis
    $('#montant_inscription_input').on('input', function() {
        const montant = parseFloat($(this).val()) || 0;
        if (montant > currentResteInscription) {
            toastr.error('Le montant ne peut pas dépasser le reste à payer');
            $(this).val(currentResteInscription);
        }
    });

    $('#montant_scolarite_input').on('input', function() {
        const montant = parseFloat($(this).val()) || 0;
        if (montant > currentResteScolarite) {
            toastr.error('Le montant ne peut pas dépasser le reste à payer');
            $(this).val(currentResteScolarite);
        }
    });

    // Soumission du formulaire de paiement
    $('#paiement-form').submit(function(e) {
        e.preventDefault();
        if (!currentEleveId) { 
            toastr.error('Veuillez sélectionner un élève'); 
            return; 
        }
        
        const montantInscription = parseFloat($('#montant_inscription_input').val()) || 0;
        const montantScolarite = parseFloat($('#montant_scolarite_input').val()) || 0;
        
        if (montantInscription === 0 && montantScolarite === 0) {
            toastr.error('Veuillez saisir au moins un montant');
            return;
        }
        
        if (montantInscription > currentResteInscription) {
            toastr.error('Le montant d\'inscription ne peut pas dépasser le reste à payer');
            return;
        }
        
        if (montantScolarite > currentResteScolarite) {
            toastr.error('Le montant de scolarité ne peut pas dépasser le reste à payer');
            return;
        }
        
        const formData = $(this).serialize();

        $.ajax({
            url: '{{ route("reglements.store_paiement") }}',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#paiement-form')[0].reset();
                    $('#date_paiement').val('{{ date("Y-m-d") }}');
                    loadEleveData(currentEleveId);
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

    function showDeleteModal(paiementId) {
        paiementToDelete = paiementId;
        $('#deleteModal').modal('show');
    }

    $('#confirm-delete').click(function() {
        if (!paiementToDelete) return;
        const button = $(this);
        button.prop('disabled', true);

        $.ajax({
            url: '{{ route("reglements.delete_paiement") }}',
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}', paiement_id: paiementToDelete },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                } else {
                    toastr.error(response.message);
                }
                $('#deleteModal').modal('hide');
                loadEleveData(currentEleveId);
            },
            error: function() { 
                toastr.error('Erreur lors de la suppression du paiement'); 
            },
            complete: function() { 
                button.prop('disabled', false); 
                paiementToDelete = null; 
            }
        });
    });

    function generateReceipt(paiementId) {
        window.open('{{ url("reglements/receipt") }}/' + paiementId, '_blank');
    }
});
</script>
@endsection