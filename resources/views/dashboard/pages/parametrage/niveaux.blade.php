@extends('dashboard.layouts.master')

@section('content')
    <!-- Page Header -->
    <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
        <div class="my-auto mb-2">
            <h3 class="page-title mb-1">Gestion des Niveaux</h3>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('dashboard') }}">Tableau de Bord</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0);">Paramétrage</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Niveaux</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex my-xl-auto right-content align-items-center flex-wrap">
            <div class="pe-1 mb-2">
                <a href="{{ route('niveaux.index') }}" class="btn btn-outline-light bg-white btn-icon me-1" data-bs-toggle="tooltip" data-bs-placement="top" title="Actualiser">
                    <i class="ti ti-refresh"></i>
                </a>
            </div>
            <div class="mb-2">
                <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add_niveau">
                    <i class="ti ti-square-rounded-plus-filled me-2"></i>Ajouter un Niveau
                </a>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

    <div class="mb-5">
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
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

    <!-- Niveaux List -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Liste des Niveaux</h5>
            <small class="text-muted">Glissez-déposez les lignes pour réorganiser l'ordre des niveaux</small>
        </div>
        <div class="card-body p-0 py-3">
            <div class="table-responsive">
                <table class="table" id="table-niveaux">
                    <thead class="thead-light">
                        <tr>
                            <th class="no-sort" style="width: 50px;">
                                <div class="form-check form-check-md">
                                    <input class="form-check-input" type="checkbox" id="select-all">
                                </div>
                            </th>
                            <th style="width: 50px;">#</th>
                            <th>Nom du Niveau</th>
                            <th>Ordre</th>
                            <th>Nombre de Classes</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="niveaux-sortable">
                        @forelse($niveaux as $niveau)
                        <tr data-id="{{ $niveau->id }}" data-order="{{ $niveau->ordre }}">
                            <td>
                                <div class="form-check form-check-md">
                                    <input class="form-check-input" type="checkbox">
                                </div>
                            </td>
                            <td>
                                <span class="drag-handle" style="cursor: grab;">
                                    <i class="ti ti-grip-vertical text-muted"></i>
                                </span>
                            </td>
                            <td>
                                <span class="fw-bold">{{ $niveau->nom }}</span>
                            </td>
                            <td>
                                <span class="badge bg-info">{{ $niveau->ordre }}</span>
                            </td>
                            <td>
                                <span class="badge bg-primary">{{ $niveau->classes_count ?? 0 }}</span>
                            </td>
                            <td class="text-center">
                                <!-- Bouton Modifier -->
                                <button class="btn btn-white btn-icon btn-sm me-2 bg-success" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#edit_niveau_{{ $niveau->id }}" 
                                        title="Modifier">
                                    <i class="ti ti-edit-circle text-white"></i>
                                </button>

                                <!-- Bouton Supprimer -->
                                <form action="{{ route('niveaux.destroy', $niveau->id) }}" 
                                      method="POST" 
                                      class="d-inline" 
                                      onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce niveau ? Toutes les classes associées seront également affectées.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-white btn-icon btn-sm bg-danger text-white" title="Supprimer">
                                        <i class="ti ti-trash-x"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="ti ti-school-off" style="font-size: 2rem;"></i>
                                    <p class="mt-2">Aucun niveau trouvé pour l'année {{ session('current_annee_scolaire') }}</p>
                                    <p class="small">Cliquez sur "Ajouter un Niveau" pour en créer un.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Ajouter un Niveau -->
    <div class="modal fade" id="add_niveau" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter un Niveau</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('niveaux.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nom du Niveau <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nom" 
                                   placeholder="Ex: 6ème, 5ème, Seconde, etc." 
                                   value="{{ old('nom') }}" required>
                            <small class="text-muted">Exemple: 6ème, 5ème, 4ème, 3ème, Seconde, Première, Terminale</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ordre d'affichage <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="ordre" 
                                   value="{{ old('ordre', $niveaux->count() + 1) }}" 
                                   min="0" required>
                            <small class="text-muted">Plus le nombre est petit, plus le niveau apparaît en haut</small>
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

    <!-- Modals Modifier -->
    @foreach($niveaux as $niveau)
    <div class="modal fade" id="edit_niveau_{{ $niveau->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modifier le Niveau</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('niveaux.update', $niveau->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nom du Niveau <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nom" 
                                   value="{{ old('nom', $niveau->nom) }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ordre d'affichage <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="ordre" 
                                   value="{{ old('ordre', $niveau->ordre) }}" 
                                   min="0" required>
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

@endsection

@section('scripts')
<!-- SortableJS pour le drag & drop -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
$(document).ready(function () {
    // Initialisation DataTable
    if (!$.fn.DataTable.isDataTable('#table-niveaux')) {
        $('#table-niveaux').DataTable({
            paging: true,
            searching: true,
            ordering: false,
            info: true,
            responsive: true,
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json'
            },
            columnDefs: [
                { orderable: false, targets: [0, 1, 5] }
            ]
        });
    }

 

    // Sélectionner tout
    $('#select-all').on('change', function() {
        const isChecked = $(this).is(':checked');
        $('tbody input[type="checkbox"]').prop('checked', isChecked);
    });


    // Messages flash
    @if(session('success'))
        toastr.success("{{ session('success') }}");
    @endif

    @if(session('error'))
        toastr.error("{{ session('error') }}");
    @endif

    @if($errors->any())
        @foreach($errors->all() as $error)
            toastr.error("{{ $error }}");
        @endforeach
    @endif
});
</script>

@endsection