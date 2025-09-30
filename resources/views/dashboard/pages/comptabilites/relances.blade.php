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
    

<!-- Dans la section Page Header, ajoutez ces boutons -->
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
    
    // Mettre à jour le résumé avec les informations de filtre
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
    
    data.data.forEach(function(eleve) {
        totalAttendu += eleve.total_attendu;
        totalPaye += eleve.total_paye;
        
        const statutClass = eleve.statut === 'À jour' ? 'a-jour-badge' : 'retard-badge';
        
        // Ajouter des badges pour cantine/transport
        let servicesBadges = '';
        if (eleve.cantine_active) {
            servicesBadges += '<span class="badge bg-warning me-1">Cantine</span>';
        }
        if (eleve.transport_active) {
            servicesBadges += '<span class="badge bg-info">Transport</span>';
        }
        
        tbody.append(`
            <tr>
                <td>
                    <div class="fw-semibold">${eleve.eleve}</div>
                    <div class="mt-1">${servicesBadges}</div>
                </td>
                <td>${eleve.classe}</td>
                <td class="fw-bold">${formatMoney(eleve.total_attendu)}</td>
                <td class="text-success">${formatMoney(eleve.total_paye)}</td>
                <td class="text-danger">${formatMoney(eleve.reste_a_payer)}</td>
                <td><span class="statut-badge ${statutClass}">${eleve.statut}</span></td>
            </tr>
        `);
    });
    
    // Ajouter le total seulement s'il y a des données
    if (data.data.length > 0) {
        tbody.append(`
            <tr class="table-active fw-bold">
                <td colspan="2">TOTAL</td>
                <td>${formatMoney(totalAttendu)}</td>
                <td class="text-success">${formatMoney(totalPaye)}</td>
                <td class="text-danger">${formatMoney(totalAttendu - totalPaye)}</td>
                <td></td>
            </tr>
        `);
    }
    
    $('#relance-results').removeClass('d-none');
}

   

    function formatMoney(amount) {
        return new Intl.NumberFormat('fr-FR', { 
            style: 'currency', 
            currency: 'XOF',
            minimumFractionDigits: 0
        }).format(amount);
    }


    // Gestion de l'exportation
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

        if (!classeId) {
            toastr.error('Veuillez sélectionner une classe');
            return;
        }

        let url = `/relance/export?classe_id=${classeId}&date_reference=${dateRef}&format=${format}`;
        
        if (typeFraisId) {
            url += `&type_frais_id=${typeFraisId}`;
        }

        window.location.href = url;
    }

});
</script>
@endsection