@extends('dashboard.layouts.master')
@section('content')
<!-- Page Header -->
<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
    <div class="my-auto">
        <h3 class="mb-1">Journal des Paiements</h3>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Tableau de Bord</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Journal des Paiements</li>
            </ol>
        </nav>
    </div>
    <div>
        <button class="btn btn-primary" id="print-btn"><i class="ti ti-printer me-2"></i>Imprimer</button>
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
    <!-- Colonne de gauche - Filtres et formulaire -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-light">
                <h4 class="text-dark">Filtres</h4>
            </div>
            <div class="card-body">
                

                <div class="mb-3">
                    <label class="form-label">Type de Frais</label>
                    <select class="form-select" id="type_frais_id" name="type_frais_id">
                        <option value="">Tous les types</option>
                        @foreach($typesFrais as $typeFrais)
                            <option value="{{ $typeFrais->id }}">{{ $typeFrais->nom }}</option>
                        @endforeach
                    </select>
                </div>

                

                <div class="mb-3">
                    <label class="form-label">Mode de Paiement</label>
                    <select class="form-select" id="mode_paiement" name="mode_paiement">
                        <option value="">Tous les modes</option>
                        <option value="especes">Espèces</option>
                        <option value="cheque">Chèque</option>
                        <option value="virement">Virement</option>
                        <option value="mobile_money">Mobile Money</option>
                    </select>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Date début</label>
                            <input type="date" class="form-control" id="date_debut" name="date_debut">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Date fin</label>
                            <input type="date" class="form-control" id="date_fin" name="date_fin">
                        </div>
                    </div>
                </div>

                <button class="btn btn-primary w-100" id="filter-btn">
                    <i class="ti ti-filter me-2"></i>Filtrer
                </button>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="card mt-3">
            <div class="card-header bg-light">
                <h4 class="text-dark">Statistiques</h4>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Total Paiements</label>
                    <input type="text" class="form-control bg-primary bg-opacity-10 text-primary fw-bold" id="total_paiements" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nombre de Paiements</label>
                    <input type="text" class="form-control bg-info bg-opacity-10 text-info fw-bold" id="nombre_paiements" readonly>
                </div>
            </div>
        </div>
    </div>

    <!-- Colonne de droite - Liste des paiements -->
    <div class="col-md-8">
        <!-- Cartes de statistiques -->
        <div class="row">
            <div class="col-md-6">
                <div class="card card-body bg-primary bg-opacity-10 border-primary">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h2 class="fw-bold mb-0" id="total-paiements-card">0 FCFA</h2>
                            <span>Total Paiements</span>
                        </div>
                        <div class="flex-shrink-0">
                            <i class="ti ti-currency-dollar fs-1 text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card card-body bg-info bg-opacity-10 border-info">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h2 class="fw-bold mb-0" id="nombre-paiements-card">0</h2>
                            <span>Nombre de Paiements</span>
                        </div>
                        <div class="flex-shrink-0">
                            <i class="ti ti-list fs-1 text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Carte des paiements -->
        <div class="card mt-3">
            <div class="card-header bg-light">
                <h4 class="text-dark">Journal des Paiements</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="paiements-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Élève</th>
                                <th>Type</th>
                                <th>Frais</th>
                                <th>Montant</th>
                                <th>Mode</th>
                                <th>Référence</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="8" class="text-center">Veuillez appliquer des filtres pour voir les paiements</td>
                            </tr>
                        </tbody>
                    </table>
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

.badge-inscription {
    background-color: #198754;
}

