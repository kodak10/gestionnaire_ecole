@extends('dashboard.layouts.master')

@section('content')
<!-- Page Header -->
<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
    <div class="my-auto">
        <h3 class="mb-1">Relance des Paiements</h3>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Tableau de Bord</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Relance des Paiements</li>
            </ol>
        </nav>
    </div>
    
    <div>
        <div class="dropdown me-2 d-inline-block">
            <a href="javascript:void(0);" class="btn btn-light dropdown-toggle" data-bs-toggle="dropdown">
                <i class="ti ti-file-export me-2"></i>Exporter
            </a>
            <ul class="dropdown-menu">
                <li>
                    <a href="#" class="dropdown-item" id="export-pdf">
                        <i class="ti ti-file-type-pdf me-2"></i>PDF
                    </a>
                </li>
                <li>
                    <a href="#" class="dropdown-item" id="export-excel">
                        <i class="ti ti-file-type-xls me-2"></i>Excel
                    </a>
                </li>
            </ul>
        </div>
        <button class="btn btn-primary" id="print-btn"><i class="ti ti-printer me-2"></i>Imprimer les relances papiers</button>
        <button class="btn btn-success" id="send-sms-btn"><i class="ti ti-send me-2"></i>Envoyer les relances par SMS</button>
    </div>
</div>
<!-- /Page Header -->

