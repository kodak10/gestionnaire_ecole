@extends('dashboard.layouts.master')

@section('content')
<!-- Page Header -->
<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
    
    <div class="my-auto mb-2">
        <h3 class="page-title mb-1">Liste des Matières</h3>
        
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Tableau de Bord</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Toutes les Matières</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex my-xl-auto right-content align-items-center flex-wrap">
        <div class="pe-1 mb-2">
            <a href="{{ route('matieres.index') }}" class="btn btn-outline-light bg-white btn-icon me-1" data-bs-toggle="tooltip" data-bs-placement="top" title="Actualiser">
                <i class="ti ti-refresh"></i>
            </a>
        </div>
        <div class="d-flex flex-wrap">
            <!-- Bouton Ajouter Matière -->
            <div class="mb-2 me-2">
                <a href="#" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#add_matiere">
                    <i class="ti ti-square-rounded-plus-filled me-2"></i>Ajouter une Matière
                </a>
            </div>

            <!-- Bouton Affecter Matières -->
            <div class="mb-2 me-2">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assignMatieresModal">
                    <i class="ti ti-school me-2"></i> Affecter Matières à une Classe
                </button>
            </div>
        </div>

    </div>
</div>
<!-- /Page Header -->

<!-- Messages d'alerte -->
<div class="mb-5">
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif
</div>



<!-- Liste des matières -->
<div class="card">
    
    
    <div class="card-body p-0 py-3">
        <div class="table-responsive">
            <table class="table" id="table-matiere">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>Niveau</th>
                        <th>Nom</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($matieres as $matiere)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            {{ $matiere->niveaux->pluck('nom')->join(', ') }}
                        </td>

                        <td>{{ $matiere->nom }}</td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="dropdown">
                                    <a href="#" class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots-vertical fs-14"></i>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-right p-3">
                                        <li>
                                            <a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#edit_matiere_{{ $matiere->id }}">
                                                <i class="ti ti-edit-circle me-2"></i>Modifier
                                            </a>
                                        </li>
                                        
                                        
                                        <li>
                                            <form action="{{ route('matieres.destroy', $matiere->id) }}" method="POST" id="delete-form-{{ $matiere->id }}">
                                                @csrf
                                                @method('DELETE')
                                                <a class="dropdown-item rounded-1" href="#" onclick="event.preventDefault(); if(confirm('Êtes-vous sûr de vouloir supprimer cette matière ?')) document.getElementById('delete-form-{{ $matiere->id }}').submit();">
                                                    <i class="ti ti-trash-x me-2"></i>Supprimer
                                                </a>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal d'ajout -->
