@extends('home.layout.app')

@section('content')
<div class="container-fuild">
    <div class="w-100 overflow-hidden position-relative flex-wrap d-block vh-100">
        <div class="row g-0">
            <!-- Section gauche - Présentation -->
            <div class="col-lg-6 d-none d-lg-flex">
                <div class="login-background position-relative d-flex align-items-center justify-content-center vh-100 overflowy-auto" 
                     style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);">
                    <div class="text-center p-5">
                        <div class="mb-5">
                            <img src="{{ asset('assets/img/logo-white.png') }}" alt="OptiScolaire" height="60">
                        </div>
                        <h2 class="text-white display-4 fw-bold mb-4">Bienvenue sur <br>OptiScolaire</h2>
                        <p class="text-white-50 fs-5 mb-5">La solution complète pour la gestion scolaire</p>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="card bg-white bg-opacity-10 border-0 h-100">
                                    <div class="card-body text-center text-white">
                                        <div class="display-6 mb-2">📚</div>
                                        <h5>Gestion des élèves</h5>
                                        <p class="text-white-50 small">Inscrivez et gérez tous vos élèves</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-white bg-opacity-10 border-0 h-100">
                                    <div class="card-body text-center text-white">
                                        <div class="display-6 mb-2">💰</div>
                                        <h5>Suivi des paiements</h5>
                                        <p class="text-white-50 small">Gérez les frais de scolarité</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="card bg-white bg-opacity-10 border-0">
                                    <div class="card-body text-center text-white">
                                        <div class="display-6 mb-2">📊</div>
                                        <h5>Rapports détaillés</h5>
                                        <p class="text-white-50 small">Générez des rapports de performance</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section droite - Formulaire -->
            <div class="col-lg-6 col-md-12">
                <div class="d-flex align-items-center justify-content-center vh-100 p-4" 
                     style="background: #f8f9fa;">
                    <div class="w-100" style="max-width: 480px;">
                        <!-- Logo mobile -->
                        <div class="text-center d-lg-none mb-4">
                            <h2 class="fw-bold" style="color: #0f3460;">OptiScolaire</h2>
                        </div>

                        <!-- Carte de connexion -->
                        <div class="card border-0 shadow-lg">
                            <div class="card-body p-5">
                                <div class="text-center mb-4">
                                    <h3 class="fw-bold" style="color: #0f3460;">Connexion</h3>
                                    <p class="text-muted">Connectez-vous à votre espace</p>
                                </div>

                                @if(session('error'))
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <i class="ti ti-alert-circle me-2"></i>
                                        {{ session('error') }}
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                @endif

                                @if($errors->any())
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <ul class="mb-0 ps-3">
                                            @foreach($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                @endif

                                <form method="POST" action="{{ route('login') }}" id="loginForm">
                                    @csrf

                                    <!-- École -->
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Établissement</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0">
                                                <i class="ti ti-school text-muted"></i>
                                            </span>
                                            <select name="ecole_id" 
                                                    id="ecoleSelect"
                                                    class="form-control border-start-0 @error('ecole_id') is-invalid @enderror"
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
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- Année scolaire -->
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Année scolaire</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0">
                                                <i class="ti ti-calendar text-muted"></i>
                                            </span>
                                            <select name="annee_scolaire_id" 
                                                    id="anneeScolaireSelect"
                                                    class="form-control border-start-0 @error('annee_scolaire_id') is-invalid @enderror"
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
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- Nom d'utilisateur -->
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Nom d'utilisateur</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0">
                                                <i class="ti ti-user text-muted"></i>
                                            </span>
                                            <input type="text" name="pseudo" value="{{ old('pseudo') }}" 
                                                   class="form-control border-start-0 @error('pseudo') is-invalid @enderror" 
                                                   placeholder="Entrez votre pseudo"
                                                   required autofocus>
                                            @error('pseudo')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- Mot de passe -->
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Mot de passe</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0">
                                                <i class="ti ti-lock text-muted"></i>
                                            </span>
                                            <input type="password" name="password" 
                                                   class="form-control border-start-0 border-end-0 @error('password') is-invalid @enderror" 
                                                   placeholder="Entrez votre mot de passe"
                                                   required>
                                            <span class="input-group-text bg-light border-start-0 toggle-password" 
                                                  style="cursor: pointer;">
                                                <i class="ti ti-eye-off text-muted"></i>
                                            </span>
                                            @error('password')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- Options -->
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                   name="remember" id="remember" 
                                                   {{ old('remember') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="remember">
                                                Se souvenir de moi
                                            </label>
                                        </div>
                                        <a href="#" class="text-decoration-none" style="color: #0f3460;">
                                            Mot de passe oublié ?
                                        </a>
                                    </div>

                                    <!-- Bouton -->
                                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold" 
                                            style="background: #0f3460; border: none;">
                                        <i class="ti ti-login me-2"></i>
                                        Se connecter
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="text-center mt-4">
                            <p class="text-muted small mb-0">
                                &copy; {{ date('Y') }} {{ config('app.name', 'OptiScolaire') }} - Tous droits réservés
                            </p>
                        </div>
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
                const icon = this.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.replace('ti-eye-off', 'ti-eye');
                } else {
                    input.type = 'password';
                    icon.classList.replace('ti-eye', 'ti-eye-off');
                }
            });
        });

        // Éléments du DOM
        const ecoleSelect = document.getElementById('ecoleSelect');
        const anneeSelect = document.getElementById('anneeScolaireSelect');
        const loginForm = document.getElementById('loginForm');

        // Fonction pour charger les années scolaires
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
                        anneeSelect.innerHTML = '<option value="">Aucune année disponible</option>';
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
                        
                        if (isActive && !selectedAnneeId) {
                            activeAnneeId = annee.id;
                        }
                    });

                    anneeSelect.innerHTML = options;

                    // Sélectionner automatiquement l'année active
                    if (activeAnneeId) {
                        anneeSelect.value = activeAnneeId;
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    anneeSelect.innerHTML = `<option value="">Erreur de chargement</option>`;
                    anneeSelect.disabled = false;
                });
        }

        // Événement de changement d'école
        ecoleSelect.addEventListener('change', function() {
            const ecoleId = this.value;
            loadAnneesScolaires(ecoleId);
        });

        // Chargement initial si une école est pré-sélectionnée
        const oldEcoleId = {{ old('ecole_id') ?? 'null' }};
        const oldAnneeId = {{ old('annee_scolaire_id') ?? 'null' }};
        
        if (oldEcoleId) {
            loadAnneesScolaires(oldEcoleId, oldAnneeId);
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
                ecoleSelect.focus();
                return false;
            }

            if (!anneeValue) {
                e.preventDefault();
                anneeSelect.focus();
                return false;
            }

            if (!pseudoValue) {
                e.preventDefault();
                document.querySelector('input[name="pseudo"]').focus();
                return false;
            }

            if (!passwordValue) {
                e.preventDefault();
                document.querySelector('input[name="password"]').focus();
                return false;
            }

            return true;
        });
    });
