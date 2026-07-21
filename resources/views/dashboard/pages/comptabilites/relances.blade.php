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
                    <label class="form-label">Classe</label>
                    <select class="form-select" id="classe_id" name="classe_id">
                        <option value="">Sélectionner une classe</option>
                        @foreach($classes as $classe)
                            <option value="{{ $classe->id }}">{{ $classe->nom }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Type</label>
                    <select class="form-select" id="type_frais_id" name="type_frais_id">
                        <option value="">Sélectionner un type de frais</option>
                        @foreach($typeFrais as $type)
                            <option value="{{ $type->id }}">{{ $type->nom }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Mois</label>
                    <select class="form-select" id="date_reference" name="date_reference" required>
                        <option value="" selected>-- Sélectionnez un mois --</option>
                        @foreach($moisScolaires as $mois)
                            <option value="{{ $mois->id }}">{{ $mois->nom }}</option>
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

        <!-- Modèles SMS disponibles -->
        {{-- <div class="card mt-3">
            <div class="card-header bg-light">
                <h4 class="text-dark">📱 Modèles SMS disponibles</h4>
            </div>
            <div class="card-body" style="max-height: 200px; overflow-y: auto;">
                <div id="sms-templates-list">
                    <p class="text-muted text-center">Chargement des modèles...</p>
                </div>
            </div>
        </div> --}}
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
                                    <th><input type="checkbox" id="select-all"></th>
                                    <th>Élève</th>
                                    <th>Classe</th>
                                    <th>Total Attendu</th>
                                    <th>Total Payé</th>
                                    <th>Reste à Payer</th>
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
                    <p class="text-muted mt-2">Veuillez sélectionner une classe, type de frais et cliquer sur "Générer la Relance"</p>
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

.sms-template-item {
    cursor: pointer;
    padding: 8px 12px;
    margin-bottom: 5px;
    background: #fff;
    border-radius: 4px;
    border: 1px solid #ddd;
    transition: all 0.2s ease;
}

.sms-template-item:hover {
    background: #e9ecef;
    border-color: #007bff;
}

.sms-template-item.active {
    background: #d4edda;
    border-color: #28a745;
}

#sms-preview-content {
    white-space: pre-wrap;
    word-wrap: break-word;
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
    // 1. CHARGEMENT DES MODÈLES SMS
    // ============================================
    function loadSmsTemplates() {
        $.ajax({
            url: '{{ route("templates.getActiveSms") }}',
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    smsTemplates = response.data;
                    displaySmsTemplates(smsTemplates);
                } else {
                    $('#sms-templates-list').html(
                        '<p class="text-warning text-center">Aucun modèle SMS actif trouvé</p>'
                    );
                }
            },
            error: function() {
                $('#sms-templates-list').html(
                    '<p class="text-danger text-center">Erreur lors du chargement des modèles</p>'
                );
            }
        });
    }

    function displaySmsTemplates(templates) {
        if (templates.length === 0) {
            $('#sms-templates-list').html(
                '<p class="text-warning text-center">Aucun modèle SMS disponible</p>'
            );
            return;
        }

        let html = '';
        templates.forEach(function(template, index) {
            const activeClass = index === 0 ? 'active' : '';
            html += `
                <div class="sms-template-item ${activeClass}" 
                     data-id="${template.id}" 
                     data-name="${template.nom}"
                     data-content="${encodeURIComponent(template.content)}">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${template.nom}</strong>
                            <br>
                            <small class="text-muted">${template.type_label}</small>
                        </div>
                        ${template.is_default ? '<span class="badge bg-warning">⭐ Défaut</span>' : ''}
                    </div>
                </div>
            `;
        });
        
        $('#sms-templates-list').html(html);

        const firstTemplate = templates[0];
        if (firstTemplate) {
            selectedTemplateId = firstTemplate.id;
            $('#sms-template-name').text(firstTemplate.nom);
        }

        $('.sms-template-item').click(function() {
            $('.sms-template-item').removeClass('active');
            $(this).addClass('active');
            selectedTemplateId = $(this).data('id');
            const name = $(this).data('name');
            $('#sms-template-name').text(name);
            previewSmsMessage();
        });
    }

    // ============================================
    // 2. GÉNÉRATION DE LA RELANCE
    // ============================================
    $('#filter-btn').click(function(e) {
        e.preventDefault();
        chargerRelance();
    });

    function chargerRelance() {
        const classeId = $('#classe_id').val();
        const dateRef = $('#date_reference').val();
        const typeFraisId = $('#type_frais_id').val();
        const montantMin = $('#montant_min').val();
        const montantMax = $('#montant_max').val();
        
        if (montantMin && montantMax && parseFloat(montantMin) > parseFloat(montantMax)) {
            toastr.error('Le montant minimum ne peut pas être supérieur au montant maximum');
            return;
        }
        
        if (!classeId) {
            toastr.error('Veuillez sélectionner une classe');
            return;
        }

        if (!dateRef) {
            toastr.error('Veuillez sélectionner un mois');
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
                type_frais_id: typeFraisId,
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
                toastr.error('Erreur lors du chargement des données');
                $('#no-data').removeClass('d-none');
            }
        });
    }

    function afficherResultats(data) {
        $('#result-title').text(data.classe);
        
        let summaryText = `Relance générée pour la classe ${data.classe} du mois de ${data.mois_reference}`;
        if (data.type_frais_id) {
            const typeFraisName = $('#type_frais_id option:selected').text();
            summaryText += ` - ${typeFraisName}`;
        }
        $('#result-summary').text(summaryText);
        
        const tbody = $('#relance-table tbody');
        tbody.empty();
        
        let totalAttendu = 0;
        let totalPaye = 0;
        let totalReste = 0;
        
        data.data.forEach(function(eleve, index) {
            totalAttendu += eleve.total_attendu || 0;
            totalPaye += eleve.total_paye || 0;
            totalReste += eleve.reste_a_payer || 0;
            
            const statutClass = eleve.statut === 'À jour' ? 'a-jour-badge' : 'retard-badge';
            const eleveNom = eleve.eleve || 'Élève ' + (index + 1);
            
            tbody.append(`
                <tr>
                    <td>
                        <input type="checkbox" class="eleve-checkbox" 
                               data-eleve='${JSON.stringify(eleve).replace(/'/g, "&#39;")}'>
                    </td>
                    <td><div class="fw-semibold">${eleveNom}</div></td>
                    <td>${eleve.classe || ''}</td>
                    <td class="fw-bold">${formatMoney(eleve.total_attendu || 0)}</td>
                    <td class="text-success">${formatMoney(eleve.total_paye || 0)}</td>
                    <td class="text-danger">${formatMoney(eleve.reste_a_payer || 0)}</td>
                    <td><span class="statut-badge ${statutClass}">${eleve.statut || 'En retard'}</span></td>
                </tr>
            `);
        });
        
        if (data.data.length > 0) {
            tbody.append(`
                <tr class="table-active fw-bold">
                    <td colspan="2">TOTAL (${data.data.length} élève${data.data.length > 1 ? 's' : ''})</td>
                    <td></td>
                    <td>${formatMoney(totalAttendu)}</td>
                    <td class="text-success">${formatMoney(totalPaye)}</td>
                    <td class="text-danger">${formatMoney(totalReste)}</td>
                    <td></td>
                </tr>
            `);
        }
        
        $('#relance-results').removeClass('d-none');
    }

    // ============================================
    // 3. SÉLECTION DES ÉLÈVES
    // ============================================
    $('#select-all').change(function() {
        $('.eleve-checkbox').prop('checked', $(this).prop('checked'));
    });

    // ============================================
    // 4. APERÇU DU MESSAGE SMS
    // ============================================
    function previewSmsMessage() {
        const selectedEleves = getSelectedEleves();
        if (selectedEleves.length === 0) {
            $('#sms-preview-content').text('Aucun élève sélectionné');
            $('#preview-char-count').text('0 caractères');
            return;
        }

        const template = getSelectedTemplate();
        if (!template) {
            $('#sms-preview-content').text('Aucun modèle sélectionné');
            return;
        }

        const eleve = selectedEleves[0];
        const message = generateSmsMessage(template.content, eleve);
        
        $('#sms-preview-content').text(message);
        $('#preview-char-count').text(message.length + ' caractères');
        
        const totalMessages = selectedEleves.length;
        const totalChars = totalMessages * message.length;
        $('#sms-count-eleves').text(totalMessages);
        $('#sms-count-messages').text(totalMessages);
        $('#sms-total-characters').text(totalChars);
    }

    function getSelectedEleves() {
        const eleves = [];
        $('.eleve-checkbox:checked').each(function() {
            try {
                const data = $(this).data('eleve');
                if (data) {
                    eleves.push(data);
                }
            } catch(e) {
                console.error('Erreur parsing data:', e);
            }
        });
        return eleves;
    }

    function getSelectedTemplate() {
        const template = smsTemplates.find(t => t.id === selectedTemplateId);
        if (!template) {
            toastr.warning('Veuillez sélectionner un modèle SMS');
            return null;
        }
        return template;
    }

