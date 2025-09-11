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
        <button class="btn btn-primary" id="print-btn"><i class="ti ti-printer me-2"></i>Imprimer</button>
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
                    <select class="form-select" id="date_reference" name="date_reference">
                        @foreach($moisScolaires as $mois)
                            <option value="{{ $mois->id }}">{{ $mois->nom }}</option>
                        @endforeach
                    </select>
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
                                    <th>Élève</th>
                                    <th>Classe</th>
                                    <th>Total Attendu</th>
                                    <th>Total Payé</th>
                                    <th>Reste à Payer</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Les données seront chargées ici par JavaScript -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Détails par mois -->
                    <div class="mt-4" id="details-container">
                        <h5 class="mb-3">Détails mois par mois</h5>
                        <div id="mois-details" class="row">
                            <!-- Les détails seront chargés ici par JavaScript -->
                        </div>
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

<!-- Modal Détails Élève -->
<div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Détails des paiements - <span id="eleve-name"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card bg-primary bg-opacity-10">
                            <div class="card-body text-center">
                                <h6>Total Attendu</h6>
                                <h4 id="modal-total-attendu" class="text-primary">0 FCFA</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-success bg-opacity-10">
                            <div class="card-body text-center">
                                <h6>Total Payé</h6>
                                <h4 id="modal-total-paye" class="text-success">0 FCFA</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-danger bg-opacity-10">
                            <div class="card-body text-center">
                                <h6>Reste à Payer</h6>
                                <h4 id="modal-reste" class="text-danger">0 FCFA</h4>
                            </div>
                        </div>
                    </div>
                </div>

                <h6>Détail mois par mois:</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Mois</th>
                                <th>Attendu (cumul)</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody id="modal-details-body">
                            <!-- Détails chargés par JavaScript -->
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    <strong>En retard depuis:</strong> <span id="modal-retard-depuis" class="badge bg-danger">-</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
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
</style>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    $('#filter-btn').click(function(e) {
        e.preventDefault();
        chargerRelance();
    });

    $('#print-btn').click(function() {
        const classeId = $('#classe_id').val();
        const dateRef = $('#date_reference').val();
        const typeFraisId = $('#type_frais_id').val(); // récupération du type de frais

        if (!classeId) {
            toastr.error('Veuillez sélectionner une classe');
            return;
        }

        let url = `/relance/imprimer?classe_id=${classeId}&date_reference=${dateRef}`;

        // Ajouter type_frais_id si sélectionné
        if (typeFraisId) {
            url += `&type_frais_id=${typeFraisId}`;
        }

        window.open(url, '_blank');
    });


    function chargerRelance() {
        const classeId = $('#classe_id').val();
        const dateRef = $('#date_reference').val();
        const typeFrais =  $('#type_frais_id').val();
        
        if (!classeId) {
            toastr.error('Veuillez sélectionner une classe');
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
                type_frais_id: typeFrais
            },
            success: function(response) {
                $('#loading').addClass('d-none');
                
                if (response.success) {
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
        $('#result-summary').text(`Relance générée pour la classe ${data.classe} du mois de ${data.mois_reference}`);
        
        const tbody = $('#relance-table tbody');
        tbody.empty();
        
        let totalAttendu = 0;
        let totalPaye = 0;
        
        data.data.forEach(function(eleve) {
            totalAttendu += eleve.total_attendu;
            totalPaye += eleve.total_paye;
            
            const statutClass = eleve.statut === 'À jour' ? 'a-jour-badge' : 'retard-badge';
            
            tbody.append(`
                <tr>
                    <td>${eleve.eleve}</td>
                    <td>${eleve.classe}</td>
                    <td class="fw-bold">${formatMoney(eleve.total_attendu)}</td>
                    <td class="text-success">${formatMoney(eleve.total_paye)}</td>
                    <td class="text-danger">${formatMoney(eleve.reste_a_payer)}</td>
                    <td><span class="statut-badge ${statutClass}">${eleve.statut}</span></td>
                    <td>
                        <button class="btn btn-sm btn-info btn-details" data-eleve='${JSON.stringify(eleve)}'>
                            <i class="ti ti-eye"></i> Détails
                        </button>
                    </td>
                </tr>
            `);
        });
        
        // Ajouter le total
        tbody.append(`
            <tr class="table-active fw-bold">
                <td colspan="2">TOTAL</td>
                <td>${formatMoney(totalAttendu)}</td>
                <td class="text-success">${formatMoney(totalPaye)}</td>
                <td class="text-danger">${formatMoney(totalAttendu - totalPaye)}</td>
                <td colspan="2"></td>
            </tr>
        `);
        
        $('#relance-results').removeClass('d-none');
        
        // Ajouter les événements sur les boutons de détails
        $('.btn-details').click(function() {
            const eleve = $(this).data('eleve');
            afficherDetailsEleve(eleve);
        });
    }

    function afficherDetailsEleve(eleve) {
        $('#eleve-name').text(eleve.eleve);
        $('#modal-total-attendu').text(formatMoney(eleve.total_attendu));
        $('#modal-total-paye').text(formatMoney(eleve.total_paye));
        $('#modal-reste').text(formatMoney(eleve.reste_a_payer));
        
        const detailsBody = $('#modal-details-body');
        detailsBody.empty();
        
        eleve.details_mois.forEach(function(mois) {
            const statutClass = mois.statut.includes('✅') ? '' : 'text-danger';
            detailsBody.append(`
                <tr>
                    <td>${mois.mois}</td>
                    <td>${formatMoney(mois.attendu_cumul)}</td>
                    <td class="${statutClass}">${mois.statut}</td>
                </tr>
            `);
        });
        
        $('#modal-retard-depuis').text(eleve.en_retard_depuis || 'À jour');
        
        $('#detailsModal').modal('show');
    }

    function formatMoney(amount) {
        return new Intl.NumberFormat('fr-FR', { 
            style: 'currency', 
            currency: 'XOF',
            minimumFractionDigits: 0
        }).format(amount);
    }
});
</script>
@endsection