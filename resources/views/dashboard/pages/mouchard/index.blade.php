@extends('dashboard.layouts.master')

@section('content')

<!-- Page Header -->
<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
    <div class="my-auto mb-2">
        <h3 class="page-title mb-1">Gestion des Enseignants</h3>
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
            <a href="{{ route('enseignants.index') }}" class="btn btn-outline-light bg-white btn-icon me-1" data-bs-toggle="tooltip" title="Actualiser">
                <i class="ti ti-refresh"></i>
            </a>
            <a href="{{ route('enseignants.export') }}" class="btn btn-outline-light bg-white btn-icon me-1" data-bs-toggle="tooltip" title="Exporter">
                <i class="ti ti-file-export"></i>
            </a>
            <a href="{{ route('enseignants.import') }}" class="btn btn-outline-light bg-white btn-icon me-1" data-bs-toggle="tooltip" title="Importer">
                <i class="ti ti-file-import"></i>
            </a>
        </div>

        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEnseignantModal">
            <i class="ti ti-square-rounded-plus-filled me-2"></i>Ajouter un Enseignant
        </button>
    </div>
</div>
<!-- /Page Header -->

<!-- Statistiques rapides -->
<div class="row mb-3">
    <div class="col-md-3 col-sm-6">
        <div class="card">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted">Total Enseignants</span>
                    <h3 class="mb-0">{{ $totalEnseignants ?? 0 }}</h3>
                </div>
                <div class="avatar bg-primary bg-opacity-10 text-primary p-2 rounded">
                    <i class="ti ti-users fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted">Enseignants Actifs</span>
                    <h3 class="mb-0">{{ $enseignantsActifs ?? 0 }}</h3>
                </div>
                <div class="avatar bg-success bg-opacity-10 text-success p-2 rounded">
                    <i class="ti ti-user-check fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted">Enseignants Inactifs</span>
                    <h3 class="mb-0">{{ $enseignantsInactifs ?? 0 }}</h3>
                </div>
                <div class="avatar bg-danger bg-opacity-10 text-danger p-2 rounded">
                    <i class="ti ti-user-off fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted">Matières Assignées</span>
                    <h3 class="mb-0">{{ $totalMatieres ?? 0 }}</h3>
                </div>
                <div class="avatar bg-warning bg-opacity-10 text-warning p-2 rounded">
                    <i class="ti ti-book fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="ti ti-check-circle me-2"></i>
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="ti ti-alert-circle me-2"></i>
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="ti ti-alert-circle me-2"></i>
    <ul class="mb-0">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<!-- Filtres de recherche avancée -->
