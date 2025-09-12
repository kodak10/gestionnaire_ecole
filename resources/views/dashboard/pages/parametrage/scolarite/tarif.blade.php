@extends('dashboard.layouts.master')

@section('content')
<!-- En-tête de page -->
<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
    <div>
        <h3 class="page-title mb-1">Liste des Tarifs</h3>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Tableau de Bord</a></li>
                <li class="breadcrumb-item active" aria-current="page">Tarifs</li>
            </ol>
        </nav>
    </div>
    <div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTarifModal">
            <i class="ti ti-square-rounded-plus-filled me-2"></i>Ajouter Tarif
        </button>
    </div>
</div>

<!-- Messages -->
@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

@if($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<!-- Tableau des tarifs -->
<div class="card">
    
    <div class="card-body">
        <div class="table-responsive">
            <table class="table" id="table-tarifs">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Type de Frais</th>
                        <th>Niveau</th>
                        <th>Montant (FCFA)</th>
                        <th>Obligatoire</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tarifs as $tarif)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $tarif->typeFrais->nom }}</td>
                        <td>{{ $tarif->niveau ? $tarif->niveau->nom : 'Tous les niveaux' }}</td>
                        <td>{{ number_format($tarif->montant, 0, ',', ' ') }}</td>
                        <td>
                            @if($tarif->obligatoire == 1)
                                <span class="badge bg-success">Oui</span>
                            @else
                                <span class="badge bg-secondary">Non</span>
                            @endif
                        </td>

                        <td>
                            <button class="btn btn-sm btn-icon edit-tarif"
                                    data-id="{{ $tarif->id }}"
                                    data-type="{{ $tarif->type_frais_id }}"
                                    data-niveau="{{ $tarif->niveau_id }}"
                                    data-montant="{{ $tarif->montant }}"
                                    data-obligatoire="{{ $tarif->obligatoire }}"
                                    title="Modifier">
                                <i class="ti ti-edit text-primary"></i>
                            </button>


                            <form action="{{ route('tarifs.destroy', $tarif->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer ce tarif ?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-icon" title="Supprimer">
                                    <i class="ti ti-trash text-danger"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Ajout -->
<div class="modal fade" id="addTarifModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('tarifs.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter un Tarif</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Type de Frais <span class="text-danger">*</span></label>
                        <select name="type_frais_id" class="form-select" required>
                            <option value="" disabled selected>-- Sélectionnez --</option>
                            @foreach($typeFrais as $type)
                                <option value="{{ $type->id }}" {{ old('type_frais_id') == $type->id ? 'selected' : '' }}>
                                    {{ $type->nom }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Niveau</label>
                        <select name="niveau_ids[]" class="form-select select2-niveau-edit" multiple="multiple">
                            @foreach($niveaux as $niveau)
                                <option value="{{ $niveau->id }}">{{ $niveau->nom }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="applyToAll" name="apply_to_all" value="1">
                        <label class="form-check-label" for="applyToAll">Appliquer à tous les niveaux</label>
                    </div>

                    <div class="mb-3">
                        <label class="form-label d-block">Obligatoire</label>
                        <input type="hidden" name="obligatoire" value="0">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="obligatoireSwitch" name="obligatoire" value="1">
                            <label class="form-check-label" for="obligatoireSwitch">Oui</label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Montant (FCFA) <span class="text-danger">*</span></label>
                        <input type="number" name="montant" class="form-control" value="{{ old('montant') }}" required min="0" step="100">
                    </div>
                    
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Modification -->
<div class="modal fade" id="editTarifModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="" id="editTarifForm">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Modifier Tarif</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Type de Frais <span class="text-danger">*</span></label>
                        <select name="type_frais_id" class="form-select" required>
                            @foreach($typeFrais as $type)
                                <option value="{{ $type->id }}">{{ $type->nom }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Niveau</label>
                        <select name="niveau_ids[]" class="form-select select2-niveau-edit" multiple="multiple">
                            @foreach($niveaux as $niveau)
                                <option value="{{ $niveau->id }}">{{ $niveau->nom }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label d-block">Obligatoire</label>
                        <input type="hidden" name="obligatoire" value="0">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="obligatoireSwitchEdit" name="obligatoire" value="1">
                            <label class="form-check-label" for="obligatoireSwitchEdit">Oui</label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Montant (FCFA) <span class="text-danger">*</span></label>
                        <input type="number" name="montant" class="form-control" required min="0" step="100">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Mettre à Jour</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/i18n/fr.js"></script>

<script>
$(document).ready(function() {
    // Initialisation Select2 multi-select pour ajout et modification
    $('.select2-niveau, .select2-niveau-edit').select2({
        language: 'fr',
        placeholder: "Sélectionnez un ou plusieurs niveaux",
        allowClear: true,
        width: '100%'
    });

    // Gestion de la case "Appliquer à tous les niveaux" (dans modal ajout)
    $('#applyToAll').change(function() {
        if ($(this).is(':checked')) {
            $('.select2-niveau-edit').val(null).trigger('change');
            $('.select2-niveau-edit').prop('disabled', true);
        } else {
            $('.select2-niveau-edit').prop('disabled', false);
        }
    });

    // Ouvrir modal modification et pré-remplir les champs
    $('.edit-tarif').click(function() {
        var tarifId = $(this).data('id');
        var formAction = '/scolarite/tarifs/' + tarifId;
        $('#editTarifForm').attr('action', formAction);

        // Type de frais
        var typeFraisId = $(this).data('type');
        $('#editTarifModal select[name="type_frais_id"]').val(typeFraisId).trigger('change');

        // Obligatoire - checkbox
        var obligatoire = $(this).data('obligatoire');
        $('#obligatoireSwitchEdit').prop('checked', obligatoire == '1');

        // Montant
        var montant = $(this).data('montant');
        $('#editTarifModal input[name="montant"]').val(montant);

        // Niveaux
        var niveauId = $(this).data('niveau');
        var niveaux = [];
        if (niveauId) {
            niveaux.push(niveauId);
        }
        $('#editTarifModal select[name="niveau_ids[]"]').val(niveaux).trigger('change');

        // Supprimer le hidden obligatoire si il existe, pour éviter conflit
        $('#editTarifForm input[name="obligatoire"][type="hidden"]').remove();

        // Affiche le modal de modification
        $('#editTarifModal').modal('show');
    });

    // Avant soumission du formulaire, gérer le checkbox obligatoire correctement
    $('#editTarifForm').submit(function(){
        // Supprime tout hidden obligatoire existant
        $(this).find('input[name="obligatoire"][type="hidden"]').remove();

        if (!$('#obligatoireSwitchEdit').is(':checked')) {
            // Si décoché, ajouter hidden avec valeur 0
            $(this).prepend('<input type="hidden" name="obligatoire" value="0">');
        }
        // Sinon, checkbox envoie la valeur 1 automatiquement
    });

    // Initialisation DataTable
    if (!$.fn.DataTable.isDataTable('#table-tarifs')) {
        $('#table-tarifs').DataTable({
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

<style>
.select2-container {
    width: 100% !important;
}
.select2-selection {
    height: 38px !important;
    border: 1px solid #ced4da !important;
}
.select2-selection__arrow {
    height: 36px !important;
}
.select2-selection--single {
    padding: 5px 0;
}
</style>
@endsection