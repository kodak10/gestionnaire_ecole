@extends('dashboard.layouts.master')
@section('content')
<!-- Page Header -->
<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
    <div class="my-auto">
        <h3 class="mb-1">Gestion des Dépenses</h3>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Tableau de Bord</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Dépenses</li>
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
    <!-- Colonne de gauche - Filtres et formulaire -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-light">
                <h4 class="text-dark">Filtres</h4>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Année Scolaire <span class="text-danger">*</span></label>
                    <select class="form-select" id="annee_scolaire_id" name="annee_scolaire_id" required>
                        @foreach($anneesScolaires as $annee)
                            <option value="{{ $annee->id }}" {{ $annee->est_active ? 'selected' : '' }}>
                                {{ $annee->annee }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Catégorie</label>
                    <select class="form-select" id="depense_category_id" name="depense_category_id">
                        <option value="">Toutes les catégories</option>
                        @foreach($categories as $categorie)
                            <option value="{{ $categorie->id }}">{{ $categorie->nom }}</option>
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

    <!-- Colonne de droite - Liste des dépenses et statistiques -->
    <div class="col-md-8">
        <!-- Cartes de statistiques -->
        <div class="row">
            <div class="col-md-6">
                <div class="card card-body bg-primary bg-opacity-10 border-primary">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h2 class="fw-bold mb-0" id="total-depenses">0 FCFA</h2>
                            <span>Total des dépenses</span>
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
                            <h2 class="fw-bold mb-0" id="nombre-depenses">0</h2>
                            <span>Nombre de dépenses</span>
                        </div>
                        <div class="flex-shrink-0">
                            <i class="ti ti-list fs-1 text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Carte des dépenses -->
        <div class="card mt-3">
            <div class="card-header bg-light">
                <h4 class="text-dark">Liste des Dépenses</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="depenses-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Libellé</th>
                                <th>Catégorie</th>
                                <th>Montant</th>
                                <th>Bénéficiaire</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="6" class="text-center">Veuillez appliquer des filtres pour voir les dépenses</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        

        
        
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <!-- Formulaire d'ajout de dépense -->
        <div class="card mt-3">
            <div class="card-header bg-light">
                <h4 class="text-dark">Nouvelle Dépense</h4>
            </div>
            <div class="card-body">
                <form id="depense-form">
                    @csrf
                    <input type="hidden" id="depense_id" name="id">
                    <input type="hidden" name="annee_scolaire_id" id="form_annee_id" value="{{ $anneesScolaires->where('est_active', true)->first()->id }}">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Libellé <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="libelle" name="libelle" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Montant (FCFA) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="montant" name="montant" required min="1">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="date_depense" name="date_depense" value="{{ date('Y-m-d') }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Catégorie <span class="text-danger">*</span></label>
                            <select class="form-select" id="depense_category_id_select" name="depense_category_id" required>
                                <option value="">Sélectionner une catégorie</option>
                                @foreach($categories as $categorie)
                                    <option value="{{ $categorie->id }}">{{ $categorie->nom }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Mode de paiement <span class="text-danger">*</span></label>
                            <select class="form-select" id="mode_paiement" name="mode_paiement" required>
                                <option value="especes">Espèces</option>
                                <option value="cheque">Chèque</option>
                                <option value="virement">Virement</option>
                                <option value="mobile_money">Mobile Money</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Bénéficiaire <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="beneficiaire" name="beneficiaire" required>
                        </div>

                        

                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                        </div>

                       
                    </div>

                    <div class="text-end mt-3">
                        <button type="button" class="btn btn-secondary" id="cancel-edit" style="display: none;">Annuler</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-check me-2"></i>Enregistrer
                        </button>
                    </div>
                </form>
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
                <p>Êtes-vous sûr de vouloir supprimer cette dépense?</p>
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
</style>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Configuration de toastr
toastr.options = {
    "closeButton": true,
    "progressBar": true,
    "positionClass": "toast-top-right",
    "timeOut": "5000"
};

$(document).ready(function() {
    // Variables globales
    let categorieChart = null;
    let depenseToDelete = null;

    // Appliquer les filtres
    $('#filter-btn').click(function() {
        loadDepenses();
    });

    // Charger les dépenses selon les filtres
    function loadDepenses() {
        const anneeId = $('#annee_scolaire_id').val();
        const categorieId = $('#depense_category_id').val();
        const dateDebut = $('#date_debut').val();
        const dateFin = $('#date_fin').val();
        
        if (!anneeId) {
            toastr.error('Veuillez sélectionner une année scolaire');
            return;
        }
        
        // Mettre à jour l'ID de l'année scolaire dans le formulaire
        $('#form_annee_id').val(anneeId);
        
        $.ajax({
            url: '{{ route("depenses.data") }}',
            type: 'GET',
            data: { 
                annee_scolaire_id: anneeId,
                depense_category_id: categorieId,
                date_debut: dateDebut,
                date_fin: dateFin
            },
            beforeSend: function() {
                $('#depenses-table tbody').html('<tr><td colspan="6" class="text-center">Chargement en cours...</td></tr>');
            },
            success: function(response) {
                if (response.success) {
                    updateDepensesTable(response.depenses);
                    updateStats(response.total_depenses, response.depenses.length);
                    updateChart(response.stats_categories);
                } else {
                    toastr.error(response.message);
                    $('#depenses-table tbody').html('<tr><td colspan="6" class="text-center">Aucune dépense trouvée</td></tr>');
                }
            },
            error: function(xhr) {
                toastr.error('Erreur lors du chargement des données');
                $('#depenses-table tbody').html('<tr><td colspan="6" class="text-center">Erreur de chargement</td></tr>');
                console.error(xhr.responseText);
            }
        });
    }

    // Mettre à jour le tableau des dépenses
    function updateDepensesTable(depenses) {
        let html = '';
        
        if (depenses.length > 0) {
            $.each(depenses, function(index, depense) {
                html += `
                <tr>
                    <td>${formatDate(depense.date_depense)}</td>
                    <td>${depense.libelle}</td>
                    <td><span class="badge bg-primary">${depense.category ? depense.category.nom : 'N/A'}</span></td>
                    <td class="fw-bold">${formatMoney(depense.montant)}</td>
                    <td>${depense.beneficiaire}</td>
                    <td>
                        <div class="d-flex gap-2">
                           
                            <button class="btn btn-sm btn-warning btn-edit" data-id="${depense.id}">
                                <i class="ti ti-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger btn-delete" data-id="${depense.id}">
                                <i class="ti ti-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                `;
            });
        } else {
            html = '<tr><td colspan="6" class="text-center">Aucune dépense trouvée</td></tr>';
        }
        
        $('#depenses-table tbody').html(html);
        
        // Ajouter les événements aux boutons
        $('.btn-view').click(function() {
            const depenseId = $(this).data('id');
            viewDepense(depenseId);
        });
        
        $('.btn-edit').click(function() {
            const depenseId = $(this).data('id');
            editDepense(depenseId);
        });
        
        $('.btn-delete').click(function() {
            const depenseId = $(this).data('id');
            showDeleteModal(depenseId);
        });
    }

    // Mettre à jour les statistiques
    function updateStats(totalDepenses, nombreDepenses) {
        $('#total-depenses').text(formatMoney(totalDepenses));
        $('#nombre-depenses').text(nombreDepenses);
    }

    // Mettre à jour le graphique
    function updateChart(statsCategories) {
        const ctx = document.getElementById('categorieChart').getContext('2d');
        
        // Détruire le graphique existant s'il y en a un
        if (categorieChart) {
            categorieChart.destroy();
        }
        
        if (statsCategories.length === 0) {
            $('#categorieChart').replaceWith('<div class="text-center py-4">Aucune donnée à afficher</div>');
            return;
        }
        
        const labels = statsCategories.map(item => item.categorie);
        const data = statsCategories.map(item => item.total);
        const backgroundColors = [
            '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', 
            '#858796', '#f8f9fc', '#5a5c69', '#2e59d9', '#17a673'
        ];
        
        categorieChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: backgroundColors,
                    hoverBackgroundColor: backgroundColors,
                    hoverBorderColor: "rgba(234, 236, 244, 1)",
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.label}: ${formatMoney(context.raw)}`;
                            }
                        }
                    }
                }
            }
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

    // Soumission du formulaire de dépense
    $('#depense-form').submit(function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        const url = $('#depense_id').val() ? '{{ route("depenses.update", ":id") }}'.replace(':id', $('#depense_id').val()) : '{{ route("depenses.store") }}';
        const method = $('#depense_id').val() ? 'PUT' : 'POST';
        
        $.ajax({
            url: url,
            type: method,
            data: formData,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#depense-form')[0].reset();
                    $('#date_depense').val('{{ date('Y-m-d') }}');
                    $('#depense_id').val('');
                    $('#cancel-edit').hide();
                    loadDepenses();
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
    });

    // Annuler l'édition
    $('#cancel-edit').click(function() {
        $('#depense-form')[0].reset();
        $('#date_depense').val('{{ date('Y-m-d') }}');
        $('#depense_id').val('');
        $(this).hide();
    });

    // Voir les détails d'une dépense
    function viewDepense(depenseId) {
        // Implémenter la vue des détails
        toastr.info('Fonctionnalité de visualisation à implémenter');
    }

    // Éditer une dépense
    function editDepense(depenseId) {
        $.ajax({
            url: '{{ url("depenses") }}/' + depenseId,
            type: 'GET',
            success: function(response) {
                if (response) {
                    $('#depense_id').val(response.id);
                    $('#libelle').val(response.libelle);
                    $('#description').val(response.description);
                    $('#montant').val(response.montant);
                    $('#date_depense').val(response.date_depense);
                    $('#depense_category_id_select').val(response.depense_category_id);
                    $('#mode_paiement').val(response.mode_paiement);
                    $('#beneficiaire').val(response.beneficiaire);
                    $('#reference').val(response.reference);
                    $('#justificatif').val(response.justificatif);
                    $('#cancel-edit').show();
                    
                    // Scroll to form
                    $('html, body').animate({
                        scrollTop: $('#depense-form').offset().top - 100
                    }, 500);
                }
            },
            error: function() {
                toastr.error('Erreur lors du chargement de la dépense');
            }
        });
    }

    // Afficher le modal de suppression
    function showDeleteModal(depenseId) {
        depenseToDelete = depenseId;
        $('#deleteModal').modal('show');
    }

    // Confirmer la suppression
    $('#confirm-delete').click(function() {
        if (!depenseToDelete) return;
        
        $.ajax({
            url: '{{ url("depenses") }}/' + depenseToDelete,
            type: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    loadDepenses();
                } else {
                    toastr.error(response.message);
                }
                $('#deleteModal').modal('hide');
            },
            error: function() {
                toastr.error('Erreur lors de la suppression de la dépense');
                $('#deleteModal').modal('hide');
            }
        });
    });

    // Charger les dépenses au chargement de la page si une année est sélectionnée
    @if($anneesScolaires->where('est_active', true)->first())
        loadDepenses();
    @endif
});
</script>
@endsection