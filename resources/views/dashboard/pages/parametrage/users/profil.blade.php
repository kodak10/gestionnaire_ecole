@extends('dashboard.layouts.master')
@section('content')
    <div class="d-md-flex d-block align-items-center justify-content-between border-bottom pb-3">
        <div class="my-auto mb-2">
            <h3 class="page-title mb-1">Profile</h3>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('dashboard') }}">Dashboard</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0);">Parametrage</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Mon Profil</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex my-xl-auto right-content align-items-center flex-wrap">
            <div class="pe-1 mb-2">
                <a href="#" class="btn btn-outline-light bg-white btn-icon" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Refresh" data-bs-original-title="Refresh" onclick="location.reload()">
                    <i class="ti ti-refresh"></i>
                </a>
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
    
    <div class="d-md-flex d-block mt-3">
        <div class="settings-right-sidebar me-md-3 border-0">
            <div class="card">
                <div class="card-header">
                    <h5>Photo de profil</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" id="photo-form">
                        @csrf
                        @method('PUT')

                        <input type="hidden" name="update_type" value="photo">

                        <div class="settings-profile-upload">
                            <span class="profile-pic">
                                <img src="{{ $user->photo ? asset('storage/' . $user->photo) : asset('assets/img/profiles/avatar-27.jpg') }}" alt="Profile" id="profile-preview" class="rounded-circle" width="120" height="120">
                            </span>
                            <div class="title-upload">
                                <h5>Modifier votre photo</h5>
                                <a href="#" class="me-2 text-danger" id="delete-photo">Supprimer</a>
                                <button type="submit" class="text-primary btn btn-link p-0">Mettre à jour</button>
                            </div>
                        </div>
                        <div class="profile-uploader profile-uploader-two mb-0">
                            <span class="upload-icon"><i class="ti ti-upload"></i></span>
                            <div class="drag-upload-btn bg-transparent me-0 border-0">
                                <p class="upload-btn"><span>Cliquez pour télécharger</span> ou glisser-déposer
                                </p>
                                <h6>JPG ou PNG</h6>
                                <h6>(Max 450 x 450 px)</h6>
                            </div>
                            <input type="file" name="photo" class="form-control" id="photo-upload" accept="image/*">
                            <div id="frames"></div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="flex-fill ps-0 border-0">
            <form action="{{ route('profile.update') }}" method="POST" id="profile-form">
                @csrf
                @method('PUT')

                <input type="hidden" name="update_type" value="profile">

                <div class="d-md-flex">
                    <div class="flex-fill">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5>Informations personnelles</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Nom complet</label>
                                    <input type="text" name="name" class="form-control" placeholder="Entrez votre nom complet" value="{{ old('name', $user->name) }}">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nom d'utilisateur</label>
                                    <input type="text" name="pseudo" class="form-control" placeholder="Entrez votre nom d'utilisateur" value="{{ old('pseudo', $user->pseudo) }}">
                                </div>
                               
                                <div class="mb-3">
                                    <label class="form-label">Rôle</label>
                                    <input type="text" class="form-control" value="{{ $user->roles->first() ? $user->roles->first()->name : 'Aucun rôle' }}" disabled>
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-header">
                                <h5>Changer le mot de passe</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Nouveau mot de passe</label>
                                    <div class="pass-group d-flex">
                                        <input type="password" name="password" class="pass-input form-control" placeholder="Laissez vide pour ne pas changer">
                                        <span class="toggle-password ti ti-eye-off"></span>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Confirmer le nouveau mot de passe</label>
                                    <div class="pass-group d-flex">
                                        <input type="password" name="password_confirmation" class="pass-input form-control" placeholder="Confirmez le nouveau mot de passe">
                                        <span class="toggle-password ti ti-eye-off"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-primary">Mettre à jour le profil</button>
                        </div>
                    </div>
                </div>
            </form>
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

        // Photo upload preview
        document.getElementById('photo-upload').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('profile-preview').src = event.target.result;
                };
                reader.readAsDataURL(file);
            }
        });

        // Delete photo
        document.getElementById('delete-photo').addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Êtes-vous sûr de vouloir supprimer votre photo de profil ?')) {
                // Vous pouvez implémenter une requête AJAX ici pour supprimer la photo
                document.getElementById('profile-preview').src = '{{ asset('assets/img/profiles/avatar-27.jpg') }}';
                document.getElementById('photo-upload').value = '';
            }
        });

        // Empêcher la soumission du formulaire photo si aucun fichier n'est sélectionné
        document.getElementById('photo-form').addEventListener('submit', function(e) {
            const fileInput = document.getElementById('photo-upload');
            if (!fileInput.value) {
                e.preventDefault();
                alert('Veuillez sélectionner une photo avant de mettre à jour.');
            }
        });
    });
</script>
@endsection