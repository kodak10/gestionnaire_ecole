@extends('dashboard.layouts.master')

@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
    <div class="my-auto mb-2">
        <h3 class="page-title mb-1">Gestion des Notes</h3>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Tableau de bord</a></li>
                <li class="breadcrumb-item active" aria-current="page">Notes</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex my-xl-auto right-content align-items-center flex-wrap">

        <div class="pe-1 mb-2">
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#bulletinAnnuelModal">
                <i class="ti ti-calendar-stats me-2"></i>Bulletin Annuel
            </button>
        </div>

        <div class="pe-1 mb-2">
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#bulletinModal">
                <i class="ti ti-file-spreadsheet me-2"></i>Générer Bulletin
            </button>
        </div>

        <div class="pe-1 mb-2">
            <a href="{{ route('notes.create') }}" class="btn btn-outline-primary">
                <i class="ti ti-file-spreadsheet me-2"></i>Saisie de Notes
            </a>
        </div>
        <div class="pe-1 mb-2">
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#fichesMoyennesModal">
                <i class="ti ti-file-spreadsheet me-2"></i>Impr. fiche de Notes
            </button>
        </div>
        <div class="pe-1 mb-2">
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalRecapMoyenne">
                <i class="ti ti-file-spreadsheet me-2"></i>Impr. récap des moyennes
            </button>
        </div>
    </div>
</div>

