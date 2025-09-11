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
                                <th>Montant</th>
                                <th>Mode de paiement</th>
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

    $('#filter-btn').click(function(e) {
        e.preventDefault();
        loadPaiements();
    });

    // Charger les paiements selon les filtres
    function loadPaiements() {
        const typeFraisId = $('#type_frais_id').val();
        const dateDebut = $('#date_debut').val();
        const dateFin = $('#date_fin').val();

        // Ne rien afficher si tous les filtres sont vides
        if (!typeFraisId && !dateDebut && !dateFin) {
            $('#paiements-table tbody').html('<tr><td colspan="8" class="text-center">Veuillez appliquer des filtres pour voir les paiements</td></tr>');
            updateStats(0, 0);
            return;
        }

        $.ajax({
            url: '{{ route("journal-paiements.data") }}',
            type: 'GET',
            data: { 
                type_frais_id: typeFraisId,
                date_debut: dateDebut,
                date_fin: dateFin
            },
            beforeSend: function() {
                $('#paiements-table tbody').html('<tr><td colspan="8" class="text-center">Chargement en cours...</td></tr>');
            },
            success: function(response) {
                if (response.success) {
                    updatePaiementsTable(response.paiements);
                    updateStats(response.total_paiements, response.nombre_paiements);
                } else {
                    toastr.error(response.message);
                    $('#paiements-table tbody').html('<tr><td colspan="8" class="text-center">Aucun paiement trouvé</td></tr>');
                    updateStats(0, 0);
                }
            },
            error: function(xhr) {
                toastr.error('Erreur lors du chargement des données');
                $('#paiements-table tbody').html('<tr><td colspan="8" class="text-center">Erreur de chargement</td></tr>');
                updateStats(0, 0);
            }
        });
    }

    // Mettre à jour le tableau des paiements
    function updatePaiementsTable(paiements) {
        let html = '';
        
        if (paiements.length > 0) {
            $.each(paiements, function(index, paiement) {
                html += `
                <tr>
                    <td>${formatDate(paiement.date)}</td>
                    <td>${paiement.eleve}</td>
                    <td>${paiement.type_frais}</td>
                    <td class="fw-bold text-success">${formatMoney(paiement.montant)}</td>
                    <td>${formatModePaiement(paiement.mode_paiement)}</td>
                </tr>
                `;
            });
        } else {
            html = '<tr><td colspan="8" class="text-center">Aucun paiement trouvé</td></tr>';
        }
        
        $('#paiements-table tbody').html(html);
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
});
</script>
@endsection