<div class="modal fade" id="add_matiere" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter une Matière</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('matieres.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Nom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('nom') is-invalid @enderror" name="nom" value="{{ old('nom') }}" required>
                                @error('nom')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                       
                        
                        
                        
                        
                    </div>
                    
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modals d'édition -->
@foreach($matieres as $matiere)
<div class="modal fade" id="edit_matiere_{{ $matiere->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier la Matière</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('matieres.update', $matiere->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Nom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('nom') is-invalid @enderror" name="nom" value="{{ old('nom', $matiere->nom) }}" required>
                                @error('nom')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

<!-- Modal Affectation Matières -->
<div class="modal fade" id="assignMatieresModal" tabindex="-1" aria-labelledby="assignMatieresModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('classes.matieres.assign') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="assignMatieresModalLabel">Affecter Matières à une Classe</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>

                <div class="modal-body">
                    <!-- Choix du niveau -->
                    <div class="mb-3">
                        <label for="niveau_id" class="form-label">Choisir le Niveau <span class="text-danger">*</span></label>
                        <select id="niveau_id" name="niveau_id" class="form-select @error('niveau_id') is-invalid @enderror" required>
                            <option value="" disabled selected>-- Sélectionner un niveau --</option>
                            @foreach($niveaux as $niveau)
                                <option value="{{ $niveau->id }}" {{ old('niveau_id') == $niveau->id ? 'selected' : '' }}>
                                    {{ $niveau->nom }}
                                </option>
                            @endforeach
                        </select>
                        @error('niveau_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Sélection multiple des matières -->
                    <div class="mb-3">
                        <label for="matieres_select" class="form-label">Choisir les Matières <span class="text-danger">*</span></label>
                        <select id="matieres_select" name="matieres[]" class="form-select select2 @error('matieres') is-invalid @enderror" multiple="multiple" required style="width: 100%;">
                            @foreach($matieres as $matiere)
                                <option value="{{ $matiere->id }}" {{ (collect(old('matieres'))->contains($matiere->id)) ? 'selected':'' }}>
                                    {{ $matiere->nom }}
                                </option>
                            @endforeach
                        </select>
                        @error('matieres')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Zone Coefficients et Ordres -->
                    <div id="coefficients_container" style="display:none;">
                        <h6 class="mb-2">Paramètres par matière sélectionnée</h6>
                        <div id="coefficients_inputs"></div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Spinner global caché au départ -->
<div id="loadingSpinner" style="
    display: none;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 1055; /* au-dessus du modal Bootstrap (1050) */
">
    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
        <span class="visually-hidden">Chargement...</span>
    </div>
</div>

@endsection

@section('scripts')
<!-- Inclure JS Select2 -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialisation Select2
    $('#matieres_select').select2({
        placeholder: 'Sélectionnez les matières',
        allowClear: true,
        dropdownParent: $('#assignMatieresModal')
    });

    let allMatieres = @json($matieres->map(function($m) {
        return [
            'id' => $m->id, 
            'nom' => $m->nom
        ];
    }));

    // Variable pour stocker les données de pivot actuelles
    let currentPivotData = {};

    /**
     * Met à jour les champs coefficient + ordre
     */
    function updateCoefficientsInputs(selectedMatiereIds, allMatieres, pivotData = null) {
        let container = document.getElementById('coefficients_inputs');
        container.innerHTML = '';
        
        if (selectedMatiereIds.length === 0) {
            document.getElementById('coefficients_container').style.display = 'none';
            return;
        }
        
        document.getElementById('coefficients_container').style.display = 'block';

        // Si pivotData est fourni, mettre à jour currentPivotData
        if (pivotData) {
            currentPivotData = pivotData;
        }

        selectedMatiereIds.forEach(function(matiereId) {
            let matiere = allMatieres.find(m => m.id == matiereId);
            if (!matiere) return;

            // Utiliser currentPivotData pour les valeurs
            let pivotInfo = currentPivotData[matiereId] || {};
            let coefficient = pivotInfo.coefficient || '1.00';
            let ordre = pivotInfo.ordre || 1;
            let denominateur = pivotInfo.denominateur || 20;

            let row = document.createElement('div');
            row.classList.add('row', 'mb-2', 'align-items-center');

            // Nom
            let colNom = document.createElement('div');
            colNom.classList.add('col-md-3', 'fw-bold');
            colNom.textContent = matiere.nom;

            // Coefficient
            let colCoef = document.createElement('div');
            colCoef.classList.add('col-md-3');
            colCoef.innerHTML = `
                <label class="form-label mb-1 small">Coefficient</label>
                <input type="text" 
                    name="coefficients[${matiere.id}]"
                    class="form-control form-control-sm coefficient-input"
                    value="${coefficient}" 
                    pattern="^\\d{1,2}([.,]\\d{1,2})?$"
                    title="Format: 5,5 ou 5.5 ou 10"
                    required>
            `;

            // Dénominateur
            let colDenominateur = document.createElement('div');
            colDenominateur.classList.add('col-md-3');
            colDenominateur.innerHTML = `
                <label class="form-label mb-1 small">Dénominateur</label>
                <input type="number" min="1" 
                    name="denominateurs[${matiere.id}]" 
                    class="form-control form-control-sm"
                    value="${denominateur}" 
                    required>
            `;

            // Ordre
            let colOrdre = document.createElement('div');
            colOrdre.classList.add('col-md-3');
            colOrdre.innerHTML = `
                <label class="form-label mb-1 small">Ordre</label>
                <input type="number" min="1" 
                    name="ordres[${matiere.id}]" 
                    class="form-control form-control-sm"
                    value="${ordre}" 
                    required>
            `;

            row.appendChild(colNom);
            row.appendChild(colCoef);
            row.appendChild(colDenominateur);
            row.appendChild(colOrdre);
            container.appendChild(row);
        });
    }

    // Initialisation
    let initialSelected = $('#matieres_select').val() || [];
    updateCoefficientsInputs(initialSelected, allMatieres);

    // Changement matières → MAJ inputs en conservant les données existantes
    $('#matieres_select').on('change', function() {
        let selectedIds = $(this).val() || [];
        
        // Pour les nouvelles matières ajoutées, initialiser avec des valeurs par défaut
        selectedIds.forEach(function(matiereId) {
            if (!currentPivotData[matiereId]) {
                currentPivotData[matiereId] = {
                    coefficient: '1.00',
                    ordre: Object.keys(currentPivotData).length + 1,
                    denominateur: 20
                };
            }
        });

        // Mettre à jour l'affichage avec les données actuelles
        updateCoefficientsInputs(selectedIds, allMatieres);
    });

    // Changement niveau → chargement via AJAX
    $('#niveau_id').on('change', function() {
        let niveauId = $(this).val();
        $('#loadingSpinner').show();

        if (!niveauId) {
            $('#matieres_select').val(null).trigger('change');
            currentPivotData = {};
            updateCoefficientsInputs([], allMatieres);
            $('#loadingSpinner').hide();
            return;
        }

        $.ajax({
            url: '/parametrages/classes/' + niveauId + '/matieres',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                // Convertir les IDs en string pour la compatibilité avec Select2
                let matieresIds = data.map(m => m.id.toString());
                
                // Mettre à jour la sélection
                $('#matieres_select').val(matieresIds).trigger('change');

                // Préparer les données de pivot
                let pivotData = {};
                data.forEach(m => {
                    pivotData[m.id] = {
                        coefficient: m.coefficient,
                        ordre: m.ordre,
                        denominateur: m.denominateur
                    };
                });

                // Mettre à jour les données de pivot actuelles
                currentPivotData = pivotData;

                // Mettre à jour les inputs avec les données de pivot
                updateCoefficientsInputs(matieresIds, allMatieres, pivotData);
                $('#loadingSpinner').hide();
            },
            error: function(xhr) {
                console.error('Erreur AJAX:', xhr.responseText);
                alert('Erreur lors du chargement des matières pour ce niveau.');
                $('#loadingSpinner').hide();
            }
        });
    });

    // Sauvegarder les modifications en temps réel dans currentPivotData
    $(document).on('change', '.coefficient-input, input[name^="denominateurs"], input[name^="ordres"]', function() {
        let name = $(this).attr('name');
        let value = $(this).val();
        let matiereId = null;

        // Extraire l'ID de la matière du nom du champ
        if (name.startsWith('coefficients[')) {
            matiereId = name.match(/coefficients\[(\d+)\]/)[1];
            if (matiereId && currentPivotData[matiereId]) {
                currentPivotData[matiereId].coefficient = value;
            }
        } else if (name.startsWith('denominateurs[')) {
            matiereId = name.match(/denominateurs\[(\d+)\]/)[1];
            if (matiereId && currentPivotData[matiereId]) {
                currentPivotData[matiereId].denominateur = parseInt(value);
            }
        } else if (name.startsWith('ordres[')) {
            matiereId = name.match(/ordres\[(\d+)\]/)[1];
            if (matiereId && currentPivotData[matiereId]) {
                currentPivotData[matiereId].ordre = parseInt(value);
            }
        }
    });

    // Validation en temps réel des coefficients
    $(document).on('input', '.coefficient-input', function() {
        let value = $(this).val();
        let regex = /^\d{1,2}([.,]\d{1,2})?$/;
        
        if (value === '' || regex.test(value)) {
            $(this).removeClass('is-invalid').addClass('is-valid');
        } else {
            $(this).removeClass('is-valid').addClass('is-invalid');
        }
    });
});
</script>
<script>
$(document).ready(function () {
    // Vérifier si la table n'est pas déjà initialisée
    if (!$.fn.DataTable.isDataTable('#table-matiere')) {
        $('#table-matiere').DataTable({
            paging: true,
            searching: true,
            ordering: true,
            info: true,
            responsive: true,
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json'
            }
        });
    }
});

</script>
@endsection


<style>
.coefficient-input.is-valid {
    border-color: #198754;
    background-color: rgba(25, 135, 84, 0.1);
}


</style>