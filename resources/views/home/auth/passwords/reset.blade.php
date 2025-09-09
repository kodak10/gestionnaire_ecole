@extends('home.layout.app')

@section('content')
<div class="container-fuild">
    <div class="w-100 overflow-hidden position-relative flex-wrap d-block vh-100">
        <div class="row justify-content-center align-items-center vh-100">
            <div class="col-md-6 col-lg-4">
                <div class="card">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <img src="{{ asset('assets/img/authentication/authentication-logo.svg') }}" class="img-fluid mb-3" alt="Logo" style="max-height: 60px;">
                            <h3>Nouveau mot de passe</h3>
                            <p class="text-muted">Définissez votre nouveau mot de passe</p>
                        </div>

                        <form method="POST" action="{{ route('password.update') }}">
                            @csrf
                            <input type="hidden" name="token" value="{{ $token }}">

                            <div class="mb-3">
                                <label for="email" class="form-label">Adresse email</label>
                                <div class="input-icon position-relative">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-mail"></i>
                                    </span>
                                    <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ $email ?? old('email') }}" required autocomplete="email" autofocus>
                                </div>
                                @error('email')
                                    <span class="invalid-feedback d-block" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Nouveau mot de passe</label>
                                <div class="pass-group">
                                    <input id="password" type="password" class="pass-input form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">
                                    <span class="ti toggle-password ti-eye-off"></span>
                                </div>
                                @error('password')
                                    <span class="invalid-feedback d-block" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="password-confirm" class="form-label">Confirmer le mot de passe</label>
                                <div class="pass-group">
                                    <input id="password-confirm" type="password" class="pass-input form-control" name="password_confirmation" required autocomplete="new-password">
                                    <span class="ti toggle-password ti-eye-off"></span>
                                </div>
                            </div>

                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary w-100">
                                    Réinitialiser le mot de passe
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection