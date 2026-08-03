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
                            <li><strong>Modèle :</strong> <span id="sms-template-name">-</span></li>
                            <li><strong>Nombre d'élèves :</strong> <span id="sms-count-eleves">0</span></li>
                            <li><strong>Nombre de SMS :</strong> <span id="sms-count-messages">0</span></li>
                            <li><strong>Total caractères :</strong> <span id="sms-total-characters">0</span></li>
                        </ul>
                    </div>
                    
                    <div class="card">
                        <div class="card-header bg-light">
                            <strong>Aperçu du message :</strong>
                            <span class="badge bg-info float-end" id="preview-char-count">0 caractères</span>
                        </div>
                        <div class="card-body">
                            <div id="sms-preview-content" style="white-space: pre-wrap; font-family: 'Courier New', monospace; font-size: 14px; line-height: 1.6; background: #f8f9fa; padding: 15px; border-radius: 5px; min-height: 100px;">
                                ...
                            </div>
                        </div>
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

@section('scripts')
<script>
$(document).ready(function() {
    let relanceData = [];
    let selectedTemplateId = null;
    let smsTemplates = [];

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
    
    const tbody = $('#relance-table tbody');
    tbody.empty();
    
    if (!data.data || data.data.length === 0) {
        tbody.append(`
            <tr>
                <td colspan="8" class="text-center py-4">
                    <i class="ti ti-inbox fs-3 text-muted"></i>
                    <p class="text-muted mt-2">Aucun élève en retard pour ces critères</p>
                </td>
            </tr>
        `);
        $('#relance-results').removeClass('d-none');
        return;
    }
    
    let totalMontantMois = 0;
    let totalCumulAttendu = 0;
    let totalCumulPaye = 0;
    let totalResteMois = 0;
    let totalResteCumul = 0;
    let totalEnRetard = 0;
    
    data.data.forEach(function(eleve, index) {
        // ✅ Convertir correctement les valeurs numériques
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
        
        const statutClass = eleve.statut === 'À jour' ? 'a-jour-badge' : 'retard-badge';
        const eleveNom = eleve.eleve || 'Élève ' + (index + 1);
        
        tbody.append(`
            <tr>
                <td>
                    <input type="checkbox" class="eleve-checkbox" 
                           data-eleve='${JSON.stringify(eleve).replace(/'/g, "&#39;")}'>
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
            <td colspan="2" class="text-end">TOTAL (${data.data.length} élève${data.data.length > 1 ? 's' : ''})</td>
            <td>${formatMoney(totalMontantMois)}</td>
            <td>${formatMoney(totalCumulAttendu)}</td>
            <td class="text-success">${formatMoney(totalCumulPaye)}</td>
            <td class="text-danger">${formatMoney(totalResteMois)}</td>
            <td class="${totalColor}">${formatMoney(totalResteCumul)}</td>
            <td>${totalEnRetard > 0 ? totalEnRetard + ' en retard' : 'Tous à jour'}</td>
        </tr>
    `);
    
    $('#relance-results').removeClass('d-none');
}

    // ============================================
    // 2. SÉLECTION DES ÉLÈVES
    // ============================================
    $('#select-all').change(function() {
        $('.eleve-checkbox').prop('checked', $(this).prop('checked'));
    });

    $(document).on('change', '.eleve-checkbox', function() {
        const total = $('.eleve-checkbox').length;
        const checked = $('.eleve-checkbox:checked').length;
        $('#select-all').prop('checked', total === checked && total > 0);
    });

    // ============================================
    // 3. FORMATAGE DES MONTANTS
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
        }).format(amount) + ' F';
    }

    function getEcoleName() {
        return '{{ session("current_ecole") ? session("current_ecole")->nom_ecole ?? "" : "Ecole" }}';
    }

    // ============================================
    // 4. IMPRESSION
    // ============================================
    $('#print-btn').click(function() {
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

        window.open(url, '_blank');
    });

    // ============================================
    // 5. EXPORTATION
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

        window.location.href = url;
    }

    // ============================================
    // 6. ENVOI DES SMS
    // ============================================
    $('#send-sms-btn').click(function() {
        const selectedEleves = getSelectedEleves();
        if (selectedEleves.length === 0) {
            toastr.warning('Veuillez sélectionner au moins un élève');
            return;
        }

        const sansTelephone = selectedEleves.filter(e => !e.telephone && !e.parent_telephone);
        if (sansTelephone.length > 0) {
            toastr.warning(sansTelephone.length + ' élève(s) n\'ont pas de numéro de téléphone');
        }

        const message = generateDefaultSmsMessage(selectedEleves[0]);
        
        $('#sms-preview-content').text(message);
        $('#preview-char-count').text(message.length + ' caractères');
        $('#sms-count-eleves').text(selectedEleves.length);
        $('#sms-count-messages').text(selectedEleves.length);
        $('#sms-total-characters').text(selectedEleves.length * message.length);
        $('#sms-template-name').text('Message de relance');
        
        $('#smsConfirmModal').modal('show');
    });

    function getSelectedEleves() {
        const eleves = [];
        $('.eleve-checkbox:checked').each(function() {
            try {
                const data = $(this).data('eleve');
                if (data) {
                    if (typeof data === 'string') {
                        try {
                            eleves.push(JSON.parse(data));
                        } catch(e) {
                            eleves.push(data);
                        }
                    } else {
                        eleves.push(data);
                    }
                }
            } catch(e) {
                console.error('Erreur parsing data:', e);
            }
        });
        return eleves;
    }

    function generateDefaultSmsMessage(eleveData) {
        if (!eleveData) return 'Message de relance de paiement.';
        
        const nom = eleveData.eleve || 'Cher parent';
        const montant = formatMoneySms(eleveData.reste_cumul || 0);
        const mois = eleveData.mois_reference || 'ce mois';
        const ecole = getEcoleName();
        
        return `Mme/M. ${nom}, nous vous rappelons que le paiement du mois de ${mois} est en attente. Montant restant : ${montant}. Veuillez régulariser votre situation auprès de ${ecole}. Merci.`;
    }

    // ============================================
    // 7. CONFIRMATION D'ENVOI DES SMS
    // ============================================
    $('#confirm-send-sms').click(function() {
        const selectedEleves = getSelectedEleves();
        
        if (selectedEleves.length === 0) {
            toastr.warning('Aucun élève sélectionné');
            return;
        }

        $('#smsConfirmModal').modal('hide');
        $('#smsProgressModal').modal('show');
        $('#sms-progress-bar').css('width', '0%');
        $('#sms-progress-text').text('Préparation des messages...');
        $('#sms-progress-detail').text('0 / ' + selectedEleves.length);

        let sent = 0;
        let failed = 0;
        const total = selectedEleves.length;

        function sendNextSms(index) {
            if (index >= total) {
                $('#smsProgressModal').modal('hide');
                showSmsResult(sent, failed);
                return;
            }

            const eleve = selectedEleves[index];
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
    // 8. RECHARGE AU CHANGEMENT DE CLASSE
    // ============================================
    $('#classe_id').change(function() {
        $('#relance-results').addClass('d-none');
        $('#no-data').removeClass('d-none');
        $('#result-title').text('');
        $('#result-summary').text('');
    });

    // ============================================
    // 9. INITIALISATION
    // ============================================
    if ($('#classe_id').val() && $('#date_reference').val()) {
        chargerRelance();
    }
});
</script>
@endsection