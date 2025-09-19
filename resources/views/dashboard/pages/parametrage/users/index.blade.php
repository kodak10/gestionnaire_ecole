@extends('dashboard.layouts.master')
@section('content')
    <div class="d-md-flex d-block align-items-center justify-content-between border-bottom pb-3">
        <div class="my-auto mb-2">
            <h3 class="page-title mb-1">Gestion des Utilisateurs</h3>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('dashboard') }}">Dashboard</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Utilisateurs</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex my-xl-auto right-content align-items-center flex-wrap">
            <div class="pe-1 mb-2">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
                    <i class="ti ti-plus me-2"></i>Nouvel Utilisateur
                </button>
            </div>
        </div>
    </div>
    
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row mt-4">
        @foreach($users as $user)
        <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-md me-3">
                                <img src="{{ $user->photo ? asset('storage/' . $user->photo) : asset('assets/img/profiles/avatar-27.jpg') }}" alt="Avatar" class="rounded-circle">
                            </div>
                            <div>
                                <h5 class="mb-0">{{ $user->name }}</h5>
                                <span class="text-muted">{{ $user->pseudo }}</span>
                            </div>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input toggle-status" type="checkbox" data-user-id="{{ $user->id }}" {{ $user->is_active ? 'checked' : '' }}>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-flex align-items-center mb-2">
                            <i class="ti ti-briefcase me-2"></i>
                            <span class="text-muted">Rôle:</span>
                            <span class="ms-2">{{ $user->roles->first() ? $user->roles->first()->name : 'Aucun rôle' }}</span>
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <i class="ti ti-school me-2"></i>
                            <span class="text-muted">École:</span>
                            <span class="ms-2">{{ $user->ecole->nom ?? 'Non assigné' }}</span>
                        </div>
                        
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <button class="btn btn-outline-secondary btn-sm reset-password" data-user-id="{{ $user->id }}" data-user-name="{{ $user->name }}">
                            <i class="ti ti-key me-1"></i>Réinit. MDP
                        </button>
                        {{-- <a href="{{ route('users.edit', $user->id) }}" class="btn btn-outline-primary btn-sm">
                            <i class="ti ti-edit me-1"></i>Modifier
                        </a> --}}
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Modal de création d'utilisateur -->
    <div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createUserModalLabel">Créer un nouvel utilisateur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('users.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Nom complet *</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Nom d'utilisateur *</label>
                                    <input type="text" name="pseudo" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Rôle *</label>
                                    <select name="role" class="form-select" required>
                                        <option value="">Sélectionner un rôle</option>
                                        @foreach($roles as $role)
                                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>                        
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Créer l'utilisateur</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle password visibility
        const togglePasswordButtons = document.querySelectorAll('.toggle-password');
        togglePasswordButtons.forEach(button => {
            button.addEventListener('click', function() {
                const input = this.previousElementSibling;
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                this.classList.toggle('ti-eye-off');
                this.classList.toggle('ti-eye');
            });
        });

        // Toggle user status
        document.querySelectorAll('.toggle-status').forEach(toggle => {
            toggle.addEventListener('change', function() {
                const userId = this.getAttribute('data-user-id');
                const isActive = this.checked ? 1 : 0;
                
                fetch(`/users/${userId}/toggle-status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ is_active: isActive })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        toastr.success('Statut mis à jour avec succès');
                    } else {
                        toastr.error('Erreur lors de la mise à jour du statut');
                        this.checked = !this.checked;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    toastr.error('Erreur lors de la mise à jour du statut');
                    this.checked = !this.checked;
                });
            });
        });

        // Reset password
        document.querySelectorAll('.reset-password').forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                const userName = this.getAttribute('data-user-name');
                
                if (confirm(`Êtes-vous sûr de vouloir réinitialiser le mot de passe de ${userName} ? Le mot de passe par défaut sera "password".`)) {
                    fetch(`/users/${userId}/reset-password`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            toastr.success('Mot de passe réinitialisé avec succès');
                        } else {
                            toastr.error('Erreur lors de la réinitialisation du mot de passe');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        toastr.error('Erreur lors de la réinitialisation du mot de passe');
                    });
                }
            });
        });
    });
</script>
@endsection