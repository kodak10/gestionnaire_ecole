@extends('dashboard.layouts.master')
@section('content')
<!-- Page Header -->
<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
    <div class="my-auto">
        <h3 class="mb-1">Gestion de la Scolarité</h3>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Tableau de Bord</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Gestion Scolarité</li>
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
        <div class="card mb-3">
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
                                {{-- <th>Actions</th> --}}
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="6" class="text-center">Veuillez sélectionner un élève pour voir les paiements</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                
            </div>
        </div>

        <!-- Carte Récapitulatif - Scolarité -->
        <div class="card">
            <div class="card-header bg-light">
                <h4 class="text-dark">Récapitulatif - Scolarité</h4>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Total Scolarité</label>
                        <input type="text" class="form-control" id="total_scolarite" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Total Payé</label>
                        <input type="text" class="form-control" id="total_paye_scolarite" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Reste à Payer</label>
                        <input type="text" class="form-control fw-bold text-danger" id="reste_payer_scolarite" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Réduction Accordée</label>
                        <input type="number" class="form-control" id="reduction_scolarite" value="{{ old('reduction_scolarite', 0) }}" min="0">
                    </div>
                </div>

                <div class="mt-3">
                    <button class="btn btn-primary w-100" id="save-reduction-btn">
                        <i class="ti ti-check me-2"></i>Appliquer Réduction
                    </button>
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
    let currentinscriptionId = null;
    let paiementToDelete = null;
    let currentReduction = 0;

    // Charger les élèves quand une classe est sélectionnée
    $('#classe_id').change(function() {
        const classeId = $(this).val();
        $('#inscription_id').empty().append('<option value="">Sélectionner un élève</option>');
        
        if (classeId) {
            $('#inscription_id').prop('disabled', false);
            
            // Requête AJAX pour charger les élèves de la classe
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
            currentinscriptionId = inscriptionId;
            
            // Charger les données de paiement
            loadPaiements(inscriptionId);
        }
    });

    // Fonction pour charger les paiements d'un élève
    function loadPaiements(inscriptionId) {

        console.log("Inscription ID envoyé:", inscriptionId);

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
                    // Adapter ici
                    const summary = {
                        total_scolarite: response.frais.scolarite,
                        total_paye_scolarite: response.total_paye.scolarite,
                        reste_payer_scolarite: response.reste_a_payer.scolarite,
                        reduction_scolarite: response.reduction.scolarite || 0
                    };
                    updateSummary(summary);
                    updatePaiementsTable(response.paiements);
                } else {
                    toastr.error(response.message);
                    $('#paiements-table tbody').html('<tr><td colspan="6" class="text-center">Aucun paiement trouvé</td></tr>');
                }
            },

            error: function() {
                toastr.error('Erreur lors du chargement des paiements');
                $('#paiements-table tbody').html('<tr><td colspan="6" class="text-center">Erreur de chargement</td></tr>');
            }
        });
    }

    // Mettre à jour le récapitulatif
    function updateSummary(summary) {
        $('#total_scolarite').val(formatMoney(summary.total_scolarite));
        $('#total_paye_scolarite').val(formatMoney(summary.total_paye_scolarite));
        $('#reste_payer_scolarite').val(formatMoney(summary.reste_payer_scolarite));
        $('#reduction_scolarite').val(summary.reduction_scolarite || 0);
        
        currentReduction = summary.reduction_scolarite
    }

    // Mettre à jour le tableau des paiements
    function updatePaiementsTable(paiements) {
        let html = '';
        
        if (paiements.length > 0) {
            $.each(paiements, function(index, paiement) {
                html += `
                <tr>
                    <td>${formatDate(paiement.created_at)}</td>
                    <td>${paiement.type_frais_id == 1 ? 'Inscription' : 'Scolarité'}</td>
                    <td>${formatMoney(paiement.montant)}</td>
                    <td>${formatModePaiement(paiement.mode_paiement)}</td>
                    

                </tr>
                `;
            });
        } else {
            html = '<tr><td colspan="5" class="text-center">Aucun paiement trouvé</td></tr>';
        }
        
        $('#paiements-table tbody').html(html);
        
        // Ajouter les événements aux boutons
        $('.btn-reçu').click(function() {
            const paiementId = $(this).data('id');
            generateReceipt(paiementId);
        });
        
        $('.btn-delete').click(function() {
            const paiementId = $(this).data('id');
            showDeleteModal(paiementId);
        });
    }


    // Formater un montant en argent
    function formatMoney(amount) {
        return new Intl.NumberFormat('fr-FR', { 
            style: 'currency', 
            currency: 'XOF',
            minimumFractionDigits: 0
        }).format(amount);
    }

    // Formater une date
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('fr-FR');
    }

    // Formater le mode de paiement
    function formatModePaiement(mode) {
        const modes = {
            'especes': 'Espèces',
            'cheque': 'Chèque',
            'virement': 'Virement',
            'mobile_money': 'Mobile Money'
        };
        return modes[mode] || mode;
    }

    // Appliquer une réduction spécifique à la scolarité
    $('#save-reduction-btn').click(function() {
        if (!currentinscriptionId) {
            toastr.error('Veuillez sélectionner un élève');
            return;
        }

        const reduction = parseFloat($('#reduction_scolarite').val()) || 0;

        // Désactiver le bouton pendant la requête
        const button = $(this);
        button.prop('disabled', true).addClass('reduction-loading');

        $.ajax({
            url: '{{ route("eleves.apply_reduction") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                inscription_id: currentinscriptionId,
                reduction: reduction
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    currentReduction = reduction;
                    loadPaiements(currentinscriptionId);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    toastr.error(xhr.responseJSON.message);
                } else {
                    toastr.error('Erreur lors de l\'application de la réduction');
                }
            },
            complete: function() {
                button.prop('disabled', false).removeClass('reduction-loading');
            }
        });
    });

    // Afficher le modal de suppression
    function showDeleteModal(paiementId) {
        paiementToDelete = paiementId;
        $('#deleteModal').modal('show');
    }

    // Confirmer la suppression
    $('#confirm-delete').click(function() {
        if (!paiementToDelete) return;
        
        $.ajax({
            url: `{{ url('paiements') }}/${paiementToDelete}`,
            type: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    loadPaiements(currentinscriptionId);
                } else {
                    toastr.error(response.message);
                }
                $('#deleteModal').modal('hide');
            },
            error: function() {
                toastr.error('Erreur lors de la suppression');
                $('#deleteModal').modal('hide');
            }
        });
    });

    // Gestion de l'impression
    $('#print-btn').click(function() {
        if (!currentinscriptionId) {
            toastr.error('Veuillez sélectionner un élève');
            return;
        }
        
        window.open(`{{ url('scolarite/print') }}/${currentinscriptionId}/`, '_blank');
    });

    // Générer un reçu
    function generateReceipt(paiementId) {
        window.open(`{{ url('scolarite/receipt') }}/${paiementId}`, '_blank');
    }
});
</script>
@endsection