.badge-scolarite {
    background-color: #0d6efd;
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
    let paiementToDelete = null;

    loadPaiements(); // charger directement les paiements filtrés par type, mode, date


    
    // Charger les paiements selon les filtres
    function loadPaiements() {
        const typeFraisId = $('#type_frais_id').val();
        const modePaiement = $('#mode_paiement').val();
        const dateDebut = $('#date_debut').val();
        const dateFin = $('#date_fin').val();

        console.log("Filtres appliqués:", { typeFraisId, modePaiement, dateDebut, dateFin });

        $.ajax({
            url: '{{ route("journal-paiements.data") }}',
            type: 'GET',
            data: { 
                type_frais_id: typeFraisId,
                mode_paiement: modePaiement,
                date_debut: dateDebut,
                date_fin: dateFin
            },
            beforeSend: function() {
                console.log("Envoi de la requête AJAX...");
                $('#paiements-table tbody').html('<tr><td colspan="8" class="text-center">Chargement en cours...</td></tr>');
            },
            success: function(response) {
                console.log("Réponse AJAX reçue:", response);

                if (response.success) {
                    updatePaiementsTable(response.paiements);
                    updateStats(response.total_paiements, response.nombre_paiements);
                } else {
                    toastr.error(response.message);
                    $('#paiements-table tbody').html('<tr><td colspan="8" class="text-center">Aucun paiement trouvé</td></tr>');
                }
            },
            error: function(xhr) {
                console.error("Erreur AJAX:", xhr.responseText);
                toastr.error('Erreur lors du chargement des données');
                $('#paiements-table tbody').html('<tr><td colspan="8" class="text-center">Erreur de chargement</td></tr>');
            }
        });
    
    }

    // Mettre à jour le tableau des paiements
    function updatePaiementsTable(paiements) {
        let html = '';
        
        if (paiements.length > 0) {
            $.each(paiements, function(index, paiement) {
                const badgeClass = paiement.est_frais_inscription ? 'badge-inscription' : 'badge-scolarite';
                const badgeText = paiement.est_frais_inscription ? 'Inscription' : 'Scolarité';
                const eleveNom = paiement.inscription && paiement.inscription.eleve ? 
                    paiement.inscription.eleve.prenom + ' ' + paiement.inscription.eleve.nom : 'N/A';
                
                html += `
                <tr>
                    <td>${formatDate(paiement.date_paiement)}</td>
                    <td>${eleveNom}</td>
                    <td><span class="badge ${badgeClass}">${badgeText}</span></td>
                    <td>${paiement.type_frais ? paiement.type_frais.nom : 'N/A'}</td>
                    <td class="fw-bold text-success">${formatMoney(paiement.montant)}</td>
                    <td>${formatModePaiement(paiement.mode_paiement)}</td>
                    <td>${paiement.reference || '-'}</td>
                    <td>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-warning btn-edit" data-id="${paiement.id}">
                                <i class="ti ti-edit"></i>
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
            html = '<tr><td colspan="8" class="text-center">Aucun paiement trouvé</td></tr>';
        }
        
        $('#paiements-table tbody').html(html);
        
        // Ajouter les événements aux boutons
        $('.btn-edit').click(function() {
            const paiementId = $(this).data('id');
            editPaiement(paiementId);
        });
        
        $('.btn-delete').click(function() {
            const paiementId = $(this).data('id');
            showDeleteModal(paiementId);
        });
    }

    // Mettre à jour les statistiques
    function updateStats(totalPaiements, nombrePaiements) {
        $('#total_paiements').val(formatMoney(totalPaiements));
        $('#nombre_paiements').val(nombrePaiements);
        
        $('#total-paiements-card').text(formatMoney(totalPaiements));
        $('#nombre-paiements-card').text(nombrePaiements);
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

    // Soumission du formulaire de paiement
    $('#paiement-form').submit(function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        const url = $('#paiement_id').val() ? '{{ route("journal-paiements.update", ":id") }}'.replace(':id', $('#paiement_id').val()) : '{{ route("journal-paiements.store") }}';
        const method = $('#paiement_id').val() ? 'PUT' : 'POST';
        
        $.ajax({
            url: url,
            type: method,
            data: formData,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#paiement-form')[0].reset();
                    $('#date_paiement').val('{{ date('Y-m-d') }}');
                    $('#paiement_id').val('');
                    $('#cancel-edit').hide();
                    loadPaiements();
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    $.each(errors, function(key, value) {
                        toastr.error(value[0]);
                    });
                } else {
                    toastr.error('Une erreur est survenue');
                    console.error(xhr.responseText);
                }
            }
        });
    }

    // Annuler l'édition
    $('#cancel-edit').click(function() {
        $('#paiement-form')[0].reset();
        $('#date_paiement').val('{{ date('Y-m-d') }}');
        $('#paiement_id').val('');
        $(this).hide();
    });

    // Éditer un paiement
    function editPaiement(paiementId) {
        $.ajax({
            url: '{{ url("journal-paiements") }}/' + paiementId,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const paiement = response.paiement;
                    $('#paiement_id').val(paiement.id);
                    $('#est_frais_inscription_select').val(paiement.est_frais_inscription ? '1' : '0');
                    $('#type_frais_id_select').val(paiement.type_frais_id);
                    $('#montant').val(paiement.montant);
                    $('#date_paiement').val(paiement.date_paiement);
                    $('#mode_paiement_select').val(paiement.mode_paiement);
                    $('#beneficiaire').val(paiement.beneficiaire);
                    $('#reference').val(paiement.reference);
                    $('#inscription_id').val(paiement.inscription_id);
                    $('#description').val(paiement.description);
                    $('#cancel-edit').show();
                    
                    // Scroll to form
                    $('html, body').animate({
                        scrollTop: $('#paiement-form').offset().top - 100
                    }, 500);
                }
            },
            error: function() {
                toastr.error('Erreur lors du chargement du paiement');
            }
        });
    }

    // Afficher le modal de suppression
    function showDeleteModal(paiementId) {
        paiementToDelete = paiementId;
        $('#deleteModal').modal('show');
    }

    // Confirmer la suppression
    $('#confirm-delete').click(function() {
        if (!paiementToDelete) return;
        
        $.ajax({
            url: '{{ url("journal-paiements") }}/' + paiementToDelete,
            type: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    loadPaiements();
                } else {
                    toastr.error(response.message);
                }
                $('#deleteModal').modal('hide');
            },
            error: function() {
                toastr.error('Erreur lors de la suppression du paiement');
                $('#deleteModal').modal('hide');
            }
        });
    });

    
});
</script>
@endsection