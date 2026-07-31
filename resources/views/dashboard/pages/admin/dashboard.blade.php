{{-- resources/views/admin/dashboard.blade.php --}}
@extends('dashboard.layouts.master')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h2>Administration du système</h2>
            <hr>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="row">
        <div class="col-md-3">
            <div class="card text-white bg-primary mb-3">
                <div class="card-body">
                    <h5 class="card-title">Écoles</h5>
                    <h2>{{ $totalEcoles }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success mb-3">
                <div class="card-body">
                    <h5 class="card-title">Années scolaires</h5>
                    <h2>{{ $totalAnnees }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info mb-3">
                <div class="card-body">
                    <h5 class="card-title">Classes</h5>
                    <h2>{{ $totalClasses }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning mb-3">
                <div class="card-body">
                    <h5 class="card-title">Matières</h5>
                    <h2>{{ $totalMatieres }}</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste des écoles -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Écoles</h5>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createEcoleModal">
                        <i class="fas fa-plus"></i> Nouvelle école
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Code</th>
                                    <th>Téléphone</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ecoles as $ecole)
                                <tr>
                                    <td>{{ $ecole->nom }}</td>
                                    <td>{{ $ecole->code }}</td>
                                    <td>{{ $ecole->telephone ?? '-' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Liste des années scolaires -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Années scolaires</h5>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createAnneeModal">
                        <i class="fas fa-plus"></i> Nouvelle année
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Année</th>
                                    <th>École</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($anneesScolaires as $annee)
                                <tr>
                                    <td>{{ $annee->annee }}</td>
                                    <td>{{ $annee->ecole->nom }}</td>
                                    <td>
                                        @if($annee->est_active)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-warning toggle-annee" data-id="{{ $annee->id }}">
                                            {{ $annee->est_active ? 'Désactiver' : 'Activer' }}
                                        </button>
                                        <button class="btn btn-sm btn-danger delete-annee" data-id="{{ $annee->id }}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Création École -->
<div class="modal fade" id="createEcoleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Créer une nouvelle école</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createEcoleForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nom de l'école *</label>
                        <input type="text" name="nom_ecole" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Code *</label>
                        <input type="text" name="code" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Directeur *</label>
                        <input type="text" name="directeur" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Adresse</label>
                        <input type="text" name="adresse" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Téléphone</label>
                        <input type="text" name="telephone" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Créer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Création Année Scolaire -->
<div class="modal fade" id="createAnneeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Créer une nouvelle année scolaire</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createAnneeForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">École *</label>
                        <select name="ecole_id" class="form-control" required>
                            <option value="">Sélectionner une école</option>
                            @foreach($ecoles as $ecole)
                                <option value="{{ $ecole->id }}">{{ $ecole->nom }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Année scolaire *</label>
                        <input type="text" name="annee" class="form-control" placeholder="ex: 2025-2026" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date début *</label>
                        <input type="date" name="date_debut" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date fin *</label>
                        <input type="date" name="date_fin" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="est_active" class="form-check-input" id="estActive">
                            <label class="form-check-label" for="estActive">Activer cette année</label>
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        La création d'une année scolaire va générer automatiquement toutes les tables nécessaires 
                        (élèves, paiements, notes, etc.) et migrer les données des inscriptions.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Créer</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // CSRF Token
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Toastr configuration
    toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "timeOut": "5000"
    };

    // Création d'une école
    $('#createEcoleForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        $.ajax({
            url: '{{ route("admin.ecoles.store") }}',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#createEcoleModal').modal('hide');
                    location.reload();
                }
            },
            error: function(xhr) {
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = xhr.responseJSON.errors;
                    for (const key in errors) {
                        toastr.error(errors[key][0]);
                    }
                } else {
                    toastr.error('Erreur lors de la création');
                }
            }
        });
    });

    // Création d'une année scolaire
    $('#createAnneeForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        // Afficher un message de chargement
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.text();
        submitBtn.text('Création en cours...').prop('disabled', true);
        
        $.ajax({
            url: '{{ route("admin.annees.store") }}',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    if (response.migration) {
                        toastr.info('Élèves migrés: ' + response.migration.count);
                    }
                    $('#createAnneeModal').modal('hide');
                    setTimeout(() => location.reload(), 2000);
                }
            },
            error: function(xhr) {
                submitBtn.text(originalText).prop('disabled', false);
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    toastr.error(xhr.responseJSON.message);
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = xhr.responseJSON.errors;
                    for (const key in errors) {
                        toastr.error(errors[key][0]);
                    }
                } else {
                    toastr.error('Erreur lors de la création');
                }
            }
        });
    });

    // Toggle année scolaire
    $(document).on('click', '.toggle-annee', function() {
        const id = $(this).data('id');
        const btn = $(this);
        
        $.ajax({
            url: '/admin/annees-scolaires/' + id + '/toggle',
            type: 'PATCH',
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    location.reload();
                }
            },
            error: function() {
                toastr.error('Erreur lors de l\'activation/désactivation');
            }
        });
    });

    // Supprimer une année scolaire
    $(document).on('click', '.delete-annee', function() {
        const id = $(this).data('id');
        
        if (confirm('Êtes-vous sûr de vouloir supprimer cette année scolaire ? Toutes les données associées seront supprimées.')) {
            $.ajax({
                url: '/admin/annees-scolaires/' + id,
                type: 'DELETE',
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        location.reload();
                    }
                },
                error: function() {
                    toastr.error('Erreur lors de la suppression');
                }
            });
        }
    });

    // Messages flash
    @if(session('success'))
        toastr.success("{{ session('success') }}");
    @endif
    
    @if(session('error'))
        toastr.error("{{ session('error') }}");
    @endif
});
</script>
@endsection