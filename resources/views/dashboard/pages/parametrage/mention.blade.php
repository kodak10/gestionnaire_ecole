@extends('dashboard.layouts.master')

@section('content')

<!-- Page Header -->
<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
    <div class="my-auto mb-2">
        <h3 class="page-title mb-1">Liste des Mentions</h3>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Tableau de Bord</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Toutes les Mentions</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex my-xl-auto right-content align-items-center flex-wrap">
        <div class="pe-1 mb-2">
            <a href="{{ route('matieres.index') }}" class="btn btn-outline-light bg-white btn-icon me-1" data-bs-toggle="tooltip" data-bs-placement="top" title="Actualiser">
                <i class="ti ti-refresh"></i>
            </a>
        </div>
        
        
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMentionModal">
            <i class="ti ti-square-rounded-plus-filled me-2"></i>Ajouter une Mention
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
            <table class="table" id="table-mentions">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nom</th>
                        <th>Description</th>
                        <th>Plage de notes</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($mentions as $mention)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $mention->nom }}</td>
                        <td>{{ $mention->description ?? '---' }}</td>
                        <td>
                            @if($mention->min_note !== null && $mention->max_note !== null)
                                {{ $mention->min_note }} - {{ $mention->max_note }}
                            @else
                                N/A
                            @endif
                        </td>
                        <td>
                            <button class="btn btn-white btn-icon btn-sm me-2" data-bs-toggle="modal" data-bs-target="#editMentionModal_{{ $mention->id }}" title="Modifier">
                                <i class="ti ti-edit-circle"></i>
                            </button>
                            <form action="{{ route('mentions.destroy', $mention->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Confirmez-vous la suppression de cette mention ?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-white btn-icon btn-sm" title="Supprimer">
                                    <i class="ti ti-trash-x"></i>
                                </button>
                            </form>
                        </td>
                    </tr>

                    <!-- Modal édition -->
                    <div class="modal fade" id="editMentionModal_{{ $mention->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <form action="{{ route('mentions.update', $mention->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-header">
                                        <h5 class="modal-title">Modifier Mention</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">Nom <span class="text-danger">*</span></label>
                                            <input type="text" name="nom" value="{{ old('nom', $mention->nom) }}" class="form-control @error('nom') is-invalid @enderror" required>
                                            @error('nom')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Description</label>
                                            <textarea name="description" class="form-control" rows="3">{{ old('description', $mention->description) }}</textarea>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <label class="form-label">Note minimale</label>
                                                <input type="number" name="min_note" min="0" max="20" value="{{ old('min_note', $mention->min_note) }}" class="form-control @error('min_note') is-invalid @enderror">
                                                @error('min_note')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Note maximale</label>
                                                <input type="number" name="max_note" min="0" max="20" value="{{ old('max_note', $mention->max_note) }}" class="form-control @error('max_note') is-invalid @enderror">
                                                @error('max_note')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
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
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal ajout -->
<div class="modal fade" id="addMentionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('mentions.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter une Mention</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nom <span class="text-danger">*</span></label>
                        <input type="text" name="nom" class="form-control @error('nom') is-invalid @enderror" value="{{ old('nom') }}" required>
                        @error('nom')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Note minimale</label>
                            <input type="number" name="min_note" min="0" max="20" value="{{ old('min_note') }}" class="form-control @error('min_note') is-invalid @enderror">
                            @error('min_note')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Note maximale</label>
                            <input type="number" name="max_note" min="0" max="20" value="{{ old('max_note') }}" class="form-control @error('max_note') is-invalid @enderror">
                            @error('max_note')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
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
    // Vérifier si la table n'est pas déjà initialisée
    if (!$.fn.DataTable.isDataTable('#table-mentions')) {
        $('#table-mentions').DataTable({
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