<div class="row">
    <!-- Filtres -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-light">
                <h4 class="text-dark">Filtres de Relance</h4>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Classe <span class="text-danger">*</span></label>
                    <select class="form-select" id="classe_id" name="classe_id">
                        <option value="">Sélectionner une classe</option>
                        @foreach($classes as $classe)
                            <option value="{{ $classe->id }}">{{ $classe->nom }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Mois <span class="text-danger">*</span></label>
                    <select class="form-select" id="date_reference" name="date_reference">
                        <option value="">-- Sélectionnez un mois --</option>
                        @foreach($moisScolaires as $mois)
                            <option value="{{ $mois->id }}">{{ $mois->nom }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Tarif</label>
                    <select class="form-select" id="tarif_id" name="tarif_id">
                        <option value="">-- Tous les tarifs --</option>
                        @foreach($tarifs as $tarif)
                            <option value="{{ $tarif->id }}">
                                {{ $tarif->typeFrais->nom ?? 'Frais' }} - {{ $tarif->libelle }} 
                                ({{ number_format($tarif->montant, 0, ',', ' ') }} F)
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Filtrer par montant du reste à payer</label>
                    <div class="row g-2">
                        <div class="col-6">
                            <input type="number" 
                                   class="form-control" 
                                   id="montant_min" 
                                   name="montant_min" 
                                   placeholder="Min (XOF)"
                                   min="0"
                                   step="1000">
                        </div>
                        <div class="col-6">
                            <input type="number" 
                                   class="form-control" 
                                   id="montant_max" 
                                   name="montant_max" 
                                   placeholder="Max (XOF)"
                                   min="0"
                                   step="1000">
                        </div>
                    </div>
                    <small class="text-muted">Laissez vide pour ne pas filtrer par montant</small>
                </div>

                <button class="btn btn-primary w-100" id="filter-btn">
                    <i class="ti ti-filter me-2"></i>Générer la Relance
                </button>
            </div>
        </div>
    </div>

    <!-- Résultats -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h4 class="text-dark mb-0">Résultats de la Relance</h4>
                <span id="result-title" class="badge bg-primary"></span>
            </div>
            <div class="card-body">
                <div id="loading" class="text-center d-none">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <p class="mt-2">Chargement des données...</p>
                </div>
                
                <div id="relance-results" class="d-none">
                    <div class="alert alert-info">
                        <i class="ti ti-info-circle me-2"></i>
                        <span id="result-summary"></span>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="relance-table">
                            <thead class="table-light">
                                <tr>
                                    <th width="30"><input type="checkbox" id="select-all"></th>
                                    <th>Élève</th>
                                    <th>Montant Mois</th>
                                    <th>Cumul Attendu</th>
                                    <th>Cumul Payé</th>
                                    <th>Reste Mois</th>
                                    <th>Reste Cumulé</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Les données seront chargées ici par JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="no-data" class="text-center py-5">
                    <i class="ti ti-search fs-1 text-muted"></i>
                    <p class="text-muted mt-2">Veuillez sélectionner une classe, un mois et cliquer sur "Générer la Relance"</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmation envoi SMS -->
<div class="modal fade" id="smsConfirmModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-send text-success"></i> Confirmation envoi SMS
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="sms-preview-container">
                    <div class="alert alert-info">
                        <strong>Résumé de l'envoi :</strong>
                        <ul class="mt-2">
                            <li><strong>Nombre d'élèves :</strong> <span id="sms-count-eleves">0</span></li>
                            <li><strong>Nombre de SMS :</strong> <span id="sms-count-messages">0</span></li>
                        </ul>
                    </div>
                    
                   
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-success" id="confirm-send-sms">
                    <i class="ti ti-send me-2"></i>Envoyer les SMS
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de progression d'envoi -->
<div class="modal fade" id="smsProgressModal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-loader text-primary"></i> Envoi des SMS en cours...
                </h5>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <p class="mt-3" id="sms-progress-text">Préparation des messages...</p>
                    <div class="progress mt-3">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                             id="sms-progress-bar" 
                             role="progressbar" 
                             style="width: 0%">0%</div>
                    </div>
                    <p class="mt-2 text-muted" id="sms-progress-detail">0 / 0</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal résultat d'envoi -->
<div class="modal fade" id="smsResultModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-check-circle text-success"></i> Résultat de l'envoi
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="sms-result-content">
                ...
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('styles')
<style>
.statut-badge {
    font-size: 0.85em;
    padding: 0.35em 0.65em;
}

.retard-badge {
    background-color: #f8d7da;
    color: #721c24;
}

.a-jour-badge {
    background-color: #d1e7dd;
    color: #0f5132;
}

.mois-card {
    border-left: 4px solid #0d6efd;
    margin-bottom: 1rem;
}

.mois-card.retard {
    border-left-color: #dc3545;
}

.mois-card.a-jour {
    border-left-color: #198754;
}

.progress {
    height: 8px;
}

#sms-preview-content {
    white-space: pre-wrap;
    word-wrap: break-word;
}

.table th {
    font-size: 0.85rem;
    white-space: nowrap;
}

.table td {
    font-size: 0.9rem;
    vertical-align: middle;
}
</style>
@endsection

{{-- @section('scripts')
<script>
$(document).ready(function() {
    let relanceData = [];
    let selectedTemplateId = null;
    let smsTemplates = [];
    let allData = [];
    let currentPage = 1;
    let perPage = 50;

    // ============================================
    // 1. GÉNÉRATION DE LA RELANCE
    // ============================================
    $('#filter-btn').click(function(e) {
        e.preventDefault();
        chargerRelance();
    });

    $('#classe_id, #date_reference, #tarif_id, #montant_min, #montant_max').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            chargerRelance();
        }
    });

    function chargerRelance() {
        const classeId = $('#classe_id').val();
        const dateRef = $('#date_reference').val();
        const tarifId = $('#tarif_id').val();
        const montantMin = $('#montant_min').val();
        const montantMax = $('#montant_max').val();
        
        if (montantMin && montantMax && parseFloat(montantMin) > parseFloat(montantMax)) {
            toastr.error('Le montant minimum ne peut pas être supérieur au montant maximum');
            return;
        }
        
        if (!classeId) {
            toastr.error('Veuillez sélectionner une classe');
            $('#classe_id').focus();
            return;
        }

        if (!dateRef) {
            toastr.error('Veuillez sélectionner un mois');
            $('#date_reference').focus();
            return;
        }

        $('#loading').removeClass('d-none');
        $('#relance-results').addClass('d-none');
        $('#no-data').addClass('d-none');

        $.ajax({
            url: '{{ route("relance.data") }}',
            type: 'GET',
            data: { 
                classe_id: classeId,
                date_reference: dateRef,
                tarif_id: tarifId,
                montant_min: montantMin,
                montant_max: montantMax
            },
            success: function(response) {
                $('#loading').addClass('d-none');
                
                if (response.success) {
                    relanceData = response.data;
                    allData = response.data || [];
                    afficherResultats(response);
                } else {
                    toastr.error(response.message);
                    $('#no-data').removeClass('d-none');
                }
            },
            error: function(xhr) {
                $('#loading').addClass('d-none');
                let errorMsg = 'Erreur lors du chargement des données';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                toastr.error(errorMsg);
                $('#no-data').removeClass('d-none');
            }
        });
    }

    function afficherResultats(data) {
        const classeNom = data.classe || 'Classe';
        const moisNom = data.mois_reference || 'Mois';
        const tarifLibelle = data.tarif_libelle || 'Tous les tarifs';
        
        $('#result-title').text(classeNom);
        
        let summaryText = `Relance générée pour la classe ${classeNom} du mois de ${moisNom}`;
        if (data.tarif_id) {
            summaryText += ` - Tarif: ${tarifLibelle}`;
        }
        if (data.montant_min) {
            summaryText += ` - Min: ${formatMoney(parseFloat(data.montant_min))}`;
        }
        if (data.montant_max) {
            summaryText += ` - Max: ${formatMoney(parseFloat(data.montant_max))}`;
        }
        $('#result-summary').text(summaryText);
        
        allData = data.data || [];
        
        if (!allData || allData.length === 0) {
            const tbody = $('#relance-table tbody');
            tbody.empty();
            tbody.append(`
                <tr>
                    <td colspan="8" class="text-center py-4">
                        <i class="ti ti-inbox fs-3 text-muted"></i>
                        <p class="text-muted mt-2">Aucun élève en retard pour ces critères</p>
                    </td>
                </tr>
            `);
            $('#relance-results').removeClass('d-none');
            updateSmsButtonText();
            return;
        }
        
        displayPage(1);
        $('#relance-results').removeClass('d-none');
        updateSmsButtonText();
    }

    // ============================================
    // 2. PAGINATION
    // ============================================
    function displayPage(page) {
        if (perPage === -1) {
            buildTableRows(allData);
            updatePaginationInfo(1, allData.length, allData.length);
            $('#pagination-controls').html('');
            return;
        }
        
        const totalItems = allData.length;
        const totalPages = Math.ceil(totalItems / perPage);
        
        if (page < 1) page = 1;
        if (page > totalPages) page = totalPages;
        currentPage = page;
        
        const start = (page - 1) * perPage;
        const end = Math.min(start + perPage, totalItems);
        const pageData = allData.slice(start, end);
        
        buildTableRows(pageData);
        updatePaginationInfo(start + 1, end, totalItems);
        buildPaginationControls(totalPages, page);
    }

    function buildTableRows(data) {
        const tbody = $('#relance-table tbody');
        tbody.empty();
        
        if (!data || data.length === 0) {
            tbody.append(`
                <tr>
                    <td colspan="8" class="text-center py-4">
                        <i class="ti ti-inbox fs-3 text-muted"></i>
                        <p class="text-muted mt-2">Aucun élève à afficher</p>
                    </td>
                </tr>
            `);
            updateSelectAllState();
            updateSmsButtonText();
            return;
        }
        
        let totalMontantMois = 0;
        let totalCumulAttendu = 0;
        let totalCumulPaye = 0;
        let totalResteMois = 0;
        let totalResteCumul = 0;
        let totalEnRetard = 0;
        
        data.forEach(function(eleve, index) {
            const montantMois = parseFloat(eleve.montant_mois) || 0;
            const cumulAttendu = parseFloat(eleve.cumul_attendu) || 0;
            const totalPaye = parseFloat(eleve.total_paye) || 0;
            const resteMois = parseFloat(eleve.reste_mois) || 0;
            const resteCumul = parseFloat(eleve.reste_cumul) || 0;
            
            totalMontantMois += montantMois;
            totalCumulAttendu += cumulAttendu;
            totalCumulPaye += totalPaye;
            totalResteMois += resteMois;
            totalResteCumul += resteCumul;
            if (eleve.statut === 'En retard') totalEnRetard++;
            
            const statutClass = eleve.statut === 'A jour' ? 'a-jour-badge' : 'retard-badge';
            const eleveNom = eleve.eleve || 'Élève ' + (index + 1);
            
            const eleveJson = JSON.stringify(eleve).replace(/'/g, "&#39;").replace(/"/g, '&quot;');
            
            tbody.append(`
                <tr>
                    <td>
                        <input type="checkbox" class="eleve-checkbox" 
                               data-eleve='${eleveJson}'>
                    </td>
                    <td><div class="fw-semibold">${eleveNom}</div></td>
                    <td class="fw-bold text-primary">${formatMoney(montantMois)}</td>
                    <td>${formatMoney(cumulAttendu)}</td>
                    <td class="text-success">${formatMoney(totalPaye)}</td>
                    <td class="text-danger">${formatMoney(resteMois)}</td>
                    <td class="text-danger fw-bold">${formatMoney(resteCumul)}</td>
                    <td><span class="statut-badge ${statutClass}">${eleve.statut || 'En retard'}</span></td>
                </tr>
            `);
        });
        
        const totalColor = totalResteCumul > 0 ? 'text-danger' : 'text-success';
        tbody.append(`
            <tr class="table-active fw-bold">
                <td colspan="2" class="text-end">TOTAL (${data.length} élève${data.length > 1 ? 's' : ''})</td>
                <td>${formatMoney(totalMontantMois)}</td>
                <td>${formatMoney(totalCumulAttendu)}</td>
                <td class="text-success">${formatMoney(totalCumulPaye)}</td>
                <td class="text-danger">${formatMoney(totalResteMois)}</td>
                <td class="${totalColor}">${formatMoney(totalResteCumul)}</td>
                <td>${totalEnRetard > 0 ? totalEnRetard + ' en retard' : 'Tous à jour'}</td>
            </tr>
        `);
        
        updateSelectAllState();
        updateSmsButtonText();
    }

    function updatePaginationInfo(start, end, total) {
        if (total === 0) {
            $('#pagination-info').text('Aucun résultat');
            return;
        }
        $('#pagination-info').text(`Affichage ${start}-${end} sur ${total}`);
    }

    function buildPaginationControls(totalPages, currentPage) {
        const controls = $('#pagination-controls');
        controls.empty();
        
        if (totalPages <= 1) {
            controls.html('<li class="page-item disabled"><a class="page-link" href="#">1</a></li>');
            return;
        }
        
        controls.append(`
            <li class="page-item ${currentPage <= 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${currentPage - 1}">Précédent</a>
            </li>
        `);
        
        let startPage = Math.max(1, currentPage - 2);
        let endPage = Math.min(totalPages, currentPage + 2);
        
        if (startPage > 1) {
            controls.append(`<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`);
            if (startPage > 2) {
                controls.append(`<li class="page-item disabled"><a class="page-link" href="#">...</a></li>`);
            }
        }
        
        for (let i = startPage; i <= endPage; i++) {
            controls.append(`
                <li class="page-item ${i === currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>
            `);
        }
        
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                controls.append(`<li class="page-item disabled"><a class="page-link" href="#">...</a></li>`);
            }
            controls.append(`<li class="page-item"><a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a></li>`);
        }
        
        controls.append(`
            <li class="page-item ${currentPage >= totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${currentPage + 1}">Suivant</a>
            </li>
        `);
        
        controls.find('a.page-link').click(function(e) {
            e.preventDefault();
            const page = $(this).data('page');
            if (page && page !== 'prev' && page !== 'next') {
                displayPage(parseInt(page));
            }
        });
    }

    // ============================================
    // 3. SÉLECTION DES ÉLÈVES EN RETARD
    // ============================================
    function updateSelectAllState() {
        const retardCheckboxes = $('.eleve-checkbox').filter(function() {
            const data = $(this).data('eleve');
            let statut = '';
            
            if (typeof data === 'string') {
                try {
                    const parsed = JSON.parse(data);
                    statut = parsed.statut || '';
                } catch(e) {
                    statut = '';
                }
            } else if (data) {
                statut = data.statut || '';
            }
            
            return statut === 'En retard';
        });
        
        const retardChecked = retardCheckboxes.filter(':checked').length;
        const totalRetard = retardCheckboxes.length;
        
        $('#select-all').prop('checked', 
            totalRetard > 0 && retardChecked === totalRetard
        );
    }

    function updateSmsButtonText() {
        const selected = $('.eleve-checkbox').filter(function() {
            const data = $(this).data('eleve');
            let statut = '';
            
            if (typeof data === 'string') {
                try {
                    const parsed = JSON.parse(data);
                    statut = parsed.statut || '';
                } catch(e) {
                    statut = '';
                }
            } else if (data) {
                statut = data.statut || '';
            }
            
            return statut === 'En retard' && $(this).is(':checked');
        }).length;
        
        if (selected > 0) {
            $('#send-sms-btn').html(`<i class="ti ti-send me-2"></i>Envoyer les SMS (${selected} élève${selected > 1 ? 's' : ''})`);
        } else {
            $('#send-sms-btn').html(`<i class="ti ti-send me-2"></i>Envoyer les relances par SMS`);
        }
    }

    $('#select-all').change(function() {
        const isChecked = $(this).prop('checked');
        
        $('.eleve-checkbox').each(function() {
            const eleveData = $(this).data('eleve');
            let statut = '';
            
            if (typeof eleveData === 'string') {
                try {
                    const parsed = JSON.parse(eleveData);
                    statut = parsed.statut || '';
                } catch(e) {
                    statut = '';
                }
            } else if (eleveData) {
                statut = eleveData.statut || '';
            }
            
            if (statut === 'En retard') {
                $(this).prop('checked', isChecked);
            } else {
                $(this).prop('checked', false);
            }
        });
        updateSelectAllState();
        updateSmsButtonText();
    });

    $(document).on('change', '.eleve-checkbox', function() {
        const data = $(this).data('eleve');
        let statut = '';
        
        if (typeof data === 'string') {
            try {
                const parsed = JSON.parse(data);
                statut = parsed.statut || '';
            } catch(e) {
                statut = '';
            }
        } else if (data) {
            statut = data.statut || '';
        }
        
        if (statut !== 'En retard' && $(this).is(':checked')) {
            $(this).prop('checked', false);
            toastr.warning('Seuls les élèves en retard peuvent être sélectionnés');
            return;
        }
        
        updateSelectAllState();
        updateSmsButtonText();
    });

    function getSelectedEleves() {
        const eleves = [];
        $('.eleve-checkbox:checked').each(function() {
            try {
                const data = $(this).data('eleve');
                if (data) {
                    let parsedData = data;
                    if (typeof data === 'string') {
                        try {
                            parsedData = JSON.parse(data);
                        } catch(e) {
                            return;
                        }
                    }
                    if (parsedData.statut === 'En retard') {
                        eleves.push(parsedData);
                    }
                }
            } catch(e) {
                console.error('Erreur parsing data:', e);
            }
        });
        return eleves;
    }

    // ============================================
    // 4. FORMATAGE DES MONTANTS
    // ============================================
    function formatMoney(amount) {
        if (typeof amount !== 'number') amount = 0;
        return new Intl.NumberFormat('fr-FR', { 
            style: 'currency', 
            currency: 'XOF',
            minimumFractionDigits: 0
        }).format(amount);
    }

    function formatMoneySms(amount) {
        if (typeof amount !== 'number') amount = 0;
        return new Intl.NumberFormat('fr-FR', { 
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(amount) + ' FCFA';
    }

    function getEcoleName() {
        return '{{ session("current_ecole") ? session("current_ecole")->nom_ecole ?? "" : "Ecole" }}';
    }

    // ============================================
    // 5. IMPRESSION
    // ============================================
    $('#print-btn').click(function() {
        const selectedEleves = getSelectedEleves();
        
        if (selectedEleves.length === 0) {
            toastr.warning('Veuillez sélectionner au moins un élève');
            return;
        }
        
        const classeId = $('#classe_id').val();
        const dateRef = $('#date_reference').val();
        const tarifId = $('#tarif_id').val();
        const montantMin = $('#montant_min').val();
        const montantMax = $('#montant_max').val();
        
        const eleveIds = selectedEleves.map(e => e.id).filter(id => id);
        
        if (!classeId) {
            toastr.error('Veuillez sélectionner une classe');
            return;
        }

        if (!dateRef) {
            toastr.error('Veuillez sélectionner un mois');
            return;
        }

        let url = `/relance/imprimer?classe_id=${classeId}&date_reference=${dateRef}`;

        if (tarifId) {
            url += `&tarif_id=${tarifId}`;
        }
        if (montantMin) {
            url += `&montant_min=${montantMin}`;
        }
        if (montantMax) {
            url += `&montant_max=${montantMax}`;
        }
        
        if (eleveIds.length > 0) {
            url += `&eleve_ids[]=${eleveIds.join('&eleve_ids[]=')}`;
        }

        window.open(url, '_blank');
    });

    // ============================================
    // 6. EXPORTATION
    // ============================================
    $('#export-excel').click(function(e) {
        e.preventDefault();
        exporter('excel');
    });

    $('#export-pdf').click(function(e) {
        e.preventDefault();
        exporter('pdf');
    });

    function exporter(format) {
        const selectedEleves = getSelectedEleves();
        
        if (selectedEleves.length === 0) {
            toastr.warning('Veuillez sélectionner au moins un élève');
            return;
        }
        
        const classeId = $('#classe_id').val();
        const dateRef = $('#date_reference').val();
        const tarifId = $('#tarif_id').val();
        const montantMin = $('#montant_min').val();
        const montantMax = $('#montant_max').val();

        if (!classeId) {
            toastr.error('Veuillez sélectionner une classe');
            return;
        }

        if (!dateRef) {
            toastr.error('Veuillez sélectionner un mois');
            return;
        }

        const eleveIds = selectedEleves.map(e => e.id).filter(id => id);

        let url = `/relance/export?classe_id=${classeId}&date_reference=${dateRef}&format=${format}`;
        
        if (tarifId) {
            url += `&tarif_id=${tarifId}`;
        }
        if (montantMin) {
            url += `&montant_min=${montantMin}`;
        }
        if (montantMax) {
            url += `&montant_max=${montantMax}`;
        }
        
        if (eleveIds.length > 0) {
            url += `&eleve_ids[]=${eleveIds.join('&eleve_ids[]=')}`;
        }

        window.location.href = url;
    }

    // ============================================
    // 7. ENVOI DES SMS
    // ============================================
    $('#send-sms-btn').click(function() {
        const selectedEleves = getSelectedEleves();
        
        if (selectedEleves.length === 0) {
            toastr.warning('Veuillez sélectionner au moins un élève en retard');
            return;
        }

        const elevesEnRetard = selectedEleves.filter(e => e.statut === 'En retard');
        
        if (elevesEnRetard.length === 0) {
            toastr.warning('Veuillez sélectionner des élèves en retard');
            return;
        }

        const sansTelephone = elevesEnRetard.filter(e => !e.telephone && !e.parent_telephone);
        if (sansTelephone.length > 0) {
            toastr.warning(sansTelephone.length + ' élève(s) n\'ont pas de numéro de téléphone');
        }

        const message = generateDefaultSmsMessage(elevesEnRetard[0]);
        
        $('#sms-preview-content').html(`
            <div style="border-left: 4px solid #dc3545; padding-left: 15px; background: #f8f9fa; padding: 15px; border-radius: 5px;">
                ${message.replace(/\n/g, '<br>')}
            </div>
        `);
        $('#preview-char-count').text(message.length + ' caractères');
        $('#sms-count-eleves').text(elevesEnRetard.length);
        $('#sms-count-messages').text(elevesEnRetard.length);
        $('#sms-total-characters').text(elevesEnRetard.length * message.length);
        $('#sms-template-name').text('Message de relance');
        
        $('#smsConfirmModal').modal('show');
    });

    function generateDefaultSmsMessage(eleveData) {
        if (!eleveData) return 'Message de relance de paiement.';
        
        const nom = eleveData.eleve || 'Cher parent';
        const montant = formatMoneySms(eleveData.reste_cumul || 0);
        const mois = eleveData.mois_reference || 'ce mois';
        const ecole = getEcoleName();
        const classe = eleveData.classe || '';
        
        return `📢 RELANCE DE PAIEMENT\n\nMadame/Monsieur ${nom},\n\nNous vous rappelons que le paiement des frais de scolarité pour le mois de ${mois} est en attente.\n\n💰 Montant restant à payer : ${montant}\n🏫 Classe : ${classe}\n\nVeuillez régulariser votre situation auprès de ${ecole} dans les plus brefs délais.\n\nMerci pour votre compréhension.\n\n${ecole}`;
    }

    // ============================================
    // 8. CONFIRMATION D'ENVOI DES SMS
    // ============================================
    $('#confirm-send-sms').click(function() {
        const selectedEleves = getSelectedEleves();
        const elevesEnRetard = selectedEleves.filter(e => e.statut === 'En retard');
        
        if (elevesEnRetard.length === 0) {
            toastr.warning('Aucun élève en retard sélectionné');
            return;
        }

        $('#smsConfirmModal').modal('hide');
        $('#smsProgressModal').modal('show');
        $('#sms-progress-bar').css('width', '0%');
        $('#sms-progress-text').text('Préparation des messages...');
        $('#sms-progress-detail').text('0 / ' + elevesEnRetard.length);

        let sent = 0;
        let failed = 0;
        const total = elevesEnRetard.length;

        function sendNextSms(index) {
            if (index >= total) {
                $('#smsProgressModal').modal('hide');
                showSmsResult(sent, failed);
                return;
            }

            const eleve = elevesEnRetard[index];
            const message = generateDefaultSmsMessage(eleve);
            
            let phone = eleve.parent_telephone || eleve.telephone || '';
            phone = phone.replace(/\s/g, '').replace(/\+/g, '');

            if (!phone || phone.length < 8) {
                failed++;
                const progress = ((sent + failed) / total * 100);
                $('#sms-progress-bar').css('width', progress + '%');
                $('#sms-progress-bar').text(Math.round(progress) + '%');
                $('#sms-progress-detail').text((sent + failed) + ' / ' + total);
                sendNextSms(index + 1);
                return;
            }

            $('#sms-progress-text').text(`Envoi à ${eleve.eleve} (${phone})...`);

            $.ajax({
                url: '{{ route("relance.send.sms") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    phone: phone,
                    message: message,
                    eleve_id: eleve.id || 0
                },
                success: function(response) {
                    if (response.success) {
                        sent++;
                    } else {
                        failed++;
                    }
                },
                error: function() {
                    failed++;
                },
                complete: function() {
                    const progress = ((sent + failed) / total * 100);
                    $('#sms-progress-bar').css('width', progress + '%');
                    $('#sms-progress-bar').text(Math.round(progress) + '%');
                    $('#sms-progress-detail').text((sent + failed) + ' / ' + total);
                    sendNextSms(index + 1);
                }
            });
        }

        sendNextSms(0);
    });

    function showSmsResult(sent, failed) {
        let html = '';
        if (sent > 0 && failed === 0) {
            html = `
                <div class="alert alert-success">
                    <i class="ti ti-check-circle me-2"></i>
                    <strong>${sent} SMS envoyés avec succès !</strong>
                </div>
                <p class="text-muted">Tous les messages ont été envoyés.</p>
            `;
        } else if (sent > 0 && failed > 0) {
            html = `
                <div class="alert alert-warning">
                    <i class="ti ti-alert-circle me-2"></i>
                    <strong>${sent} SMS envoyés, ${failed} échecs</strong>
                </div>
                <p class="text-muted">Vérifiez les numéros de téléphone des élèves concernés.</p>
            `;
        } else {
            html = `
                <div class="alert alert-danger">
                    <i class="ti ti-alert-circle me-2"></i>
                    <strong>Aucun SMS envoyé (${failed} échecs)</strong>
                </div>
                <p class="text-muted">Vérifiez les numéros de téléphone et les crédits SMS.</p>
            `;
        }
        
        $('#sms-result-content').html(html);
        $('#smsResultModal').modal('show');
    }

    // ============================================
    // 9. GESTION DU NOMBRE D'ÉLÉMENTS PAR PAGE
    // ============================================
    $(document).on('change', '#per-page', function() {
        perPage = parseInt($(this).val());
        displayPage(1);
    });

    // ============================================
    // 10. RECHARGE AU CHANGEMENT DE CLASSE
    // ============================================
    $('#classe_id').change(function() {
        $('#relance-results').addClass('d-none');
        $('#no-data').removeClass('d-none');
        $('#result-title').text('');
        $('#result-summary').text('');
        updateSmsButtonText();
    });

    // ============================================
    // 11. INITIALISATION
    // ============================================
    if ($('#classe_id').val() && $('#date_reference').val()) {
        chargerRelance();
    }

    updateSmsButtonText();
});
</script>
@endsection --}}