// ============================================
// 5. GÉNÉRATION DU MESSAGE SMS - CORRIGÉE
// ============================================
function generateSmsMessage(templateContent, eleveData) {
    if (!eleveData) {
        return templateContent;
    }
    
    // Récupérer le nom complet de l'élève
    const eleveNomComplet = eleveData.eleve || '';
    const nomParts = eleveNomComplet.split(' ');
    const nom = nomParts[0] || '';
    const prenomComplet = nomParts.slice(1).join(' ') || '';
    
    // Récupérer le nom du parent (SEULEMENT LE PREMIER MOT)
    const parentNomComplet = eleveData.parent_nom || '';
    const parentNomParts = parentNomComplet.split(' ');
    const parentNom = parentNomParts[0] || '';
    // ⚠️ Le reste du nom du parent est IGNORÉ !!!
    
    // Récupérer les montants
    const resteAPayer = parseFloat(eleveData.reste_a_payer) || 0;
    const totalAttendu = parseFloat(eleveData.total_attendu) || 0;
    const totalPaye = parseFloat(eleveData.total_paye) || 0;
    
    // Récupérer les infos du filtre
    const typeFrais = $('#type_frais_id option:selected').text() || 'Scolarité';
    const mois = $('#date_reference option:selected').text() || '';
    
    // Formater les montants
    const montantFormatted = formatMoneySms(resteAPayer);
    const totalFormatted = formatMoneySms(totalAttendu);
    const payeFormatted = formatMoneySms(totalPaye);
    const echeance = getNextPaymentDate();
    
    // Fonction pour construire le message avec un prénom donné
    function buildMessage(prenom) {
        let msg = templateContent;
        const vars = {
            '%NOM%': nom,
            '%PRENOM%': prenom,
            '%CLASSE%': eleveData.classe || '',
            '%RESTE%': montantFormatted,
            '%MONTANT_DU%': montantFormatted,
            '%MONTANT_PAYE%': payeFormatted,
            '%MONTANT_ATTENDU%': totalFormatted,
            '%RESTE_MOIS%': montantFormatted,
            '%RESTE_TOTAL%': montantFormatted,
            '%TYPE_FRAIS%': typeFrais,
            '%MOIS_CONCERNE%': mois,
            '%DATE_ECHEANCE%': echeance,
            // ⚠️ Parent : seulement le premier nom
            '%NOM_RESPONSABLE%': parentNom || nom,
            '%ECOLE%': getEcoleName(),
            '%DATE%': new Date().toLocaleDateString('fr-FR'),
        };
        
        for (const [key, value] of Object.entries(vars)) {
            msg = msg.replace(new RegExp(key, 'g'), value);
        }
        msg = msg.replace(/%[^%]+%/g, '');
        msg = msg.replace(/\s+/g, ' ').trim();
        return msg;
    }
    
    // 1. Essayer avec le prénom complet
    let message = buildMessage(prenomComplet);
    if (message.length <= 160) {
        return message;
    }
    
    // 2. Si trop long, couper le prénom progressivement
    const prenomParts = prenomComplet.trim().split(/\s+/);
    
    // Essayer avec le premier prénom seulement
    if (prenomParts.length > 0) {
        const testMessage = buildMessage(prenomParts[0]);
        if (testMessage.length <= 160) {
            return testMessage;
        }
    }
    
    // 3. Essayer avec une abréviation du prénom (1ère lettre)
    if (prenomParts.length > 0 && prenomParts[0].length > 0) {
        const abbr = prenomParts[0].charAt(0) + '.';
        const testMessage = buildMessage(abbr);
        if (testMessage.length <= 160) {
            return testMessage;
        }
    }
    
    // 4. Essayer sans prénom du tout
    const sansPrenom = buildMessage('');
    if (sansPrenom.length <= 160) {
        return sansPrenom;
    }
    
    // 5. Dernier recours : tronquer le message
    return sansPrenom.substring(0, 157) + '...';
}

    // ============================================
    // FORMATAGE DES MONTANTS POUR SMS
    // ============================================
    function formatMoneySms(amount) {
        if (typeof amount !== 'number') amount = 0;
        return new Intl.NumberFormat('fr-FR', { 
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(amount) + ' F';
    }

    // ============================================
    // FONCTIONS UTILITAIRES
    // ============================================
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

    function getNextPaymentDate() {
        const date = new Date();
        date.setDate(date.getDate() + 5);
        return date.toLocaleDateString('fr-FR');
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

        const template = getSelectedTemplate();
        if (!template) {
            return;
        }

        const firstEleve = selectedEleves[0];
        const message = generateSmsMessage(template.content, firstEleve);
        
        $('#sms-preview-content').text(message);
        $('#preview-char-count').text(message.length + ' caractères');
        $('#sms-count-eleves').text(selectedEleves.length);
        $('#sms-count-messages').text(selectedEleves.length);
        $('#sms-total-characters').text(selectedEleves.length * message.length);
        $('#sms-template-name').text(template.nom);
        
        $('#smsConfirmModal').modal('show');
    });

    // ============================================
    // 7. CONFIRMATION D'ENVOI DES SMS
    // ============================================
    $('#confirm-send-sms').click(function() {
        const selectedEleves = getSelectedEleves();
        const template = getSelectedTemplate();
        
        if (!template || selectedEleves.length === 0) {
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
            const message = generateSmsMessage(template.content, eleve);
            
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
    // 8. IMPRESSION
    // ============================================
    $('#print-btn').click(function() {
        const classeId = $('#classe_id').val();
        const dateRef = $('#date_reference').val();
        const typeFraisId = $('#type_frais_id').val();
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

        if (typeFraisId) {
            url += `&type_frais_id=${typeFraisId}`;
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
    // 9. EXPORTATION
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
        const typeFraisId = $('#type_frais_id').val();
        const montantMin = $('#montant_min').val();
        const montantMax = $('#montant_max').val();

        if (!classeId) {
            toastr.error('Veuillez sélectionner une classe');
            return;
        }

        let url = `/relance/export?classe_id=${classeId}&date_reference=${dateRef}&format=${format}`;
        
        if (typeFraisId) {
            url += `&type_frais_id=${typeFraisId}`;
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
    // 10. INITIALISATION
    // ============================================
    loadSmsTemplates();

    $(document).on('change', '.eleve-checkbox', function() {
        previewSmsMessage();
    });
});
</script>
@endsection