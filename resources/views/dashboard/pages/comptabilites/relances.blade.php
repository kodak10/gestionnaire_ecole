@extends('dashboard.layouts.master')
@section('content')
<!-- Page Header -->
<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
    <div class="my-auto">
        <h3 class="mb-1">Gestion des Règlements et Relances</h3>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Tableau de Bord</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Règlements</li>
            </ol>
        </nav>
    </div>
    <div>
        <button class="btn btn-primary" id="print-btn"><i class="ti ti-printer me-2"></i>Imprimer</button>
        <button class="btn btn-danger" id="relance-btn"><i class="ti ti-alert-circle me-2"></i>Générer Relances</button>
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
    <!-- Colonne de gauche - Filtres -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-light">
                <h4 class="text-dark">Filtres</h4>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Année Scolaire <span class="text-danger">*</span></label>
                    {{-- <select class="form-select" id="annee_scolaire_id" name="annee_scolaire_id" required>
                        @foreach($anneesScolaires as $annee)
                            <option value="{{ $annee->id }}" {{ $annee->active ? 'selected' : '' }}>{{ $annee->nom }}</option>
                        @endforeach
                    </select> --}}
                    <select class="form-select" id="annee_scolaire_id" name="annee_scolaire_id" required>
                        @foreach($anneesScolaires as $annee)
                            <option value="{{ $annee->id }}" {{ $annee->est_active ? 'selected' : '' }}>
                                {{ $annee->annee }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Classe</label>
                    <select class="form-select select2" id="classe_id" name="classe_id">
                        <option value="">Toutes les classes</option>
                        @foreach($classes as $classe)
                            <option value="{{ $classe->id }}">{{ $classe->nom }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Type de Frais</label>
                    <select class="form-select select2" id="type_frais_id" name="type_frais_id">
                        <option value="">Tous les frais</option>
                        @foreach($typesFrais as $type)
                            <option value="{{ $type->id }}">{{ $type->nom }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Mois</label>
                    <select class="form-select select2" id="mois_id" name="mois_id">
                        <option value="">Tous les mois</option>
                        @foreach($moisScolaires as $mois)
                            <option value="{{ $mois->id }}">{{ $mois->nom }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Statut</label>
                    <select class="form-select" id="statut" name="statut">
                        <option value="">Tous</option>
                        <option value="paye">Payé</option>
                        <option value="impaye">Impayé</option>
                        <option value="partiel">Partiel</option>
                    </select>
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
                    <label class="form-label">Total Attendus</label>
                    <input type="text" class="form-control" id="total_attendus" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Total Perçus</label>
                    <input type="text" class="form-control" id="total_percus" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Total Restants</label>
                    <input type="text" class="form-control fw-bold text-danger" id="total_restants" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Taux de Recouvrement</label>
                    <input type="text" class="form-control" id="taux_recouvrement" readonly>
                </div>
            </div>
        </div>
    </div>

    <!-- Colonne de droite - Tableau des Règlements -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-light">
                <h4 class="text-dark">Liste des Règlements</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="reglements-table">
                        <thead>
                            <tr>
                                <th>Élève</th>
                                <th>Classe</th>
                                <th>Type Frais</th>
                                <th>Mois</th>
                                <th>Montant</th>
                                <th>Payé</th>
                                <th>Reste</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Les données seront chargées via AJAX -->
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-center mt-3" id="pagination-links">
                    <!-- Les liens de pagination seront chargés ici -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour enregistrer un paiement -->
<div class="modal fade" id="paiementModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Enregistrer un Paiement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="paiement-form">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="paiement_eleve_id" name="eleve_id">
                    <input type="hidden" id="paiement_type_frais_id" name="type_frais_id">
                    <input type="hidden" id="paiement_mois_id" name="mois_id">
                    <input type="hidden" id="paiement_annee_id" name="annee_scolaire_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Montant <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="montant" name="montant" required min="1">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="date_paiement" name="date_paiement" value="{{ date('Y-m-d') }}" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Mode de Paiement <span class="text-danger">*</span></label>
                        <select class="form-select" id="mode_paiement" name="mode_paiement" required>
                            <option value="especes">Espèces</option>
                            <option value="cheque">Chèque</option>
                            <option value="virement">Virement</option>
                            <option value="mobile_money">Mobile Money</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Référence</label>
                        <input type="text" class="form-control" id="reference" name="reference" placeholder="N° chèque ou transaction">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Remarques</label>
                        <textarea class="form-control" id="remarques" name="remarques" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal pour les relances -->
<div class="modal fade" id="relanceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Générer des Relances</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table" id="relances-table">
                        <thead>
                            <tr>
                                <th width="5%"><input type="checkbox" id="select-all"></th>
                                <th>Élève</th>
                                <th>Classe</th>
                                <th>Type Frais</th>
                                <th>Mois</th>
                                <th>Montant</th>
                                <th>Reste</th>
                                <th>Retard (jours)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Les données seront chargées ici -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="generate-relances">Générer les Relances</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<script>
$(document).ready(function() {
    // Initialisation des select2
    $('.select2').select2({
        placeholder: 'Sélectionner une option',
        allowClear: true
    });

    // Variables globales
    let currentPage = 1;
    let filters = {};

    // Charger les données initiales
    loadReglements();

    // Filtrer les données
    $('#filter-btn').click(function() {
        currentPage = 1;
        updateFilters();
        loadReglements();
    });

    // Mettre à jour les filtres
    function updateFilters() {
        filters = {
            annee_scolaire_id: $('#annee_scolaire_id').val(),
            classe_id: $('#classe_id').val(),
            type_frais_id: $('#type_frais_id').val(),
            mois_id: $('#mois_id').val(),
            statut: $('#statut').val(),
            page: currentPage
        };
    }

    // Charger les règlements
    function loadReglements() {
        $.ajax({
            url: '{{ route("reglements.data") }}',
            type: 'GET',
            data: filters,
            beforeSend: function() {
                $('#reglements-table tbody').html('<tr><td colspan="9" class="text-center">Chargement en cours...</td></tr>');
            },
            success: function(response) {
                if (response.success) {
                    updateStats(response.stats);
                    updateReglementsTable(response.reglements.data);
                    updatePagination(response.reglements);
                } else {
                    toastr.error(response.message);
                    $('#reglements-table tbody').html('<tr><td colspan="9" class="text-center">Aucun règlement trouvé</td></tr>');
                }
            },
            error: function() {
                toastr.error('Erreur lors du chargement des règlements');
                $('#reglements-table tbody').html('<tr><td colspan="9" class="text-center">Erreur de chargement</td></tr>');
            }
        });
    }

    // Mettre à jour les statistiques
    function updateStats(stats) {
        $('#total_attendus').val(formatMoney(stats.total_attendus));
        $('#total_percus').val(formatMoney(stats.total_percus));
        $('#total_restants').val(formatMoney(stats.total_restants));
        
        const taux = stats.total_attendus > 0 
            ? Math.round((stats.total_percus / stats.total_attendus) * 100) 
            : 0;
        $('#taux_recouvrement').val(taux + '%');
    }

    // Mettre à jour le tableau des règlements
    function updateReglementsTable(reglements) {
        let html = '';
        
        if (reglements.length > 0) {
            $.each(reglements, function(index, reglement) {
                const statutClass = getStatutClass(reglement.statut);
                
                html += `
                <tr>
                    <td>${reglement.eleve_nom}</td>
                    <td>${reglement.classe_nom}</td>
                    <td>${reglement.type_frais_nom}</td>
                    <td>${reglement.mois_nom || '-'}</td>
                    <td>${formatMoney(reglement.montant_total)}</td>
                    <td>${formatMoney(reglement.montant_paye)}</td>
                    <td>${formatMoney(reglement.reste)}</td>
                    <td><span class="badge bg-${statutClass}">${reglement.statut}</span></td>
                    <td>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-primary btn-payer" 
                                data-eleve-id="${reglement.eleve_id}"
                                data-type-frais-id="${reglement.type_frais_id}"
                                data-mois-id="${reglement.mois_id || ''}"
                                data-annee-id="${reglement.annee_scolaire_id}"
                                data-reste="${reglement.reste}">
                                <i class="ti ti-coin"></i>
                            </button>
                            <button class="btn btn-sm btn-info btn-historique" 
                                data-eleve-id="${reglement.eleve_id}"
                                data-type-frais-id="${reglement.type_frais_id}"
                                data-mois-id="${reglement.mois_id || ''}">
                                <i class="ti ti-history"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                `;
            });
        } else {
            html = '<tr><td colspan="9" class="text-center">Aucun règlement trouvé</td></tr>';
        }
        
        $('#reglements-table tbody').html(html);
        
        // Ajouter les événements aux boutons
        $('.btn-payer').click(function() {
            const eleveId = $(this).data('eleve-id');
            const typeFraisId = $(this).data('type-frais-id');
            const moisId = $(this).data('mois-id');
            const anneeId = $(this).data('annee-id');
            const reste = $(this).data('reste');
            
            $('#paiement_eleve_id').val(eleveId);
            $('#paiement_type_frais_id').val(typeFraisId);
            $('#paiement_mois_id').val(moisId);
            $('#paiement_annee_id').val(anneeId);
            $('#montant').val(reste).attr('max', reste);
            
            $('#paiementModal').modal('show');
        });
        
        $('.btn-historique').click(function() {
            const eleveId = $(this).data('eleve-id');
            const typeFraisId = $(this).data('type-frais-id');
            const moisId = $(this).data('mois-id');
            
            // Charger l'historique des paiements (à implémenter)
            showHistoriquePaiements(eleveId, typeFraisId, moisId);
        });
    }

    // Mettre à jour la pagination
    function updatePagination(data) {
        let html = '';
        
        if (data.last_page > 1) {
            html += `<ul class="pagination">`;
            
            // Bouton Précédent
            if (data.current_page > 1) {
                html += `<li class="page-item">
                    <a class="page-link" href="#" data-page="${data.current_page - 1}">Précédent</a>
                </li>`;
            }
            
            // Pages
            for (let i = 1; i <= data.last_page; i++) {
                html += `<li class="page-item ${i === data.current_page ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>`;
            }
            
            // Bouton Suivant
            if (data.current_page < data.last_page) {
                html += `<li class="page-item">
                    <a class="page-link" href="#" data-page="${data.current_page + 1}">Suivant</a>
                </li>`;
            }
            
            html += `</ul>`;
        }
        
        $('#pagination-links').html(html);
        
        // Gérer le clic sur les liens de pagination
        $('.page-link').click(function(e) {
            e.preventDefault();
            currentPage = $(this).data('page');
            filters.page = currentPage;
            loadReglements();
        });
    }

    // Obtenir la classe CSS pour le statut
    function getStatutClass(statut) {
        switch (statut.toLowerCase()) {
            case 'payé': return 'success';
            case 'impayé': return 'danger';
            case 'partiel': return 'warning';
            default: return 'secondary';
        }
    }

    // Formater un montant en argent
    function formatMoney(amount) {
        return new Intl.NumberFormat('fr-FR', { 
            style: 'currency', 
            currency: 'XOF',
            minimumFractionDigits: 0
        }).format(amount);
    }

    // Enregistrer un paiement
    $('#paiement-form').submit(function(e) {
        e.preventDefault();
        
        $.ajax({
            url: '',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#paiementModal').modal('hide');
                    loadReglements();
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
                }
            }
        });
    });

    // Gérer le bouton de relance
    $('#relance-btn').click(function() {
        $.ajax({
            url: '{{ route("relances.prepare") }}',
            type: 'GET',
            data: filters,
            beforeSend: function() {
                $('#relances-table tbody').html('<tr><td colspan="8" class="text-center">Chargement en cours...</td></tr>');
            },
            success: function(response) {
                if (response.success && response.relances.length > 0) {
                    updateRelancesTable(response.relances);
                    $('#relanceModal').modal('show');
                } else {
                    toastr.info('Aucune relance à générer avec les filtres actuels');
                }
            },
            error: function() {
                toastr.error('Erreur lors de la préparation des relances');
            }
        });
    });

    // Mettre à jour le tableau des relances
    function updateRelancesTable(relances) {
        let html = '';
        
        $.each(relances, function(index, relance) {
            html += `
            <tr>
                <td><input type="checkbox" class="relance-checkbox" data-id="${relance.id}" checked></td>
                <td>${relance.eleve_nom}</td>
                <td>${relance.classe_nom}</td>
                <td>${relance.type_frais_nom}</td>
                <td>${relance.mois_nom || '-'}</td>
                <td>${formatMoney(relance.montant_total)}</td>
                <td>${formatMoney(relance.reste)}</td>
                <td>${relance.jours_retard}</td>
            </tr>
            `;
        });
        
        $('#relances-table tbody').html(html);
        
        // Gérer la sélection/désélection
        $('#select-all').change(function() {
            $('.relance-checkbox').prop('checked', $(this).is(':checked'));
        });
    }

    // Générer les relances sélectionnées
    $('#generate-relances').click(function() {
        const selected = [];
        $('.relance-checkbox:checked').each(function() {
            selected.push($(this).data('id'));
        });
        
        if (selected.length === 0) {
            toastr.warning('Veuillez sélectionner au moins une relance à générer');
            return;
        }
        
        $.ajax({
            url: '{{ route("relances.generate") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                reglements: selected
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#relanceModal').modal('hide');
                    
                    // Télécharger le PDF si demandé
                    if (response.pdf_url) {
                        window.open(response.pdf_url, '_blank');
                    }
                } else {
                    toastr.error(response.message);
                }
            },
            error: function() {
                toastr.error('Erreur lors de la génération des relances');
            }
        });
    });

    // Afficher l'historique des paiements
    function showHistoriquePaiements(eleveId, typeFraisId, moisId) {
        // Implémentation à compléter
        toastr.info('Fonctionnalité d\'historique à implémenter');
    }

    // Gérer l'impression
    $('#print-btn').click(function() {
        const params = $.param(filters);
        window.open(`{{ url('reglements/print') }}?${params}`, '_blank');
    });
});
</script>

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

.badge {
    font-size: 0.85em;
    font-weight: 500;
}
</style>
@endsection