<div class="card mb-3">
    <div class="card-body">
        <form action="{{ route('enseignants.index') }}" method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Recherche</label>
                <input type="text" name="search" class="form-control" placeholder="Nom, téléphone..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Statut</label>
                <select name="status" class="form-select">
                    <option value="">Tous</option>
                    <option value="actif" {{ request('status') == 'actif' ? 'selected' : '' }}>Actif</option>
                    <option value="inactif" {{ request('status') == 'inactif' ? 'selected' : '' }}>Inactif</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-search me-1"></i> Filtrer
                </button>
                <a href="{{ route('enseignants.index') }}" class="btn btn-light ms-2">
                    <i class="ti ti-rotate me-1"></i> Réinitialiser
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between py-3">
        <h5 class="card-title mb-0">Liste des Enseignants</h5>
        <span class="badge bg-primary">{{ $enseignants->total() ?? 0 }} enseignant(s)</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped" id="table-enseignants">
                <thead class="table-light">
                    <tr>
                        <th width="50">#</th>
                        <th>Nom complet</th>
                        <th>Téléphone</th>
                        <th>Email</th>
                        <th>Spécialités</th>
                        <th>Statut</th>
                        <th>Date d'ajout</th>
                        <th width="150" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($enseignants as $enseignant)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-sm me-2">
                                    @if($enseignant->photo)
                                        <img src="{{ asset('storage/' . $enseignant->photo) }}" alt="{{ $enseignant->nom_prenoms }}" class="rounded-circle">
                                    @else
                                        <span class="avatar-initials rounded-circle bg-primary text-white">
                                            {{ Str::limit($enseignant->nom_prenoms, 2, '') }}
                                        </span>
                                    @endif
                                </div>
                                <div>
                                    <h6 class="mb-0">{{ $enseignant->nom_prenoms }}</h6>
                                    <small class="text-muted">ID: {{ $enseignant->id }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <a href="tel:{{ $enseignant->telephone }}" class="text-decoration-none">
                                <i class="ti ti-phone me-1"></i>
                                {{ $enseignant->telephone }}
                            </a>
                        </td>
                        <td>
                            @if($enseignant->email)
                                <a href="mailto:{{ $enseignant->email }}" class="text-decoration-none">
                                    <i class="ti ti-mail me-1"></i>
                                    {{ $enseignant->email }}
                                </a>
                            @else
                                <span class="text-muted">Non renseigné</span>
                            @endif
                        </td>
                        <td>
                            @if($enseignant->matieres->count() > 0)
                                @foreach($enseignant->matieres->take(2) as $matiere)
                                    <span class="badge bg-info me-1">{{ $matiere->nom }}</span>
                                @endforeach
                                @if($enseignant->matieres->count() > 2)
                                    <span class="badge bg-secondary">+{{ $enseignant->matieres->count() - 2 }}</span>
                                @endif
                            @else
                                <span class="text-muted">Aucune</span>
                            @endif
                        </td>
                        <td>
                            @if($enseignant->is_active)
                                <span class="badge bg-success bg-opacity-10 text-success">
                                    <i class="ti ti-circle-check me-1"></i>Actif
                                </span>
                            @else
                                <span class="badge bg-danger bg-opacity-10 text-danger">
                                    <i class="ti ti-circle-x me-1"></i>Inactif
                                </span>
                            @endif
                        </td>
                        <td>
                            <small>{{ $enseignant->created_at ? $enseignant->created_at->format('d/m/Y') : '-' }}</small>
                        </td>
                        <td>
                            <div class="d-flex gap-1 justify-content-center">
                                <!-- Bouton Voir Détails -->
                                <button class="btn btn-white btn-icon btn-sm" data-bs-toggle="modal" data-bs-target="#viewEnseignantModal_{{ $enseignant->id }}" title="Voir les détails">
                                    <i class="ti ti-eye text-info"></i>
                                </button>

                                <!-- Bouton Modifier -->
                                <button class="btn btn-white btn-icon btn-sm" data-bs-toggle="modal" data-bs-target="#editEnseignantModal_{{ $enseignant->id }}" title="Modifier">
                                    <i class="ti ti-edit-circle text-primary"></i>
                                </button>

                                <!-- Bouton Changer Statut -->
                                <button class="btn btn-white btn-icon btn-sm" data-bs-toggle="modal" data-bs-target="#statusEnseignantModal_{{ $enseignant->id }}" title="Changer le statut">
                                    <i class="ti ti-toggle-right text-warning"></i>
                                </button>

                                <!-- Bouton Supprimer -->
                                <form action="{{ route('enseignants.destroy', $enseignant->id) }}" method="POST" class="d-inline delete-form" data-entity="enseignant">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-white btn-icon btn-sm" title="Supprimer" data-bs-toggle="tooltip">
                                        <i class="ti ti-trash-x text-danger"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>

                    <!-- Modal Visualisation -->
                    <div class="modal fade" id="viewEnseignantModal_{{ $enseignant->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Détails de l'Enseignant</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-4 text-center">
                                            <div class="avatar avatar-xl mb-3">
                                                @if($enseignant->photo)
                                                    <img src="{{ asset('storage/' . $enseignant->photo) }}" alt="{{ $enseignant->nom_prenoms }}" class="rounded-circle">
                                                @else
                                                    <span class="avatar-initials rounded-circle bg-primary text-white" style="width: 100px; height: 100px; display: flex; align-items: center; justify-content: center; font-size: 40px;">
                                                        {{ Str::limit($enseignant->nom_prenoms, 2, '') }}
                                                    </span>
                                                @endif
                                            </div>
                                            <h5>{{ $enseignant->nom_prenoms }}</h5>
                                            <span class="badge {{ $enseignant->is_active ? 'bg-success' : 'bg-danger' }}">
                                                {{ $enseignant->is_active ? 'Actif' : 'Inactif' }}
                                            </span>
                                        </div>
                                        <div class="col-md-8">
                                            <table class="table table-borderless">
                                                <tr>
                                                    <th width="150">Téléphone</th>
                                                    <td>{{ $enseignant->telephone ?? '-' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Email</th>
                                                    <td>{{ $enseignant->email ?? '-' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Adresse</th>
                                                    <td>{{ $enseignant->adresse ?? '-' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Spécialités</th>
                                                    <td>
                                                        @if($enseignant->matieres->count() > 0)
                                                            @foreach($enseignant->matieres as $matiere)
                                                                <span class="badge bg-info me-1">{{ $matiere->nom }}</span>
                                                            @endforeach
                                                        @else
                                                            <span class="text-muted">Aucune</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Date d'inscription</th>
                                                    <td>{{ $enseignant->created_at ? $enseignant->created_at->format('d/m/Y à H:i') : '-' }}</td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Fermer</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Édition -->
                    <div class="modal fade" id="editEnseignantModal_{{ $enseignant->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <form action="{{ route('enseignants.update', $enseignant->id) }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-header">
                                        <h5 class="modal-title">Modifier l'Enseignant</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3 text-center">
                                            <div class="avatar avatar-lg mb-2">
                                                @if($enseignant->photo)
                                                    <img src="{{ asset('storage/' . $enseignant->photo) }}" alt="{{ $enseignant->nom_prenoms }}" class="rounded-circle" id="editPhotoPreview_{{ $enseignant->id }}">
                                                @else
                                                    <span class="avatar-initials rounded-circle bg-primary text-white" style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center; font-size: 32px;" id="editPhotoPreview_{{ $enseignant->id }}">
                                                        {{ Str::limit($enseignant->nom_prenoms, 2, '') }}
                                                    </span>
                                                @endif
                                            </div>
                                            <input type="file" name="photo" class="form-control form-control-sm" accept="image/*" id="editPhotoInput_{{ $enseignant->id }}" onchange="previewEditPhoto(this, {{ $enseignant->id }})">
                                        </div>

                                        <div class="row">
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">Nom & Prénoms <span class="text-danger">*</span></label>
                                                <input type="text" name="nom_prenoms" value="{{ old('nom_prenoms', $enseignant->nom_prenoms) }}" class="form-control @error('nom_prenoms') is-invalid @enderror" required>
                                                @error('nom_prenoms')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Téléphone <span class="text-danger">*</span></label>
                                                <input type="text" name="telephone" value="{{ old('telephone', $enseignant->telephone) }}" class="form-control @error('telephone') is-invalid @enderror" required>
                                                @error('telephone')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Email</label>
                                                <input type="email" name="email" value="{{ old('email', $enseignant->email) }}" class="form-control @error('email') is-invalid @enderror">
                                                @error('email')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Adresse</label>
                                            <textarea name="adresse" class="form-control @error('adresse') is-invalid @enderror" rows="2">{{ old('adresse', $enseignant->adresse) }}</textarea>
                                            @error('adresse')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Matières enseignées</label>
                                            <select name="matieres[]" class="form-select select2" multiple>
                                                @foreach($matieres as $matiere)
                                                    <option value="{{ $matiere->id }}" {{ $enseignant->matieres->contains($matiere->id) ? 'selected' : '' }}>
                                                        {{ $matiere->nom }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="editStatus_{{ $enseignant->id }}" {{ $enseignant->is_active ? 'checked' : '' }}>
                                                <label class="form-check-label" for="editStatus_{{ $enseignant->id }}">Actif</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                                        <button type="submit" class="btn btn-primary">Mettre à jour</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Changement Statut -->
                    <div class="modal fade" id="statusEnseignantModal_{{ $enseignant->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <form action="{{ route('enseignants.status', $enseignant->id) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <div class="modal-header">
                                        <h5 class="modal-title">Changer le Statut</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p>Êtes-vous sûr de vouloir changer le statut de <strong>{{ $enseignant->nom_prenoms }}</strong> ?</p>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="statusSwitch_{{ $enseignant->id }}" {{ $enseignant->is_active ? 'checked' : '' }}>
                                            <label class="form-check-label" for="statusSwitch_{{ $enseignant->id }}">
                                                {{ $enseignant->is_active ? 'Actif' : 'Inactif' }}
                                            </label>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                                        <button type="submit" class="btn btn-warning">Changer le statut</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <div class="text-muted">
                                <i class="ti ti-users fs-1 d-block mb-2"></i>
                                <p>Aucun enseignant enregistré pour le moment.</p>
                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addEnseignantModal">
                                    <i class="ti ti-plus me-1"></i> Ajouter le premier enseignant
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                Affichage de {{ $enseignants->firstItem() ?? 0 }} à {{ $enseignants->lastItem() ?? 0 }} sur {{ $enseignants->total() ?? 0 }} entrées
            </div>
            <div>
                {{ $enseignants->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Modal Ajout -->
<div class="modal fade" id="addEnseignantModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form action="{{ route('enseignants.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter un Enseignant</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3 text-center">
                        <div class="avatar avatar-lg mb-2">
                            <span class="avatar-initials rounded-circle bg-primary text-white" style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center; font-size: 32px;" id="photoPreview">
                                <i class="ti ti-user"></i>
                            </span>
                        </div>
                        <input type="file" name="photo" class="form-control form-control-sm" accept="image/*" id="photoInput" onchange="previewPhoto(this)">
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Nom & Prénoms <span class="text-danger">*</span></label>
                            <input type="text" name="nom_prenoms" value="{{ old('nom_prenoms') }}" class="form-control @error('nom_prenoms') is-invalid @enderror" required>
                            @error('nom_prenoms')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Téléphone <span class="text-danger">*</span></label>
                            <input type="text" name="telephone" value="{{ old('telephone') }}" class="form-control @error('telephone') is-invalid @enderror" required>
                            @error('telephone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Adresse</label>
                        <textarea name="adresse" class="form-control @error('adresse') is-invalid @enderror" rows="2">{{ old('adresse') }}</textarea>
                        @error('adresse')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Matières enseignées</label>
                        <select name="matieres[]" class="form-select select2" multiple>
                            @foreach($matieres ?? [] as $matiere)
                                <option value="{{ $matiere->id }}">{{ $matiere->nom }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="addStatus" checked>
                            <label class="form-check-label" for="addStatus">Actif</label>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-plus me-1"></i> Ajouter
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function () {
    // Initialisation de DataTable
    if ($.fn.DataTable) {
        if (!$.fn.DataTable.isDataTable('#table-enseignants')) {
            $('#table-enseignants').DataTable({
                paging: true,
                searching: true,
                ordering: true,
                info: true,
                responsive: true,
                pageLength: 25,
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json'
                }
            });
        }
    }

    // Initialisation de Select2
    $('.select2').select2({
        theme: 'bootstrap-5',
        placeholder: 'Sélectionnez des matières',
        allowClear: true
    });

    // Tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Confirmation de suppression améliorée
    $('.delete-form').on('submit', function(e) {
        e.preventDefault();
        var form = this;
        var entity = $(this).data('entity') || 'élément';
        
        Swal.fire({
            title: 'Êtes-vous sûr ?',
            text: "Vous ne pourrez pas revenir en arrière !",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Oui, supprimer !',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });

    // Gestion du switch de statut dans le modal
    $('[id^="statusSwitch_"]').on('change', function() {
        var label = $(this).closest('.form-check').find('.form-check-label');
        if ($(this).is(':checked')) {
            label.text('Actif');
        } else {
            label.text('Inactif');
        }
    });

    // Auto-dismiss des alertes après 5 secondes
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
});

// Fonction pour la prévisualisation de la photo d'ajout
function previewPhoto(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            $('#photoPreview').html('<img src="' + e.target.result + '" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover;">');
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Fonction pour la prévisualisation de la photo d'édition
function previewEditPhoto(input, id) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            $('#editPhotoPreview_' + id).html('<img src="' + e.target.result + '" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover;">');
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Gestion du changement de statut dans le modal d'ajout
document.addEventListener('DOMContentLoaded', function() {
    var statusSwitch = document.getElementById('addStatus');
    if (statusSwitch) {
        statusSwitch.addEventListener('change', function() {
            var label = this.closest('.form-check').querySelector('.form-check-label');
            if (this.checked) {
                label.textContent = 'Actif';
            } else {
                label.textContent = 'Inactif';
            }
        });
    }
});
</script>
@endsection