@extends('dashboard.layouts.master')

@section('content')
{{-- @php
    dd($ecoleInfos);
@endphp --}}


<div class="d-md-flex d-block align-items-center justify-content-between border-bottom pb-3">
    <div class="my-auto mb-2">
        <h3 class="page-title mb-1">Paramètres Académiques</h3>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Tableau de Bord</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="javascript:void(0);">Paramètres</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Paramètres Académiques</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex my-xl-auto right-content align-items-center flex-wrap">
        <div class="pe-1 mb-2">
            <a href="{{ route('ecoles.index') }}" class="btn btn-outline-light bg-white btn-icon" data-bs-toggle="tooltip" data-bs-placement="top" title="Actualiser">
                <i class="ti ti-refresh"></i>
            </a>
        </div>
    </div>
</div>

<div class="mb-5">
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">
        <strong>Des erreurs ont été détectées :</strong>
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

</div>

<div class="row">
    <div class="col-xxl-2 col-xl-3">
        <div class="pt-3 d-flex flex-column list-group mb-4">
            <a href="{{ route('ecoles.index') }}" class="d-block rounded active p-2">Paramètres École</a>
        </div>
    </div>
    <div class="col-xxl-10 col-xl-9">
        <div class="border-start ps-3">
            <form action="{{ route('ecoles.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="d-flex align-items-center justify-content-between flex-wrap border-bottom pt-3 mb-3">
                    <div class="mb-3">
                        <h5 class="mb-1">Paramètres de l'École</h5>
                        <p>Configuration des paramètres de l'école</p>
                    </div>
                    <div class="mb-3">
                        <a href="{{ route('ecoles.index') }}" class="btn btn-light me-2">Annuler</a>
                        <button class="btn btn-primary" type="submit">Enregistrer</button>
                    </div>
                </div>
                <div class="d-md-flex">
                    <div class="row flex-fill">
                        <div class="col-xl-10">
                            <!-- Logo -->
                            <div class="d-flex align-items-center justify-content-between flex-wrap border mb-3 p-3 pb-0 rounded">
                                <div class="row align-items-center flex-fill">
                                    <div class="col-xxl-8 col-lg-6">
                                        <div class="mb-3">
                                            <h6>Logo de l'école</h6>
                                            <p>Logo de l'établissement</p>
                                        </div>
                                    </div>
                                    <div class="col-xxl-4 col-lg-6">
                                        <div class="mb-3">
                                            <input type="file" class="form-control" name="logo">
                                            @if($ecoleInfos && $ecoleInfos->logo)
                                                <img src="{{ asset($ecoleInfos->logo) }}" alt="Logo" class="mt-2 img-thumbnail" style="max-height: 100px;">
                                            @endif

                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Nom de l'école -->
                            <div class="d-flex align-items-center justify-content-between flex-wrap border mb-3 p-3 pb-0 rounded">
                                <div class="row align-items-center flex-fill">
                                    <div class="col-xxl-8 col-lg-6">
                                        <div class="mb-3">
                                            <h6>Nom de l'école</h6>
                                            <p>Nom de l'établissement</p>
                                        </div>
                                    </div>
                                    <div class="col-xxl-4 col-lg-6">
                                        <div class="mb-3">

                                            <input type="text" class="form-control" name="nom_ecole" value="{{ $ecoleInfos->nom_ecole ?? '' }}" placeholder="Entrez le nom de l'école">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex align-items-center justify-content-between flex-wrap border mb-3 p-3 pb-0 rounded">
                                <div class="row align-items-center flex-fill">
                                    <div class="col-xxl-8 col-lg-6">
                                        <div class="mb-3">
                                            <h6>Code</h6>
                                            <p>Code de l'établissement</p>
                                        </div>
                                    </div>
                                    <div class="col-xxl-4 col-lg-6">
                                        <div class="mb-3">
                                            <input type="text" class="form-control" name="code" value="{{ $ecoleInfos->code ?? '' }}" placeholder="Entrez le code de l'école">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex align-items-center justify-content-between flex-wrap border mb-3 p-3 pb-0 rounded">
                                <div class="row align-items-center flex-fill">
                                    <div class="col-xxl-8 col-lg-6">
                                        <div class="mb-3">
                                            <h6>Sigle</h6>
                                            <p>Sigle de l'établissement</p>
                                        </div>
                                    </div>
                                    <div class="col-xxl-4 col-lg-6">
                                        <div class="mb-3">
                                            <input type="text" class="form-control" name="sigle_ecole" value="{{ $ecoleInfos->sigle_ecole ?? '' }}" placeholder="Entrez le sigle de l'école">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Téléphone -->
                            <div class="d-flex align-items-center justify-content-between flex-wrap border mb-3 p-3 pb-0 rounded">
                                <div class="row align-items-center flex-fill">
                                    <div class="col-xxl-8 col-lg-6">
                                        <div class="mb-3">
                                            <h6>Téléphone</h6>
                                            <p>Numéro de téléphone de l'école</p>
                                        </div>
                                    </div>
                                    <div class="col-xxl-4 col-lg-6">
                                        <div class="mb-3">
                                            <input type="text" class="form-control" name="telephone" value="{{ $ecoleInfos->telephone ?? '' }}" placeholder="Entrez le numéro de téléphone">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="d-flex align-items-center justify-content-between flex-wrap border mb-3 p-3 pb-0 rounded">
                                <div class="row align-items-center flex-fill">
                                    <div class="col-xxl-8 col-lg-6">
                                        <div class="mb-3">
                                            <h6>Email</h6>
                                            <p>Adresse email de l'école</p>
                                        </div>
                                    </div>
                                    <div class="col-xxl-4 col-lg-6">
                                        <div class="mb-3">
                                            <input type="email" class="form-control" name="email" value="{{ $ecoleInfos->email ?? '' }}" placeholder="Entrez l'email">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Fax -->
                            <div class="d-flex align-items-center justify-content-between flex-wrap border mb-3 p-3 pb-0 rounded">
                                <div class="row align-items-center flex-fill">
                                    <div class="col-xxl-8 col-lg-6">
                                        <div class="mb-3">
                                            <h6>Fax</h6>
                                            <p>Numéro de fax de l'école</p>
                                        </div>
                                    </div>
                                    <div class="col-xxl-4 col-lg-6">
                                        <div class="mb-3">
                                            <input type="text" class="form-control" name="fax" value="{{ $ecoleInfos->fax ?? '' }}" placeholder="Entrez le numéro de fax">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Adresse -->
                            <div class="d-flex align-items-center justify-content-between flex-wrap border mb-3 p-3 pb-0 rounded">
                                <div class="row align-items-center flex-fill">
                                    <div class="col-xxl-8 col-lg-6">
                                        <div class="mb-3">
                                            <h6>Adresse</h6>
                                            <p>Adresse complète de l'école</p>
                                        </div>
                                    </div>
                                    <div class="col-xxl-4 col-lg-6">
                                        <div class="mb-3">
                                            <textarea rows="4" class="form-control" name="adresse" placeholder="Entrez l'adresse">{{ $ecoleInfos->adresse ?? '' }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Directeur -->
                            <div class="d-flex align-items-center justify-content-between flex-wrap border mb-3 p-3 pb-0 rounded">
                                <div class="row align-items-center flex-fill">
                                    <div class="col-xxl-8 col-lg-6">
                                        <div class="mb-3">
                                            <h6>Directeur</h6>
                                            <p>Nom du directeur de l'école</p>
                                        </div>
                                    </div>
                                    <div class="col-xxl-4 col-lg-6">
                                        <div class="mb-3">
                                            <input type="text" class="form-control" name="directeur" value="{{ $ecoleInfos->directeur ?? '' }}" placeholder="Entrez le nom du directeur">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer bulletin -->
                            <div class="d-flex align-items-center justify-content-between flex-wrap border mb-3 p-3 pb-0 rounded">
                                <div class="row align-items-center flex-fill">
                                    <div class="col-xxl-8 col-lg-6">
                                        <div class="mb-3">
                                            <h6>Pied de page des bulletins</h6>
                                            <p>Texte à afficher en bas des bulletins</p>
                                        </div>
                                    </div>
                                    <div class="col-xxl-4 col-lg-6">
                                        <div class="mb-3">
                                            <textarea rows="4" class="form-control" name="footer_bulletin" placeholder="Entrez le texte du pied de page">{{ $ecoleInfos->footer_bulletin ?? '' }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Notification SMS -->
                            <div class="d-flex align-items-center justify-content-between flex-wrap border mb-3 p-3 pb-0 rounded">
                                <div class="row align-items-center flex-fill">
                                    <div class="col-xxl-8 col-lg-6">
                                        <div class="mb-3">
                                            <h6>Envoyer les messages de paiement par SMS</h6>
                                            <p>Activer ou désactiver l'envoi de SMS pour les paiements</p>
                                        </div>
                                    </div>
                                    <div class="col-xxl-4 col-lg-6">
                                        <div class="form-check form-switch mt-3">
                                            <input class="form-check-input" type="checkbox" name="sms_notification" id="sms_notification" value="1" {{ ($ecoleInfos->sms_notification ?? false) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="sms_notification">Activer</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection