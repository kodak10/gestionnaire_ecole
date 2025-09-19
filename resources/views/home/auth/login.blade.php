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
                        <form method="POST" action="{{ route('login') }}">
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
                                            <label class="form-label">Ecole / Année Scolaire </label>
                                            <div class="input-icon mb-3 position-relative">
                                                <span class="input-icon-addon">
                                                    <i class="ti ti-calendar"></i>
                                                </span>
                                               
                                                <select name="user_ecole_annee" class="form-control @error('user_ecole_annee') is-invalid @enderror" required>
                                                    <option value="">Sélectionnez votre école et année scolaire</option>
                                                    @foreach($anneesScolaires as $annee)
                                                        <option value="{{ $annee->ecole_id }}_{{ $annee->id }}">
                                                            {{ $annee->ecole->nom_ecole }} - {{ $annee->annee }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('user_ecole_annee')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                                @error('ecole_id')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>

                                            <label class="form-label">Nom d'utilisateur</label>
                                            <div class="input-icon mb-3 position-relative">
                                                <span class="input-icon-addon">
                                                    <i class="ti ti-user"></i>
                                                </span>
                                                <input type="text" name="pseudo" value="{{ old('pseudo', 'admin') }}" class="form-control @error('pseudo') is-invalid @enderror" required autofocus>
                                                @error('pseudo')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>

                                            <label class="form-label">Mot de passe</label>
                                            <div class="pass-group">
                                                <input type="password" value="Kodak.10" name="password" class="pass-input form-control @error('password') is-invalid @enderror" required>
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
                                                    <input class="form-check-input mt-0" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                                </div>
                                                <label class="ms-1 mb-0" for="remember">Se souvenir de moi</label>
                                            </div>
                                            
                                        </div>
                                        <div class="mb-3">
                                            <button type="submit" class="btn btn-primary w-100">Se connecter</button>
                                        </div>
                                        <div class="text-center">
                                            <h6 class="fw-normal text-dark mb-0">Vous n'avez pas de compte? <a href="#" class="hover-a">Faire une demande</a></h6>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3 text-center">
                                    <p class="mb-0">Copyright &copy; {{ date('Y') }} - {{ config('app.name', 'SchoolManager') }}</p>
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
    // Script pour améliorer l'expérience utilisateur
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

        // Validation des sélecteurs
        const form = document.querySelector('form');
        form.addEventListener('submit', function(e) {
            const anneeSelect = document.querySelector('select[name="annee_scolaire_id"]');
            
            if (!anneeSelect.value) {
                e.preventDefault();
                alert('Veuillez sélectionner une année scolaire');
                anneeSelect.focus();
                return false;
            }
            
           
        });
    });
</script>
@endsection