<!-- Filter -->
<div class="bg-white p-3 border rounded-1 d-flex align-items-center justify-content-between flex-wrap mb-4 pb-0">
    <h4 class="mb-3">Liste des Notes</h4>
    <div class="d-flex align-items-center flex-wrap">		
        <form method="GET" action="{{ route('notes.index') }}" class="d-flex flex-wrap">
            <div class="input-group mb-3 me-2" style="width: 200px;">
                <input type="text" name="nom" class="form-control" placeholder="Nom élève..." value="{{ request('nom') }}">
                <button class="btn btn-primary" type="submit"><i class="ti ti-search"></i></button>
            </div>
            
            <div class="dropdown mb-3 me-2">
                <a href="javascript:void(0);" class="btn btn-outline-light bg-white dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                    <i class="ti ti-filter me-2"></i>Filtrer
                </a>
                <div class="dropdown-menu drop-width p-3">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Classe</label>
                                <select class="form-select" name="classe_id">
                                    <option value="">Toutes</option>
                                    @foreach($classes as $classe)
                                        <option value="{{ $classe->id }}" {{ request('classe_id') == $classe->id ? 'selected' : '' }}>{{ $classe->nom }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Matière</label>
                                <select name="matiere_id" class="form-select">
                                    <option value="">Toutes</option>
                                    @foreach($matieres as $matiere)
                                        <option value="{{ $matiere->id }}" {{ request('matiere_id') == $matiere->id ? 'selected' : '' }}>{{ $matiere->nom }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Mois</label>
                                <select name="mois_id" class="form-select">
                                    <option value="">Tous</option>
                                    @foreach($moisScolaire as $mois)
                                        <option value="{{ $mois->id }}" {{ request('mois_id') == $mois->id ? 'selected' : '' }}>{{ $mois->nom }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end">
                        <a href="{{ route('notes.index') }}" class="btn btn-light me-3">Réinitialiser</a>
                        <button type="submit" class="btn btn-primary">Appliquer</button>
                    </div>
                </div>
            </div>
        </form>
        
        <div class="dropdown mb-3">
            <a href="javascript:void(0);" class="btn btn-outline-light bg-white dropdown-toggle" data-bs-toggle="dropdown">
                <i class="ti ti-sort-ascending-2 me-2"></i>Trier par 
            </a>
            <ul class="dropdown-menu p-3">
                <li>
                    <a href="{{ route('notes.index', array_merge(request()->query(), ['sort_by' => 'valeur', 'sort' => 'desc'])) }}" 
                       class="dropdown-item rounded-1 {{ request('sort_by') == 'valeur' && request('sort') == 'desc' ? 'active' : '' }}">
                       Note (plus haute)
                    </a>
                </li>
                <li>
                    <a href="{{ route('notes.index', array_merge(request()->query(), ['sort_by' => 'valeur', 'sort' => 'asc'])) }}" 
                       class="dropdown-item rounded-1 {{ request('sort_by') == 'valeur' && request('sort') == 'asc' ? 'active' : '' }}">
                       Note (plus basse)
                    </a>
                </li>
                <li>
                    <a href="{{ route('notes.index', array_merge(request()->query(), ['sort_by' => 'created_at', 'sort' => 'desc'])) }}" 
                       class="dropdown-item rounded-1 {{ request('sort_by') == 'created_at' && request('sort') == 'desc' ? 'active' : '' }}">
                       Récemment ajoutés
                    </a>
                </li>
            </ul>
        </div>
    </div>	
</div>
<!-- /Filter -->

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-center mb-0">
                <thead>
                    <tr>
                        <th>Élève</th>
                        <th>Matière</th>
                        <th>Classe</th>
                        <th>Note</th>
                        <th>Coeff</th>
                        <th>Mois</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($notes as $note)
                    <tr>
                        <td>{{ $note->inscription->eleve->nom }} {{ $note->inscription->eleve->prenom }}</td>
                        <td>{{ $note->matiere->nom }}</td>
                        <td>{{ $note->classe->nom }}</td>
                        <td>
                            <span class="fw-bold {{ $note->valeur < 10 ? 'text-danger' : 'text-success' }}">
                                {{ number_format($note->valeur, 2) }}
                            </span>
                        </td>
                        <td>{{ $note->coefficient }}</td>
                        <td>{{ $note->mois->nom }}</td>
                        
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center">Aucune note trouvée</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="col-md-12 text-center mt-4">
    {{ $notes->appends(request()->query())->links() }}
</div>

<!-- Modal pour générer bulletin annuel avec sélection des mois -->
<div class="modal fade" id="bulletinAnnuelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('notes.generateBulletinAnnuel') }}" method="GET" target="_blank">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Générer le Bulletin Annuel</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Classe <span class="text-danger">*</span></label>
                        <select name="classe_id" class="form-select select2" required>
                            <option value="">Sélectionner une classe</option>
                            @foreach($classes as $classe)
                                <option value="{{ $classe->id }}">{{ $classe->nom }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Mois / Périodes à inclure <span class="text-danger">*</span></label>
                        <div class="alert alert-info">
                            <i class="ti ti-info-circle"></i> 
                            Sélectionnez les mois à inclure dans le calcul de la moyenne annuelle
                        </div>
                        
                        <select name="mois_ids[]" id="mois_select2" class="form-select select2" multiple="multiple" required>
                            @foreach($moisScolaire as $mois)
                                <option value="{{ $mois->id }}">{{ $mois->nom }}</option>
                            @endforeach
                        </select>
                        
                        <small class="text-muted">Vous pouvez sélectionner plusieurs mois</small>
                    </div>
                    
                    <!-- Case à cocher pour l'enregistrement et clôture -->
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="save_and_close" name="save_and_close" value="1">
                            <label class="form-check-label" for="save_and_close">
                                <strong>Enregistrer et clôturer le bulletin</strong>
                            </label>
                            <div class="form-text">
                                Cochez cette case pour enregistrer les moyennes dans la base de données et clôturer le bulletin.
                                Si non coché, le bulletin sera uniquement généré (PDF) sans enregistrement.
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section des appréciations individuelles (affichée uniquement si case cochée) -->
                    <div class="mb-3" id="appreciations_section" style="display: none;">
                        <label class="form-label">Appréciations individuelles (élève par élève)</label>
                        <div class="alert alert-warning">
                            <i class="ti ti-alert-triangle"></i>
                            Ces appréciations seront ajoutées au bulletin de chaque élève
                        </div>
                        <div id="appreciations_container">
                            <p class="text-muted">Sélectionnez d'abord une classe pour voir les élèves</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="generate_btn">
                        <i class="ti ti-file-spreadsheet me-2"></i>Générer le bulletin
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal pour générer bulletin -->
<div class="modal fade" id="bulletinModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">  <!-- Changement ici : ajout de modal-lg -->
        <form action="{{ route('notes.generateBulletin') }}" method="GET" target="_blank">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Générer un Bulletin Mensuel</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Classe <span class="text-danger">*</span></label>
                                <select name="classe_id" class="form-select select2" required>
                                    <option value="">Sélectionner une classe</option>
                                    @foreach($classes as $classe)
                                        <option value="{{ $classe->id }}">{{ $classe->nom }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Mois <span class="text-danger">*</span></label>
                                <select name="mois_id" class="form-select select2" required>
                                    <option value="">Sélectionner un mois</option>
                                    @foreach($moisScolaire as $mois)
                                        <option value="{{ $mois->id }}">{{ $mois->nom }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Case à cocher pour l'enregistrement et clôture -->
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="save_mois" name="save_mois" value="1">
                            <label class="form-check-label" for="save_mois">
                                <strong>Enregistrer et clôturer le bulletin mensuel</strong>
                            </label>
                            <div class="form-text">
                                Cochez cette case pour enregistrer les moyennes du mois dans la base de données.
                                Si non coché, le bulletin sera uniquement généré (PDF) sans enregistrement.
                            </div>
                        </div>
                    </div>
                    
                    <!-- Message d'avertissement si des données existent déjà -->
                    <div class="mb-3" id="existingDataWarning" style="display: none;">
                        <div class="alert alert-warning">
                            <i class="ti ti-alert-triangle"></i>
                            <strong>Attention :</strong> Des moyennes existent déjà pour cette classe et ce mois.
                            Cochez la case ci-dessus pour les remplacer.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="generateMoisBtn">
                        <i class="ti ti-file-spreadsheet me-2"></i>Générer le bulletin
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal pour générer les fiches de notes -->
<div class="modal fade" id="fichesMoyennesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('notes.generateFichesMoyennes') }}" method="GET" target="_blank">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Générer la Fiche de Notes</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Classe</label>
                        <select name="classe_id" class="form-select" required>
                            <option value="">Choisir une classe</option>
                            @foreach($classes as $classe)
                                <option value="{{ $classe->id }}">{{ $classe->nom }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mois</label>
                        <select name="mois_id" class="form-select" required>
                            <option value="">Choisir un mois</option>
                            @foreach($moisScolaire as $mois)
                                <option value="{{ $mois->id }}">{{ $mois->nom }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Générer</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal pour générer les fiches de recap -->
<div class="modal fade" id="modalRecapMoyenne" tabindex="-1" aria-labelledby="modalRecapMoyenneLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalRecapMoyenneLabel">Impression du récapitulatif des moyennes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <form id="formRecapMoyenne" action="{{ route('notes.recap.pdf') }}" method="GET" target="_blank">
                    <div class="mb-3">
                        <label class="form-label">Type de récapitulatif <span class="text-danger">*</span></label>
                        <select name="type" id="recap_type" class="form-select" required>
                            <option value="">Sélectionner...</option>
                            <option value="mensuel">Récapitulatif mensuel</option>
                            <option value="annuel">Récapitulatif annuel</option>
                        </select>
                    </div>
                    
                    <!-- Champs pour le récapitulatif mensuel (toujours visibles) -->
                    <div id="mensuel_fields">
                        <div class="mb-3">
                            <label class="form-label">Classe <span class="text-danger">*</span></label>
                            <select name="classe_id" class="form-select select2">
                                <option value="">Sélectionner une classe</option>
                                @foreach($classes as $classe)
                                    <option value="{{ $classe->id }}">{{ $classe->nom }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mois <span class="text-danger">*</span></label>
                            <select name="mois_id" class="form-select select2">
                                <option value="">Sélectionner un mois</option>
                                @foreach($moisScolaire as $mois)
                                    <option value="{{ $mois->id }}">{{ $mois->nom }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    
                    <!-- Message pour le récapitulatif annuel -->
                    <div id="annuel_message" class="alert alert-info" style="display: none;">
                        <i class="ti ti-info-circle"></i>
                        Le récapitulatif annuel affichera toutes les classes ayant des bulletins annuels enregistrés. Les champs ci-dessus ne sont pas nécessaires.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" form="formRecapMoyenne" class="btn btn-primary">
                    <i class="ti ti-printer me-2"></i>Imprimer
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Select2 CSS et JS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

<script>
    $(document).ready(function() {
        // Initialiser Select2 pour tous les champs
        $('.select2').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Sélectionnez...',
            allowClear: true
        });
        
        $('#mois_select2').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Sélectionnez un ou plusieurs mois',
            allowClear: true
        });
        
        // ==================== BULLETIN ANNUEL ====================
        $('#save_and_close').on('change', function() {
            if($(this).is(':checked')) {
                $('#appreciations_section').show();
                chargerAppreciations();
            } else {
                $('#appreciations_section').hide();
            }
        });
        
        $('select[name="classe_id"]').on('change', function() {
            if($('#save_and_close').is(':checked')) {
                chargerAppreciations();
            }
        });
        
        function chargerAppreciations() {
            var classeId = $('select[name="classe_id"]').val();
            if(!classeId) {
                $('#appreciations_container').html('<p class="text-muted">Sélectionnez d\'abord une classe</p>');
                return;
            }
            
            $('#appreciations_container').html('<p class="text-muted">Chargement des élèves...</p>');
            
            $.ajax({
                url: '{{ route("notes.inscriptions_by_classe") }}',
                type: 'GET',
                data: { classe_id: classeId },
                success: function(response) {
                    if(response.length > 0) {
                        var html = '<div class="table-responsive" style="max-height: 400px; overflow-y: auto;">';
                        html += '<table class="table table-sm table-bordered">';
                        html += '<thead><tr><th>Élève</th><th>Appréciation individuelle</th></tr></thead>';
                        html += '<tbody>';
                        
                        response.forEach(function(eleve) {
                            html += '<tr>';
                            html += '<td>' + eleve.nom_complet + '</td>';
                            html += '<td>';
                            html += '<textarea name="appreciations[' + eleve.id + ']" class="form-control" rows="2" placeholder="Appréciation pour cet élève..."></textarea>';
                            html += '</td>';
                            html += '</tr>';
                        });
                        
                        html += '</tbody></table></div>';
                        html += '<small class="text-muted">Ces appréciations seront affichées dans la section "Appréciation du conseil de classe" du bulletin.</small>';
                        
                        $('#appreciations_container').html(html);
                    } else {
                        $('#appreciations_container').html('<p class="text-warning">Aucun élève trouvé dans cette classe</p>');
                    }
                },
                error: function() {
                    $('#appreciations_container').html('<p class="text-danger">Erreur lors du chargement des élèves</p>');
                }
            });
        }
        
        $('form[action*="generateBulletinAnnuel"]').on('submit', function(e) {
            var classeId = $('select[name="classe_id"]').val();
            var moisCount = $('select[name="mois_ids[]"]').val() ? $('select[name="mois_ids[]"]').val().length : 0;
            
            if (!classeId) {
                e.preventDefault();
                toastr.error("Veuillez sélectionner une classe");
                return false;
            }
            
            if (moisCount === 0) {
                e.preventDefault();
                toastr.error("Veuillez sélectionner au moins un mois pour le bulletin annuel");
                return false;
            }
            
            var saveAndClose = $('#save_and_close').is(':checked');
            if(saveAndClose) {
                toastr.success("Génération et enregistrement du bulletin annuel en cours...");
            } else {
                toastr.success("Génération du bulletin annuel en cours...");
            }
        });
        
        // ==================== BULLETIN MENSUEL ====================
        var classeIdMois = null;
        var moisId = null;
        
        var $classeSelect = $('form[action*="generateBulletin"] select[name="classe_id"]');
        var $moisSelect = $('form[action*="generateBulletin"] select[name="mois_id"]');
        var $saveMoisCheckbox = $('#save_mois');
        var $existingDataWarning = $('#existingDataWarning');
        var $generateMoisBtn = $('#generateMoisBtn');
        
        $classeSelect.on('change', function() {
            classeIdMois = $(this).val();
            checkExistingMoisData();
        });
        
        $moisSelect.on('change', function() {
            moisId = $(this).val();
            checkExistingMoisData();
        });
        
        function checkExistingMoisData() {
            if (!classeIdMois || !moisId) {
                $existingDataWarning.hide();
                $saveMoisCheckbox.prop('disabled', false);
                $generateMoisBtn.prop('disabled', false);
                return;
            }
            
            $.ajax({
                url: '{{ route("notes.checkExistingMoisMoyenne") }}',
                type: 'GET',
                data: { 
                    classe_id: classeIdMois, 
                    mois_id: moisId 
                },
                success: function(response) {
                    if (response.exists) {
                        $existingDataWarning.show();
                        if (!response.can_modify) {
                            $saveMoisCheckbox.prop('disabled', true);
                            $saveMoisCheckbox.prop('checked', false);
                            $generateMoisBtn.prop('disabled', true);
                            $existingDataWarning.html('<div class="alert alert-danger mb-0"><i class="ti ti-ban"></i> <strong>Impossible :</strong> Des moyennes existent déjà pour cette classe et ce mois et sont verrouillées. Contactez l\'administrateur.</div>');
                        } else {
                            $saveMoisCheckbox.prop('disabled', false);
                            $generateMoisBtn.prop('disabled', false);
                            $existingDataWarning.html('<div class="alert alert-warning mb-0"><i class="ti ti-alert-triangle"></i> <strong>Attention :</strong> Des moyennes existent déjà pour cette classe et ce mois. Cochez la case ci-dessus pour les remplacer.</div>');
                        }
                    } else {
                        $existingDataWarning.hide();
                        $saveMoisCheckbox.prop('disabled', false);
                        $generateMoisBtn.prop('disabled', false);
                    }
                },
                error: function() {
                    $existingDataWarning.hide();
                    $saveMoisCheckbox.prop('disabled', false);
                    $generateMoisBtn.prop('disabled', false);
                }
            });
        }
        
        $('form[action*="generateBulletin"]').on('submit', function(e) {
            var classeId = $classeSelect.val();
            var moisIdVal = $moisSelect.val();
            var saveMois = $saveMoisCheckbox.is(':checked');
            
            if (!classeId) {
                e.preventDefault();
                toastr.error("Veuillez sélectionner une classe");
                return false;
            }
            
            if (!moisIdVal) {
                e.preventDefault();
                toastr.error("Veuillez sélectionner un mois");
                return false;
            }
            
            $.ajax({
                url: '{{ route("notes.checkExistingMoisMoyenne") }}',
                type: 'GET',
                async: false,
                data: { 
                    classe_id: classeId, 
                    mois_id: moisIdVal 
                },
                success: function(response) {
                    if (response.exists && !saveMois) {
                        e.preventDefault();
                        toastr.warning("Des moyennes existent déjà pour cette classe et ce mois. Cochez 'Enregistrer et clôturer' pour les remplacer.");
                        return false;
                    }
                }
            });
            
            if (saveMois) {
                toastr.success("Génération et enregistrement du bulletin mensuel en cours...");
            } else {
                toastr.success("Génération du bulletin mensuel en cours...");
            }
        });
        
        // ==================== RÉCAPITULATIF DES MOYENNES ====================
        // Gestion de l'affichage selon le type
        $('#recap_type').on('change', function() {
            var type = $(this).val();
            if (type === 'annuel') {
                $('#mensuel_fields').hide();
                $('#annuel_message').show();
                // Désactiver les champs pour l'annuel
                $('select[name="classe_id"]').prop('required', false);
                $('select[name="mois_id"]').prop('required', false);
            } else {
                $('#mensuel_fields').show();
                $('#annuel_message').hide();
                // Activer les champs pour le mensuel
                if (type === 'mensuel') {
                    $('select[name="classe_id"]').prop('required', true);
                    $('select[name="mois_id"]').prop('required', true);
                } else {
                    $('select[name="classe_id"]').prop('required', false);
                    $('select[name="mois_id"]').prop('required', false);
                }
            }
        });
        
        // Validation du formulaire
        $('form[id="formRecapMoyenne"]').on('submit', function(e) {
            var type = $('#recap_type').val();
            
            if (!type) {
                e.preventDefault();
                toastr.error("Veuillez sélectionner le type de récapitulatif");
                return false;
            }
            
            if (type === 'mensuel') {
                var classeId = $('select[name="classe_id"]').val();
                var moisId = $('select[name="mois_id"]').val();
                
                if (!classeId) {
                    e.preventDefault();
                    toastr.error("Veuillez sélectionner une classe");
                    return false;
                }
                
                if (!moisId) {
                    e.preventDefault();
                    toastr.error("Veuillez sélectionner un mois");
                    return false;
                }
                toastr.success("Génération du récapitulatif mensuel en cours...");
            } else {
                toastr.success("Génération du récapitulatif annuel en cours...");
            }
        });
        
        // ==================== AUTRES MODALS ====================
        $('form[action*="generateFichesMoyennes"]').on('submit', function(e) {
            var classeId = $('select[name="classe_id"]').val();
            var moisId = $('select[name="mois_id"]').val();
            
            if (!classeId) {
                e.preventDefault();
                toastr.error("Veuillez sélectionner une classe");
                return false;
            }
            
            if (!moisId) {
                e.preventDefault();
                toastr.error("Veuillez sélectionner un mois");
                return false;
            }
            
            toastr.success("Génération de la fiche de notes en cours...");
        });
    });
</script>
@endpush