@section('scripts')
<script>
$(document).ready(function() {
    let relanceData = [];
    let selectedTemplateId = null;
    let smsTemplates = [];
    let allData = [];
    let currentPage = 1;
    let perPage = 50;

    $('#filter-btn').click(function(e) {
        e.preventDefault();
        chargerRelance();
    });

    $('#classe_id, #date_reference, #tarif_id, #montant_min, #montant_max').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            chargerRelance();
        }
    });

    function chargerRelance() {
        const classeId = $('#classe_id').val();
        const dateRef = $('#date_reference').val();
        const tarifId = $('#tarif_id').val();
        const montantMin = $('#montant_min').val();
        const montantMax = $('#montant_max').val();
        
        if (montantMin && montantMax && parseFloat(montantMin) > parseFloat(montantMax)) {
            toastr.error('Le montant minimum ne peut pas être supérieur au montant maximum');
            return;
        }
        
        if (!classeId) {
            toastr.error('Veuillez sélectionner une classe');
            $('#classe_id').focus();
            return;
        }

        if (!dateRef) {
            toastr.error('Veuillez sélectionner un mois');
            $('#date_reference').focus();
            return;
        }

        $('#loading').removeClass('d-none');
        $('#relance-results').addClass('d-none');
        $('#no-data').addClass('d-none');

        $.ajax({
            url: '{{ route("relance.data") }}',
            type: 'GET',
            data: { 
                classe_id: classeId,
                date_reference: dateRef,
                tarif_id: tarifId,
                montant_min: montantMin,
                montant_max: montantMax
            },
            success: function(response) {
                $('#loading').addClass('d-none');
                
                if (response.success) {
                    relanceData = response.data;
                    allData = response.data || [];
                    afficherResultats(response);
                } else {
                    toastr.error(response.message);
                    $('#no-data').removeClass('d-none');
                }
            },
            error: function(xhr) {
                $('#loading').addClass('d-none');
                let errorMsg = 'Erreur lors du chargement des données';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                toastr.error(errorMsg);
                $('#no-data').removeClass('d-none');
            }
        });
    }

    function afficherResultats(data) {
        const classeNom = data.classe || 'Classe';
        const moisNom = data.mois_reference || 'Mois';
        const tarifLibelle = data.tarif_libelle || 'Tous les tarifs';
        
        $('#result-title').text(classeNom);
        
        let summaryText = `Relance générée pour la classe ${classeNom} du mois de ${moisNom}`;
        if (data.tarif_id) {
            summaryText += ` - Tarif: ${tarifLibelle}`;
        }
        if (data.montant_min) {
            summaryText += ` - Min: ${formatMoney(parseFloat(data.montant_min))}`;
        }
        if (data.montant_max) {
            summaryText += ` - Max: ${formatMoney(parseFloat(data.montant_max))}`;
        }
        $('#result-summary').text(summaryText);
        
        allData = data.data || [];
        
        if (!allData || allData.length === 0) {
            const tbody = $('#relance-table tbody');
            tbody.empty();
            tbody.append(`
                <tr>
                    <td colspan="8" class="text-center py-4">
                        <i class="ti ti-inbox fs-3 text-muted"></i>
                        <p class="text-muted mt-2">Aucun élève en retard pour ces critères</p>
                    </td>
                </tr>
            `);
            $('#relance-results').removeClass('d-none');
            updateSmsButtonText();
            return;
        }
        
        displayPage(1);
        $('#relance-results').removeClass('d-none');
        updateSmsButtonText();
    }

    function displayPage(page) {
        if (perPage === -1) {
            buildTableRows(allData);
            updatePaginationInfo(1, allData.length, allData.length);
            $('#pagination-controls').html('');
            return;
        }
        
        const totalItems = allData.length;
        const totalPages = Math.ceil(totalItems / perPage);
        
        if (page < 1) page = 1;
        if (page > totalPages) page = totalPages;
        currentPage = page;
        
        const start = (page - 1) * perPage;
        const end = Math.min(start + perPage, totalItems);
        const pageData = allData.slice(start, end);
        
        buildTableRows(pageData);
        updatePaginationInfo(start + 1, end, totalItems);
        buildPaginationControls(totalPages, page);
    }

    function buildTableRows(data) {
        const tbody = $('#relance-table tbody');
        tbody.empty();
        
        if (!data || data.length === 0) {
            tbody.append(`
                <tr>
                    <td colspan="8" class="text-center py-4">
                        <i class="ti ti-inbox fs-3 text-muted"></i>
                        <p class="text-muted mt-2">Aucun élève à afficher</p>
                    </td>
                </tr>
            `);
            updateSelectAllState();
            updateSmsButtonText();
            return;
        }
        
        let totalMontantMois = 0;
        let totalCumulAttendu = 0;
        let totalCumulPaye = 0;
        let totalResteMois = 0;
        let totalResteCumul = 0;
        let totalEnRetard = 0;
        
        data.forEach(function(eleve, index) {
            const montantMois = parseFloat(eleve.montant_mois) || 0;
            const cumulAttendu = parseFloat(eleve.cumul_attendu) || 0;
            const totalPaye = parseFloat(eleve.total_paye) || 0;
            const resteMois = parseFloat(eleve.reste_mois) || 0;
            const resteCumul = parseFloat(eleve.reste_cumul) || 0;
            
            totalMontantMois += montantMois;
            totalCumulAttendu += cumulAttendu;
            totalCumulPaye += totalPaye;
            totalResteMois += resteMois;
            totalResteCumul += resteCumul;
            if (eleve.statut === 'En retard') totalEnRetard++;
            
            const statutClass = eleve.statut === 'A jour' ? 'a-jour-badge' : 'retard-badge';
            const eleveNom = eleve.eleve || 'Élève ' + (index + 1);
            
            const eleveJson = JSON.stringify(eleve).replace(/'/g, "&#39;").replace(/"/g, '&quot;');
            
            tbody.append(`
                <tr>
                    <td>
                        <input type="checkbox" class="eleve-checkbox" 
                               data-eleve='${eleveJson}'>
                    </td>
                    <td><div class="fw-semibold">${eleveNom}</div></td>
                    <td class="fw-bold text-primary">${formatMoney(montantMois)}</td>
                    <td>${formatMoney(cumulAttendu)}</td>
                    <td class="text-success">${formatMoney(totalPaye)}</td>
                    <td class="text-danger">${formatMoney(resteMois)}</td>
                    <td class="text-danger fw-bold">${formatMoney(resteCumul)}</td>
                    <td><span class="statut-badge ${statutClass}">${eleve.statut || 'En retard'}</span></td>
                </tr>
            `);
        });
        
        const totalColor = totalResteCumul > 0 ? 'text-danger' : 'text-success';
        tbody.append(`
            <tr class="table-active fw-bold">
                <td colspan="2" class="text-end">TOTAL (${data.length} élève${data.length > 1 ? 's' : ''})</td>
                <td>${formatMoney(totalMontantMois)}</td>
                <td>${formatMoney(totalCumulAttendu)}</td>
                <td class="text-success">${formatMoney(totalCumulPaye)}</td>
                <td class="text-danger">${formatMoney(totalResteMois)}</td>
                <td class="${totalColor}">${formatMoney(totalResteCumul)}</td>
                <td>${totalEnRetard > 0 ? totalEnRetard + ' en retard' : 'Tous à jour'}</td>
            </tr>
        `);
        
        updateSelectAllState();
        updateSmsButtonText();
    }

    function updatePaginationInfo(start, end, total) {
        if (total === 0) {
            $('#pagination-info').text('Aucun résultat');
            return;
        }
        $('#pagination-info').text(`Affichage ${start}-${end} sur ${total}`);
    }

    function buildPaginationControls(totalPages, currentPage) {
        const controls = $('#pagination-controls');
        controls.empty();
        
        if (totalPages <= 1) {
            controls.html('<li class="page-item disabled"><a class="page-link" href="#">1</a></li>');
            return;
        }
        
        controls.append(`
            <li class="page-item ${currentPage <= 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${currentPage - 1}">Précédent</a>
            </li>
        `);
        
        let startPage = Math.max(1, currentPage - 2);
        let endPage = Math.min(totalPages, currentPage + 2);
        
        if (startPage > 1) {
            controls.append(`<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`);
            if (startPage > 2) {
                controls.append(`<li class="page-item disabled"><a class="page-link" href="#">...</a></li>`);
            }
        }
        
        for (let i = startPage; i <= endPage; i++) {
            controls.append(`
                <li class="page-item ${i === currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>
            `);
        }
        
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                controls.append(`<li class="page-item disabled"><a class="page-link" href="#">...</a></li>`);
            }
            controls.append(`<li class="page-item"><a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a></li>`);
        }
        
        controls.append(`
            <li class="page-item ${currentPage >= totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${currentPage + 1}">Suivant</a>
            </li>
        `);
        
        controls.find('a.page-link').click(function(e) {
            e.preventDefault();
            const page = $(this).data('page');
            if (page && page !== 'prev' && page !== 'next') {
                displayPage(parseInt(page));
            }
        });
    }

    function updateSelectAllState() {
        const retardCheckboxes = $('.eleve-checkbox').filter(function() {
            const data = $(this).data('eleve');
            let statut = '';
            
            if (typeof data === 'string') {
                try {
                    const parsed = JSON.parse(data);
                    statut = parsed.statut || '';
                } catch(e) {
                    statut = '';
                }
            } else if (data) {
                statut = data.statut || '';
            }
            
            return statut === 'En retard';
        });
        
        const retardChecked = retardCheckboxes.filter(':checked').length;
        const totalRetard = retardCheckboxes.length;
        
        $('#select-all').prop('checked', 
            totalRetard > 0 && retardChecked === totalRetard
        );
    }

    function updateSmsButtonText() {
        const selected = $('.eleve-checkbox').filter(function() {
            const data = $(this).data('eleve');
            let statut = '';
            
            if (typeof data === 'string') {
                try {
                    const parsed = JSON.parse(data);
                    statut = parsed.statut || '';
                } catch(e) {
                    statut = '';
                }
            } else if (data) {
                statut = data.statut || '';
            }
            
            return statut === 'En retard' && $(this).is(':checked');
        }).length;
        
        if (selected > 0) {
            $('#send-sms-btn').html(`<i class="ti ti-send me-2"></i>Envoyer les SMS (${selected} élève${selected > 1 ? 's' : ''})`);
        } else {
            $('#send-sms-btn').html(`<i class="ti ti-send me-2"></i>Envoyer les relances par SMS`);
        }
    }

    $('#select-all').change(function() {
        const isChecked = $(this).prop('checked');
        
        $('.eleve-checkbox').each(function() {
            const eleveData = $(this).data('eleve');
            let statut = '';
            
            if (typeof eleveData === 'string') {
                try {
                    const parsed = JSON.parse(eleveData);
                    statut = parsed.statut || '';
                } catch(e) {
                    statut = '';
                }
            } else if (eleveData) {
                statut = eleveData.statut || '';
            }
            
            if (statut === 'En retard') {
                $(this).prop('checked', isChecked);
            } else {
                $(this).prop('checked', false);
            }
        });
        updateSelectAllState();
        updateSmsButtonText();
    });

    $(document).on('change', '.eleve-checkbox', function() {
        const data = $(this).data('eleve');
        let statut = '';
        
        if (typeof data === 'string') {
            try {
                const parsed = JSON.parse(data);
                statut = parsed.statut || '';
            } catch(e) {
                statut = '';
            }
        } else if (data) {
            statut = data.statut || '';
        }
        
        if (statut !== 'En retard' && $(this).is(':checked')) {
            $(this).prop('checked', false);
            toastr.warning('Seuls les élèves en retard peuvent être sélectionnés');
            return;
        }
        
        updateSelectAllState();
        updateSmsButtonText();
    });

    function getSelectedEleves() {
        const eleves = [];
        $('.eleve-checkbox:checked').each(function() {
            try {
                const data = $(this).data('eleve');
                if (data) {
                    let parsedData = data;
                    if (typeof data === 'string') {
                        try {
                            parsedData = JSON.parse(data);
                        } catch(e) {
                            return;
                        }
                    }
                    if (parsedData.statut === 'En retard') {
                        eleves.push(parsedData);
                    }
                }
            } catch(e) {}
        });
        return eleves;
    }

    function formatMoney(amount) {
        if (typeof amount !== 'number') amount = 0;
        return new Intl.NumberFormat('fr-FR', { 
            style: 'currency', 
            currency: 'XOF',
            minimumFractionDigits: 0
        }).format(amount);
    }

    function getEcoleName() {
        return '{{ session("current_ecole") ? session("current_ecole")->nom_ecole ?? "" : "Ecole" }}';
    }

    $('#print-btn').click(function() {
        const selectedEleves = getSelectedEleves();
        
        if (selectedEleves.length === 0) {
            toastr.warning('Veuillez sélectionner au moins un élève');
            return;
        }
        
        const classeId = $('#classe_id').val();
        const dateRef = $('#date_reference').val();
        const tarifId = $('#tarif_id').val();
        const montantMin = $('#montant_min').val();
        const montantMax = $('#montant_max').val();
        
        const eleveIds = selectedEleves.map(e => e.id).filter(id => id);
        
        if (!classeId) {
            toastr.error('Veuillez sélectionner une classe');
            return;
        }

        if (!dateRef) {
            toastr.error('Veuillez sélectionner un mois');
            return;
        }

        let url = `/relance/imprimer?classe_id=${classeId}&date_reference=${dateRef}`;

        if (tarifId) {
            url += `&tarif_id=${tarifId}`;
        }
        if (montantMin) {
            url += `&montant_min=${montantMin}`;
        }
        if (montantMax) {
            url += `&montant_max=${montantMax}`;
        }
        
        if (eleveIds.length > 0) {
            url += `&eleve_ids[]=${eleveIds.join('&eleve_ids[]=')}`;
        }

        window.open(url, '_blank');
    });

    $('#export-excel').click(function(e) {
        e.preventDefault();
        exporter('excel');
    });

    $('#export-pdf').click(function(e) {
        e.preventDefault();
        exporter('pdf');
    });

    function exporter(format) {
        const selectedEleves = getSelectedEleves();
        
        if (selectedEleves.length === 0) {
            toastr.warning('Veuillez sélectionner au moins un élève');
            return;
        }
        
        const classeId = $('#classe_id').val();
        const dateRef = $('#date_reference').val();
        const tarifId = $('#tarif_id').val();
        const montantMin = $('#montant_min').val();
        const montantMax = $('#montant_max').val();

        if (!classeId) {
            toastr.error('Veuillez sélectionner une classe');
            return;
        }

        if (!dateRef) {
            toastr.error('Veuillez sélectionner un mois');
            return;
        }

        const eleveIds = selectedEleves.map(e => e.id).filter(id => id);

        let url = `/relance/export?classe_id=${classeId}&date_reference=${dateRef}&format=${format}`;
        
        if (tarifId) {
            url += `&tarif_id=${tarifId}`;
        }
        if (montantMin) {
            url += `&montant_min=${montantMin}`;
        }
        if (montantMax) {
            url += `&montant_max=${montantMax}`;
        }
        
        if (eleveIds.length > 0) {
            url += `&eleve_ids[]=${eleveIds.join('&eleve_ids[]=')}`;
        }

        window.location.href = url;
    }

    $('#send-sms-btn').click(function() {
        const selectedEleves = getSelectedEleves();
        
        if (selectedEleves.length === 0) {
            toastr.warning('Veuillez sélectionner au moins un élève en retard');
            return;
        }

        const elevesEnRetard = selectedEleves.filter(e => e.statut === 'En retard');
        
        if (elevesEnRetard.length === 0) {
            toastr.warning('Veuillez sélectionner des élèves en retard');
            return;
        }

        const sansTelephone = elevesEnRetard.filter(e => !e.telephone && !e.parent_telephone);
        if (sansTelephone.length > 0) {
            toastr.warning(sansTelephone.length + ' élève(s) n\'ont pas de numéro de téléphone');
        }

        // Afficher l'aperçu du message (généré côté serveur)
        $('#sms-preview-content').html(`
            <div style="border-left: 4px solid #dc3545; padding-left: 15px; background: #f8f9fa; padding: 15px; border-radius: 5px;">
                <em>Chargement de l'aperçu...</em>
            </div>
        `);
        $('#sms-count-eleves').text(elevesEnRetard.length);
        $('#sms-count-messages').text(elevesEnRetard.length);
        $('#sms-template-name').text('Template base de données');
        
        $('#smsConfirmModal').modal('show');
    });

    $('#confirm-send-sms').click(function() {
        const selectedEleves = getSelectedEleves();
        const elevesEnRetard = selectedEleves.filter(e => e.statut === 'En retard');
        
        if (elevesEnRetard.length === 0) {
            toastr.warning('Aucun élève en retard sélectionné');
            return;
        }

        $('#smsConfirmModal').modal('hide');
        $('#smsProgressModal').modal('show');
        $('#sms-progress-bar').css('width', '0%');
        $('#sms-progress-text').text('Préparation des messages...');
        $('#sms-progress-detail').text('0 / ' + elevesEnRetard.length);

        let sent = 0;
        let failed = 0;
        const total = elevesEnRetard.length;

        function sendNextSms(index) {
            if (index >= total) {
                $('#smsProgressModal').modal('hide');
                showSmsResult(sent, failed);
                return;
            }

            const eleve = elevesEnRetard[index];
            
            let phone = eleve.parent_telephone || eleve.telephone || '';
            phone = phone.replace(/\s/g, '');

            if (!phone || phone.length < 8) {
                failed++;
                const progress = ((sent + failed) / total * 100);
                $('#sms-progress-bar').css('width', progress + '%');
                $('#sms-progress-bar').text(Math.round(progress) + '%');
                $('#sms-progress-detail').text((sent + failed) + ' / ' + total);
                sendNextSms(index + 1);
                return;
            }

            $('#sms-progress-text').text(`Envoi à ${eleve.eleve} (${phone})...`);

            // Envoyer l'ID de l'élève, le téléphone et les données pour générer le message côté serveur
            $.ajax({
                url: '{{ route("relance.send.sms") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    phone: phone,
                    eleve_id: eleve.id || 0,
                    classe: eleve.classe || '',
                    mois: eleve.mois_reference || '',
                    montant: eleve.reste_cumul || 0,
                    eleve_nom: eleve.eleve || '',
                    parent_nom: eleve.parent_nom || ''
                },
                success: function(response) {
                    if (response.success) {
                        sent++;
                    } else {
                        failed++;
                    }
                },
                error: function() {
                    failed++;
                },
                complete: function() {
                    const progress = ((sent + failed) / total * 100);
                    $('#sms-progress-bar').css('width', progress + '%');
                    $('#sms-progress-bar').text(Math.round(progress) + '%');
                    $('#sms-progress-detail').text((sent + failed) + ' / ' + total);
                    sendNextSms(index + 1);
                }
            });
        }

        sendNextSms(0);
    });

    function showSmsResult(sent, failed) {
        let html = '';
        if (sent > 0 && failed === 0) {
            html = `
                <div class="alert alert-success">
                    <i class="ti ti-check-circle me-2"></i>
                    <strong>${sent} SMS envoyés avec succès !</strong>
                </div>
                <p class="text-muted">Tous les messages ont été envoyés.</p>
            `;
        } else if (sent > 0 && failed > 0) {
            html = `
                <div class="alert alert-warning">
                    <i class="ti ti-alert-circle me-2"></i>
                    <strong>${sent} SMS envoyés, ${failed} échecs</strong>
                </div>
                <p class="text-muted">Vérifiez les numéros de téléphone des élèves concernés.</p>
            `;
        } else {
            html = `
                <div class="alert alert-danger">
                    <i class="ti ti-alert-circle me-2"></i>
                    <strong>Aucun SMS envoyé (${failed} échecs)</strong>
                </div>
                <p class="text-muted">Vérifiez les numéros de téléphone et les crédits SMS.</p>
            `;
        }
        
        $('#sms-result-content').html(html);
        $('#smsResultModal').modal('show');
    }

    $(document).on('change', '#per-page', function() {
        perPage = parseInt($(this).val());
        displayPage(1);
    });

    $('#classe_id').change(function() {
        $('#relance-results').addClass('d-none');
        $('#no-data').removeClass('d-none');
        $('#result-title').text('');
        $('#result-summary').text('');
        updateSmsButtonText();
    });

    if ($('#classe_id').val() && $('#date_reference').val()) {
        chargerRelance();
    }

    updateSmsButtonText();
});
</script>
@endsection
