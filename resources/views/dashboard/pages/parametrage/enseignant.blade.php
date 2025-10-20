@extends('dashboard.layouts.master')

@section('content')

<!-- Page Header -->
<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
    <div class="my-auto mb-2">
        <h3 class="page-title mb-1">Liste des Enseignants</h3>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Tableau de Bord</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Tous les Enseignants</li>
            </ol>
        </nav>
    </div>

    <div class="d-flex my-xl-auto right-content align-items-center flex-wrap">
        <div class="pe-1 mb-2">
            <a href="{{ route('enseignants.index') }}" class="btn btn-outline-light bg-white btn-icon me-1" title="Actualiser">
                <i class="ti ti-refresh"></i>
            </a>
        </div>

        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEnseignantModal">
            <i class="ti ti-square-rounded-plus-filled me-2"></i>Ajouter un Enseignant
        </button>
    </div>
</div>
<!-- /Page Header -->

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

<div class="card">
    <div class="card-body p-0 py-3">
        <div class="table-responsive">
            <table class="table" id="table-enseignants">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nom complet</th>
                        <th>Téléphone</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($enseignants as $enseignant)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $enseignant->nom_prenoms }}</td>
                        <td>{{ $enseignant->telephone }}</td>
                        <td>
                            <!-- Bouton Modifier -->
                            <button class="btn btn-white btn-icon btn-sm me-2" data-bs-toggle="modal" data-bs-target="#editEnseignantModal_{{ $enseignant->id }}" title="Modifier">
                                <i class="ti ti-edit-circle"></i>
                            </button>

                            <!-- Bouton Supprimer -->
                            <form action="{{ route('enseignants.destroy', $enseignant->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Confirmez-vous la suppression de cet enseignant ?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-white btn-icon btn-sm" title="Supprimer">
                                    <i class="ti ti-trash-x"></i>
                                </button>
                            </form>
                        </td>
                    </tr>

                    <!-- Modal édition -->
                    <div class="modal fade" id="editEnseignantModal_{{ $enseignant->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <form action="{{ route('enseignants.update', $enseignant->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-header">
                                        <h5 class="modal-title">Modifier un Enseignant</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">Nom & Prénoms <span class="text-danger">*</span></label>
                                                <input type="text" name="nom_prenoms" value="{{ old('nom_prenoms', $enseignant->nom_prenoms) }}" class="form-control @error('nom_prenoms') is-invalid @enderror" required>
                                                @error('nom_prenoms')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Téléphone <span class="text-danger">*</span></label>
                                            <input type="text" name="telephone" value="{{ old('telephone', $enseignant->telephone) }}" class="form-control @error('telephone') is-invalid @enderror" required>
                                            @error('telephone')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
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
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal ajout -->
<div class="modal fade" id="addEnseignantModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('enseignants.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter un Enseignant</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Nom & Prenoms <span class="text-danger">*</span></label>
                            <input type="text" name="nom_prenoms" value="{{ old('nom_prenoms') }}" class="form-control @error('nom_prenoms') is-invalid @enderror" required>
                            @error('nom_prenoms')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Téléphone <span class="text-danger">*</span></label>
                        <input type="text" name="telephone" value="{{ old('telephone') }}" class="form-control @error('telephone') is-invalid @enderror" required>
                        @error('telephone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
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
    if (!$.fn.DataTable.isDataTable('#table-enseignants')) {
        $('#table-enseignants').DataTable({
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
