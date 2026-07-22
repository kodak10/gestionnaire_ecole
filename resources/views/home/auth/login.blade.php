@extends('home.layout.app')

@section('content')
<div class="container-fuild">
    <div class="w-100 overflow-hidden position-relative flex-wrap d-block vh-100">
        <div class="row">
            <div class="col-lg-6">
                <div class="login-background position-relative d-lg-flex align-items-center justify-content-center d-lg-block d-none flex-wrap vh-100 overflowy-auto">
                    <div>
                        <img src="{{ asset('assets/img/authentication/authentication-02.jpg') }}" alt="Img">
                    </div>
                    <div class="authen-overlay-item w-100 p-4">
                        <h4 class="text-white mb-3">Bienvenue sur OptiScolaire !</h4>
                        <div class="d-flex align-items-center flex-row mb-3 justify-content-between p-3 br-5 gap-3 card">
                            <div>
                                <h6>Gestion des élèves</h6>
                                <p class="mb-0 text-truncate">Inscrivez et gérez les informations de tous vos élèves.</p>
                            </div>
                            <a href="javascript:void(0);"><i class="ti ti-chevrons-right"></i></a>
                        </div>
                        <div class="d-flex align-items-center flex-row mb-3 justify-content-between p-3 br-5 gap-3 card">
                            <div>
                                <h6>Suivi des paiements</h6>
                                <p class="mb-0 text-truncate">Gérez les frais de scolarité et suivez les paiements.</p>
                            </div>
                            <a href="javascript:void(0);"><i class="ti ti-chevrons-right"></i></a>
                        </div>
                        <div class="d-flex align-items-center flex-row mb-3 justify-content-between p-3 br-5 gap-3 card">
                            <div>
                                <h6>Rapports détaillés</h6>
                                <p class="mb-0 text-truncate">Générez des rapports sur la performance de votre école.</p>
                            </div>
                            <a href="javascript:void(0);"><i class="ti ti-chevrons-right"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-12 col-sm-12">
                <div class="row justify-content-center align-items-center vh-100 overflow-auto flex-wrap">
                    <div class="col-md-8 mx-auto p-4">
                        <form method="POST" action="{{ route('login') }}" id="loginForm">
                            @csrf
                            <div>
                                <div class="mx-auto mb-3 text-center">
                                    <h3 class="mt-3">{{ config('app.name', 'SchoolManager') }}</h3>
                                </div>
                                <div class="card">
                                    <div class="card-body p-4">
                                        <div class="mb-4">
                                            <h2 class="mb-2">Connexion</h2>
                                            <p class="mb-0">Veuillez entrer vos identifiants</p>
                                        </div>

                                        @if(session('error'))
                                            <div class="alert alert-danger">{{ session('error') }}</div>
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

                                        <div class="mb-3">
                                            <!-- Sélection de l'école -->
                                            <label class="form-label">École</label>
                                            <div class="input-icon mb-3 position-relative">
                                                <span class="input-icon-addon">
                                                    <i class="ti ti-school"></i>
                                                </span>
                                                <select name="ecole_id" 
                                                        id="ecoleSelect"
                                                        class="form-control @error('ecole_id') is-invalid @enderror"
                                                        required>
                                                    <option value="">Sélectionnez une école</option>
                                                    @foreach($ecoles as $ecole)
                                                        <option value="{{ $ecole->id }}" 
                                                                {{ old('ecole_id') == $ecole->id ? 'selected' : '' }}>
                                                            {{ $ecole->nom_ecole }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('ecole_id')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>

                                            <!-- Sélection de l'année scolaire -->
                                            <label class="form-label">Année Scolaire</label>
                                            <div class="input-icon mb-3 position-relative">
                                                <span class="input-icon-addon">
                                                    <i class="ti ti-calendar"></i>
                                                </span>
                                                <select name="annee_scolaire_id" 
                                                        id="anneeScolaireSelect"
                                                        class="form-control @error('annee_scolaire_id') is-invalid @enderror"
                                                        required>
                                                    <option value="">Sélectionnez d'abord une école</option>
                                                    @if(old('ecole_id'))
                                                        @foreach($ecoles as $ecole)
                                                            @if($ecole->id == old('ecole_id'))
                                                                @foreach($ecole->anneesScolaires as $annee)
                                                                    <option value="{{ $annee->id }}" 
                                                                            {{ old('annee_scolaire_id') == $annee->id ? 'selected' : '' }}>
                                                                        {{ $annee->annee }}
                                                                    </option>
                                                                @endforeach
                                                            @endif
                                                        @endforeach
                                                    @endif
                                                </select>
                                                @error('annee_scolaire_id')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>

                                            <!-- Nom d'utilisateur -->
                                            <label class="form-label">Nom d'utilisateur</label>
                                            <div class="input-icon mb-3 position-relative">
                                                <span class="input-icon-addon">
                                                    <i class="ti ti-user"></i>
                                                </span>
                                                <input type="text" name="pseudo" value="{{ old('pseudo') }}" 
                                                       class="form-control @error('pseudo') is-invalid @enderror" 
                                                       required autofocus>
                                                @error('pseudo')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>

                                            <!-- Mot de passe -->
                                            <label class="form-label">Mot de passe</label>
                                            <div class="pass-group">
                                                <input type="password" name="password" 
                                                       class="pass-input form-control @error('password') is-invalid @enderror" 
                                                       required>
                                                <span class="ti toggle-password ti-eye-off"></span>
                                                @error('password')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>
                                        </div>
            
                                        <div class="form-wrap form-wrap-checkbox mb-3">
                                            <div class="d-flex align-items-center">
                                                <div class="form-check form-check-md mb-0">
                                                    <input class="form-check-input mt-0" type="checkbox" 
                                                           name="remember" id="remember" 
                                                           {{ old('remember') ? 'checked' : '' }}>
                                                </div>
                                                <label class="ms-1 mb-0" for="remember">Se souvenir de moi</label>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <button type="submit" class="btn btn-primary w-100">Se connecter</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3 text-center">
                                    <p class="mb-0">Copyright &copy; {{ date('Y') }} - {{ config('app.name', 'OptiScolaire') }}</p>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle password visibility
        document.querySelectorAll('.toggle-password').forEach(function(button) {
            button.addEventListener('click', function() {
                const input = this.previousElementSibling;
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                this.classList.toggle('ti-eye');
                this.classList.toggle('ti-eye-off');
            });
        });

        // Éléments du DOM
        const ecoleSelect = document.getElementById('ecoleSelect');
        const anneeSelect = document.getElementById('anneeScolaireSelect');
        const loginForm = document.getElementById('loginForm');

        // Fonction pour charger les années scolaires d'une école
        function loadAnneesScolaires(ecoleId, selectedAnneeId = null) {
            if (!ecoleId) {
                anneeSelect.innerHTML = '<option value="">Sélectionnez d\'abord une école</option>';
                anneeSelect.disabled = true;
                return;
            }

            anneeSelect.innerHTML = '<option value="">Chargement...</option>';
            anneeSelect.disabled = true;

            fetch(`/ecoles/${ecoleId}/annees-scolaires`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Erreur HTTP: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    anneeSelect.disabled = false;
                    
                    if (!Array.isArray(data) || data.length === 0) {
                        anneeSelect.innerHTML = '<option value="">Aucune année scolaire disponible</option>';
                        return;
                    }

                    let options = '';
                    let activeAnneeId = null;
                    
                    data.forEach(annee => {
                        const isActive = annee.est_active == 1 || annee.est_active === true;
                        const selected = (selectedAnneeId && annee.id == selectedAnneeId) ? 'selected' : '';
                        options += `<option value="${annee.id}" ${selected}>
                            ${annee.annee}
                        </option>`;
                        
                        if (isActive) {
                            activeAnneeId = annee.id;
                        }
                    });

                    anneeSelect.innerHTML = options;

                    // Sélectionner automatiquement l'année active si aucune n'est sélectionnée
                    if (activeAnneeId && !selectedAnneeId) {
                        anneeSelect.value = activeAnneeId;
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    anneeSelect.innerHTML = `<option value="">Erreur: ${error.message}</option>`;
                    anneeSelect.disabled = false;
                });
        }

        // Événement de changement d'école
        ecoleSelect.addEventListener('change', function() {
            const ecoleId = this.value;
            loadAnneesScolaires(ecoleId);
            document.querySelector('input[name="pseudo"]').value = '';
        });

        // Si une école est déjà sélectionnée (après erreur de validation)
        const oldEcoleId = {{ old('ecole_id') ?? 'null' }};
        const oldAnneeId = {{ old('annee_scolaire_id') ?? 'null' }};
        
        if (oldEcoleId) {
            // Charger les années avec l'ancienne sélection
            loadAnneesScolaires(oldEcoleId, oldAnneeId);
            // Sélectionner l'école
            ecoleSelect.value = oldEcoleId;
        }

        // Validation du formulaire
        loginForm.addEventListener('submit', function(e) {
            const ecoleValue = ecoleSelect.value;
            const anneeValue = anneeSelect.value;
            const pseudoValue = document.querySelector('input[name="pseudo"]').value;
            const passwordValue = document.querySelector('input[name="password"]').value;

            if (!ecoleValue) {
                e.preventDefault();
                alert('Veuillez sélectionner une école');
                ecoleSelect.focus();
                return false;
            }

            if (!anneeValue) {
                e.preventDefault();
                alert('Veuillez sélectionner une année scolaire');
                anneeSelect.focus();
                return false;
            }

            if (!pseudoValue) {
                e.preventDefault();
                alert('Veuillez entrer votre nom d\'utilisateur');
                document.querySelector('input[name="pseudo"]').focus();
                return false;
            }

            if (!passwordValue) {
                e.preventDefault();
                alert('Veuillez entrer votre mot de passe');
                document.querySelector('input[name="password"]').focus();
                return false;
            }

            return true;
        });
    });
</script>
@endsection