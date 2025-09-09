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
                        <h4 class="text-white mb-3">Rejoignez OptiScolaire !</h4>
                        <div class="d-flex align-items-center flex-row mb-3 justify-content-between p-3 br-5 gap-3 card">
                            <div>
                                <h6>Gestion simplifiée</h6>
                                <p class="mb-0 text-truncate">Gérez votre école de manière efficace et intuitive.</p>
                            </div>
                            <a href="javascript:void(0);"><i class="ti ti-chevrons-right"></i></a>
                        </div>
                        <div class="d-flex align-items-center flex-row mb-3 justify-content-between p-3 br-5 gap-3 card">
                            <div>
                                <h6>Suivi en temps réel</h6>
                                <p class="mb-0 text-truncate">Accédez aux informations de vos élèves à tout moment.</p>
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
                        <form method="POST" action="{{ route('register') }}">
                            @csrf
                            <div>
                                <div class="mx-auto mb-5 text-center">
                                    <img src="{{ asset('assets/img/authentication/authentication-logo.svg') }}" class="img-fluid" alt="Logo">
                                    <h3 class="mt-3">{{ config('app.name', 'SchoolManager') }}</h3>
                                </div>
                                <div class="card">
                                    <div class="card-body p-4">
                                        <div class="mb-4">
                                            <h2 class="mb-2">Créer un compte</h2>
                                            <p class="mb-0">Veuillez remplir les informations ci-dessous</p>
                                        </div>

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
                                            <label class="form-label">Nom complet</label>
                                            <div class="input-icon mb-3 position-relative">
                                                <span class="input-icon-addon">
                                                    <i class="ti ti-user"></i>
                                                </span>
                                                <input type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required>
                                            </div>
                                            
                                            <label class="form-label">Nom d'utilisateur</label>
                                            <div class="input-icon mb-3 position-relative">
                                                <span class="input-icon-addon">
                                                    <i class="ti ti-at"></i>
                                                </span>
                                                <input type="text" name="pseudo" value="{{ old('pseudo') }}" class="form-control @error('pseudo') is-invalid @enderror" required>
                                            </div>
                                            
                                            <label class="form-label">Mot de passe</label>
                                            <div class="pass-group mb-3">
                                                <input type="password" name="password" class="pass-input form-control @error('password') is-invalid @enderror" required>
                                                <span class="ti toggle-password ti-eye-off"></span>
                                            </div>
                                            
                                            <label class="form-label">Confirmer le mot de passe</label>
                                            <div class="pass-group">
                                                <input type="password" name="password_confirmation" class="pass-input form-control" required>
                                                <span class="ti toggle-password ti-eye-off"></span>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <button type="submit" class="btn btn-primary w-100">S'inscrire</button>
                                        </div>
                                        <div class="text-center">
                                            <h6 class="fw-normal text-dark mb-0">Vous avez déjà un compte? <a href="{{ route('login') }}" class="hover-a">Se connecter</a></h6>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-5 text-center">
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
@endsection