</script>

<!-- Styles additionnels -->
<style>
    .login-background {
        position: relative;
        overflow: hidden;
    }
    
    .login-background::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle at 30% 40%, rgba(255,255,255,0.05) 0%, transparent 60%);
        animation: pulse 8s ease-in-out infinite;
    }
    
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.1); }
    }
    
    .card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.1) !important;
    }
    
    .input-group-text {
        background: #f8f9fa;
        border-color: #dee2e6;
    }
    
    .form-control:focus {
        border-color: #0f3460;
        box-shadow: 0 0 0 0.2rem rgba(15, 52, 96, 0.15);
    }
    
    .btn-primary:hover {
        background: #1a1a2e !important;
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(15, 52, 96, 0.3);
    }
    
    .btn-primary:active {
        transform: translateY(0);
    }
    
    .toggle-password:hover {
        background: #e9ecef !important;
    }
    
    /* Responsive */
    @media (max-width: 991.98px) {
        .vh-100 {
            height: auto !important;
            min-height: 100vh;
        }
        
        .card-body {
            padding: 2rem !important;
        }
    }
    
    @media (max-width: 575.98px) {
        .card-body {
            padding: 1.5rem !important;
        }
        
        .display-4 {
            font-size: 2rem !important;
        }
    }
</style>
@endsection