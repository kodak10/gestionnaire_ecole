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
        return ['id' => $m->id, 'nom' => $m->nom, 'coefficient' => $m->coefficient];
    }));

    /**
     * Met à jour les champs coefficient + ordre
     */
   function updateCoefficientsInputs(selectedMatiereIds, allMatieres, data = {}) {
        let container = document.getElementById('coefficients_inputs');
        container.innerHTML = '';
        if (selectedMatiereIds.length === 0) {
            document.getElementById('coefficients_container').style.display = 'none';
            return;
        }
        document.getElementById('coefficients_container').style.display = 'block';

        selectedMatiereIds.forEach(function(matiereId) {
            let matiere = allMatieres.find(m => m.id == matiereId);
            if (!matiere) return;

            // <-- ici on utilise bien "data" et pas "matieresDataById"
            let coef = data[matiereId]?.coefficient ?? matiere.coefficient ?? 1;
            let ordre = data[matiereId]?.ordre ?? matiere.ordre ?? 1;

            let row = document.createElement('div');
            row.classList.add('row', 'mb-2', 'align-items-center');

            // Nom
            let colNom = document.createElement('div');
            colNom.classList.add('col-md-4', 'fw-bold');
            colNom.textContent = matiere.nom;

            // Coefficient
            let colCoef = document.createElement('div');
            colCoef.classList.add('col-md-4');
            colCoef.innerHTML = `
                <label class="form-label mb-1 small">Coefficient</label>
                <input type="number" min="1" max="10" name="coefficients[${matiere.id}]" 
                    class="form-control form-control-sm" value="${coef}" required>
            `;

            // Ordre
            let colOrdre = document.createElement('div');
            colOrdre.classList.add('col-md-4');
            colOrdre.innerHTML = `
                <label class="form-label mb-1 small">Ordre</label>
                <input type="number" min="1" name="ordres[${matiere.id}]" 
                    class="form-control form-control-sm" value="${ordre}" required>
            `;

            row.appendChild(colNom);
            row.appendChild(colCoef);
            row.appendChild(colOrdre);
            container.appendChild(row);
        });
    }


    // Initialisation
    updateCoefficientsInputs($('#matieres_select').val() || [], allMatieres);

    // Changement matières → MAJ inputs
    $('#matieres_select').on('change', function() {
        updateCoefficientsInputs($(this).val() || [], allMatieres);
    });

    // Changement niveau → chargement via AJAX
    $('#niveau_id').on('change', function() {
        let niveauId = $(this).val();
        $('#loadingSpinner').show();

        if (!niveauId) {
            $('#matieres_select').val(null).trigger('change');
            updateCoefficientsInputs([], allMatieres);
            $('#loadingSpinner').hide();
            return;
        }

        $.ajax({
            url: '/parametrages/classes/' + niveauId + '/matieres',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                let matieresIds = data.map(m => m.id.toString());
                $('#matieres_select').val(matieresIds).trigger('change');

                let infos = {};
                data.forEach(m => {
                    infos[m.id] = {
                        coefficient: m.coefficient,
                        ordre: m.ordre
                    };
                });

                updateCoefficientsInputs(matieresIds, allMatieres, infos);
                $('#loadingSpinner').hide();
            },
            error: function(xhr) {
                alert('Erreur lors du chargement des matières.');
                console.error(xhr.responseText);
                $('#loadingSpinner').hide();
            }
        });
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