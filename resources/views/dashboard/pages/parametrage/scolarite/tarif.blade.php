@extends('dashboard.layouts.master')

@section('content')
<!-- Page Header -->
<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
    <div class="my-auto mb-2">
        <h3 class="page-title mb-1">Liste des Tarifs</h3>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Tableau de Bord</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Tous les Tarifs</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex my-xl-auto right-content align-items-center flex-wrap">
        <div class="pe-1 mb-2">
            <a href="{{ route('tarifs.index') }}" class="btn btn-outline-light bg-white btn-icon me-1" data-bs-toggle="tooltip" data-bs-placement="top" title="Actualiser">
                <i class="ti ti-refresh"></i>
            </a>
        </div>
        <div class="mb-2">
            <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTarifModal">
                <i class="ti ti-square-rounded-plus-filled me-2"></i>Ajouter un Tarif
            </a>
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

    @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif
</div>

<!-- Liste des tarifs -->
<!-- Liste des tarifs -->
<div class="card">
    <div class="card-body p-0 py-3">
        <div class="table-responsive">
            <table class="table" id="table-tarifs">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>Type de Frais</th>
                        <th>Niveau</th>
                        <th>Libellé</th>
                        <th>Montant (FCFA)</th>
                        <th>Obligatoire</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
    @forelse($tarifs as $tarif)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $tarif->typeFrais->nom ?? 'Non défini' }}</td>
            <td>
                @if($tarif->niveau_id === null)
                    <span class="badge bg-secondary">Tous les niveaux</span>
                @else
                    {{ $tarif->niveau->nom ?? 'Niveau non défini' }}
                @endif
            </td>
            <td>{{ $tarif->libelle ?? '' }}</td>
            <td>{{ number_format($tarif->montant, 0, ',', ' ') }}</td>
            <td>
                @if($tarif->obligatoire)
                    <span class="badge bg-success">Oui</span>
                @else
                    <span class="badge bg-secondary">Non</span>
                @endif
            </td>
            <td>
                <!-- Bouton Modifier -->
                <button class="btn btn-white btn-icon btn-sm me-2 bg-success edit-tarif"
                        data-id="{{ $tarif->id }}"
                        data-type="{{ $tarif->type_frais_id }}"
                        data-niveau="{{ $tarif->niveau_id }}"
                        data-montant="{{ $tarif->montant }}"
                        data-obligatoire="{{ $tarif->obligatoire ? '1' : '0' }}"
                        data-libelle="{{ $tarif->libelle }}"
                        title="Modifier">
                    <i class="ti ti-edit-circle text-white"></i>
                </button>

                <!-- Bouton Supprimer -->
                <form action="{{ route('tarifs.destroy', $tarif->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Confirmez-vous la suppression de ce tarif ?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-white btn-icon btn-sm bg-danger text-white" title="Supprimer">
                        <i class="ti ti-trash-x"></i>
                    </button>
                </form>
            </td>
        </tr>
    @empty
        <!-- Pas de ligne, DataTable gérera l'affichage -->
    @endforelse
</tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Ajout -->
<div class="modal fade" id="addTarifModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter un Tarif</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('tarifs.store') }}" method="POST" id="addTarifForm">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Type de Frais <span class="text-danger">*</span></label>
                                <select class="form-select" name="type_frais_id" required>
                                    <option value="">Sélectionner</option>
                                    @foreach($typeFrais as $type)
                                        <option value="{{ $type->id }}">{{ $type->nom }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Niveaux</label>
                                <select name="niveau_ids[]" class="form-select select2-niveau" multiple="multiple">
                                    @foreach($niveaux as $niveau)
                                        <option value="{{ $niveau->id }}">{{ $niveau->nom }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Laissez vide pour appliquer à tous les niveaux</small>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Libellé</label>
                                <input type="text" class="form-control" name="libelle" value="{{ old('libelle') }}" placeholder="Ex: Frais de scolarité annuel">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label d-block">Obligatoire</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="obligatoireSwitchAdd" name="obligatoire" value="1">
                                    <label class="form-check-label" for="obligatoireSwitchAdd">Oui</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Montant (FCFA) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="montant" required min="0" step="100">
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

<!-- Modal Modification -->
<div class="modal fade" id="editTarifModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier le Tarif</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST" id="editTarifForm">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Type de Frais <span class="text-danger">*</span></label>
                                <select class="form-select" name="type_frais_id" required>
                                    @foreach($typeFrais as $type)
                                        <option value="{{ $type->id }}">{{ $type->nom }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Niveaux</label>
                                <select name="niveau_ids[]" class="form-select select2-niveau-edit" multiple="multiple">
                                    @foreach($niveaux as $niveau)
                                        <option value="{{ $niveau->id }}">{{ $niveau->nom }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Laissez vide pour appliquer à tous les niveaux</small>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Libellé</label>
                                <input type="text" class="form-control" name="libelle" id="editlibelle" placeholder="Ex: Frais de scolarité annuel">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label d-block">Obligatoire</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="obligatoireSwitchEdit" name="obligatoire" value="1">
                                    <label class="form-check-label" for="obligatoireSwitchEdit">Oui</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Montant (FCFA) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="montant" id="editMontant" required min="0" step="100">
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

@endsection

@section('scripts')
<script>
$(document).ready(function () {
    // Initialiser Select2 pour les modales
    function initSelect2() {
        // Select2 pour la modal d'ajout
        if (!$('#addTarifModal .select2-niveau').data('select2')) {
            $('#addTarifModal .select2-niveau').select2({
                dropdownParent: $('#addTarifModal'),
                placeholder: "Sélectionnez un ou plusieurs niveaux",
                allowClear: true,
                width: '100%'
            });
        }

        // Select2 pour la modal de modification
        if (!$('#editTarifModal .select2-niveau-edit').data('select2')) {
            $('#editTarifModal .select2-niveau-edit').select2({
                dropdownParent: $('#editTarifModal'),
                placeholder: "Sélectionnez un ou plusieurs niveaux",
                allowClear: true,
                width: '100%'
            });
        }
    }

    // Initialiser Select2
    initSelect2();

    // Initialiser DataTable uniquement si le tableau n'est pas vide
    var table = $('#table-tarifs');
    var hasData = table.find('tbody tr:not(.dataTables-empty)').length > 0;
    
    if ($.fn.DataTable && !$.fn.DataTable.isDataTable('#table-tarifs')) {
        if (hasData) {
            // Si des données existent, initialiser DataTable normalement
            table.DataTable({
                paging: true,
                searching: true,
                ordering: true,
                info: true,
                responsive: true,
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json'
                },
                columnDefs: [
                    { orderable: false, targets: [6] }
                ]
            });
        } else {
            // Si pas de données, initialiser avec des options minimales
            table.DataTable({
                paging: false,
                searching: false,
                ordering: false,
                info: false,
                responsive: true,
                language: {
                    emptyTable: "Aucun tarif trouvé"
                }
            });
        }
    }

    // Ouvrir modal modification
    $(document).on('click', '.edit-tarif', function() {
        var tarifId = $(this).data('id');
        var formAction = '/scolarite/tarifs/' + tarifId;
        $('#editTarifForm').attr('action', formAction);

        var typeFraisId = $(this).data('type');
        $('#editTarifModal select[name="type_frais_id"]').val(typeFraisId).trigger('change');

        var obligatoire = $(this).data('obligatoire');
        $('#obligatoireSwitchEdit').prop('checked', obligatoire == '1');

        var montant = $(this).data('montant');
        $('#editMontant').val(montant);

        var libelle = $(this).data('libelle') || '';
        $('#editlibelle').val(libelle);

        // Pour la modification d'un seul tarif, on pré-sélectionne le niveau
        var niveauId = $(this).data('niveau');
        var select = $('#editTarifModal select[name="niveau_ids[]"]');
        
        if (select.data('select2')) {
            select.val(null).trigger('change');
            if (niveauId) {
                select.val([niveauId]).trigger('change');
            }
        }

        $('#editTarifModal').modal('show');
    });

    // Réinitialiser Select2 quand les modales sont fermées
    $('#addTarifModal').on('hidden.bs.modal', function () {
        $('#addTarifModal .select2-niveau').val(null).trigger('change');
        // Réinitialiser le formulaire
        $('#addTarifForm')[0].reset();
    });

    $('#editTarifModal').on('hidden.bs.modal', function () {
        $('#editTarifModal .select2-niveau-edit').val(null).trigger('change');
    });

    // Gestion des checkbox obligatoire
    $('#editTarifForm').on('submit', function() {
        // Supprimer l'ancien hidden s'il existe
        $(this).find('input[name="obligatoire_hidden"]').remove();
        
        if (!$('#obligatoireSwitchEdit').is(':checked')) {
            $(this).append('<input type="hidden" name="obligatoire" value="0">');
        }
    });

    $('#addTarifForm').on('submit', function() {
        // Supprimer l'ancien hidden s'il existe
        $(this).find('input[name="obligatoire_hidden"]').remove();
        
        if (!$('#obligatoireSwitchAdd').is(':checked')) {
            $(this).append('<input type="hidden" name="obligatoire" value="0">');
        }
    });
});
</script>
@endsection