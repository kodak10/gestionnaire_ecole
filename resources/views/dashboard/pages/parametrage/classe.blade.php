@extends('dashboard.layouts.master')

@section('content')
    <!-- Page Header -->
    <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
        <div class="my-auto mb-2">
            <h3 class="page-title mb-1">Liste des Classes</h3>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('dashboard') }}">Tableau de Bord</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0);">Classes</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Toutes les Classes</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex my-xl-auto right-content align-items-center flex-wrap">
            <div class="pe-1 mb-2">
                <a href="{{ route('classes.index') }}" class="btn btn-outline-light bg-white btn-icon me-1" data-bs-toggle="tooltip" data-bs-placement="top" title="Actualiser">
                    <i class="ti ti-refresh"></i>
                </a>
            </div>
            
           
            <div class="mb-2">
                <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add_class">
                    <i class="ti ti-square-rounded-plus-filled me-2"></i>Ajouter une Classe
                </a>
            </div>
        </div>
    </div>
    <!-- /Page Header -->

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

    <!-- Classes List -->
    <div class="card">
       
        <div class="card-body p-0 py-3">
            <div class="table-responsive">
                <table class="table" id="table-classe">
                    <thead class="thead-light">
                        <tr>
                            <th class="no-sort">
                                <div class="form-check form-check-md">
                                    <input class="form-check-input" type="checkbox" id="select-all">
                                </div>
                            </th>
                            <th>Niveau</th>
                            <th>Classe</th>
                            <th>Capacité</th>
                            <th>Nombre d'Élèves</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($classes as $classe)
                        <tr>
                            <td>
                                <div class="form-check form-check-md">
                                    <input class="form-check-input" type="checkbox">
                                </div>
                            </td>
                            <td>{{ $classe->niveau->nom }}</td>
                            <td>{{ $classe->nom }}</td>
                            <td>{{ $classe->capacite }}</td>
                            <td>{{ $classe->inscriptions->count() }}</td>
                            
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="dropdown">
                                        <a href="#" class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="ti ti-dots-vertical fs-14"></i>
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-right p-3">
                                            <li>
                                                <a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#edit_class_{{ $classe->id }}">
                                                    <i class="ti ti-edit-circle me-2"></i>Modifier
                                                </a>
                                            </li>
                                            <li>
                                                <form action="{{ route('classes.destroy', $classe->id) }}" method="POST" id="delete-form-{{ $classe->id }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <a class="dropdown-item rounded-1" href="#" onclick="event.preventDefault(); if(confirm('Êtes-vous sûr de vouloir supprimer cette classe ?')) document.getElementById('delete-form-{{ $classe->id }}').submit();">
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

    <!-- create.blade.php -->
    <div class="modal fade" id="add_class" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter une Classe</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('classes.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Niveau</label>
                                    <select class="form-select" name="niveau_id" required>
                                        <option value="">Sélectionner</option>
                                        @foreach($niveaux as $niveau)
                                            <option value="{{ $niveau->id }}">{{ $niveau->nom }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nom</label>
                                    <input type="text" class="form-control" name="nom" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Capacité</label>
                                    <input type="number" class="form-control" name="capacite" value="30" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Enseignant <span class="text-danger">*</span></label>
                                    <select name="enseignant_id" class="form-control @error('enseignant_id') is-invalid @enderror" required>
                                        <option value="">-- Sélectionner un enseignant --</option>
                                        @foreach($enseignants as $enseignant)
                                            <option value="{{ $enseignant->id }}" {{ old('enseignant_id', $classe->enseignant_id ?? '') == $enseignant->id ? 'selected' : '' }}>
                                                {{ $enseignant->nom_prenoms }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('enseignant_id')
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

    @foreach($classes as $classe)
        <!-- edit.blade.php -->
        <div class="modal fade" id="edit_class_{{ $classe->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Modifier la Classe</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('classes.update', $classe->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Niveau</label>
                                        <select class="form-select" name="niveau_id" required>
                                            @foreach($niveaux as $niveau)
                                                <option value="{{ $niveau->id }}" {{ $classe->niveau_id == $niveau->id ? 'selected' : '' }}>
                                                    {{ $niveau->nom }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Nom</label>
                                        <input type="text" class="form-control" name="nom" value="{{ $classe->nom }}" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Capacité</label>
                                        <input type="number" class="form-control" name="capacite" value="{{ $classe->capacite }}" required>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Enseignant <span class="text-danger">*</span></label>
                                        <select name="enseignant_id" class="form-control @error('enseignant_id') is-invalid @enderror" required>
                                            <option value="">-- Sélectionner un enseignant --</option>
                                            @foreach($enseignants as $enseignant)
                                                <option value="{{ $enseignant->id }}"
                                                    {{ old('enseignant_id', $classe->enseignant_id) == $enseignant->id ? 'selected' : '' }}>
                                                    {{ $enseignant->nom_prenoms }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('enseignant_id')
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

@endsection
@section('scripts')
<script>
$(document).ready(function () {
    // Vérifier si la table n'est pas déjà initialisée
    if (!$.fn.DataTable.isDataTable('#table-classe')) {
        $('#table-classe').DataTable({
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