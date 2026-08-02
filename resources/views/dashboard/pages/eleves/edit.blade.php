{{-- resources/views/dashboard/pages/eleves/edit.blade.php --}}

@extends('dashboard.layouts.master')

@section('content')
<!-- Page Header -->
<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
    <div class="my-auto mb-2">
        <h3 class="mb-1">Modification Élève</h3>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Tableau de Bord</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('eleves.index') }}">Élèves</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Modification</li>
            </ol>
        </nav>
    </div>
</div>
<!-- /Page Header -->

<!-- Messages -->
<div class="mb-5">
    @if ($errors->any())
        <div class="alert alert-danger mt-4 w-100">
            <h5 class="mb-2">Erreurs de validation :</h5>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger mt-4 w-100">
            {{ session('error') }}
        </div>
    @endif

    @if (session('success'))
        <div class="alert alert-success mt-4 w-100">
            {{ session('success') }}
        </div>
    @endif
</div>
        
<form action="{{ route('eleves.update', $eleve->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <div class="row">
        <!-- Colonne de gauche - Informations Élève/Parents -->
        <div class="col-md-7">
            <!-- Carte Informations Élève -->
            <div class="card">
                <div class="card-header bg-light">
                    <ul class="nav nav-tabs nav-tabs-bottom">
                        <li class="nav-item">
                            <a class="nav-link active" href="#eleve-tab" data-bs-toggle="tab">Élève</a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        <!-- Onglet Élève -->
                        <div class="tab-pane fade show active" id="eleve-tab">
                            <!-- Photo de profil -->
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <div class="d-flex align-items-center flex-wrap row-gap-3 mb-3">
                                        <div class="avatar-upload">
                                            <div class="avatar-edit">
                                                <input 
                                                    type="file"
                                                    id="avatarUpload"
                                                    name="photo_path"
                                                    accept="image/*"
                                                />
                                                <label for="avatarUpload">
                                                    <i class="ti ti-file fs-16"></i>
                                                </label>
                                            </div>
                                            <div class="avatar-preview">
                                                <div id="avatarPreview"
                                                    style="background-image: url({{ $eleve->photo_url ?? asset('assets/img/profiles/avatar-01.jpg') }});">
                                                </div>
                                            </div>
                                        </div>
                                        <p class="fs-12 ms-3">Format JPG, PNG - Max 4MB</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Informations de base -->
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Matricule</label>
                                        <input type="text" class="form-control" value="{{ $eleve->matricule }}" disabled>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Nom <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control text-uppercase" name="nom" value="{{ old('nom', $eleve->nom) }}" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Prénoms <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control text-uppercase" name="prenom" value="{{ old('prenom', $eleve->prenom) }}" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Date de Naissance <span class="text-danger">*</span></label>
                                        <input 
                                            type="date" 
                                            class="form-control" 
                                            name="naissance" 
                                            value="{{ old('naissance', $eleve->naissance ? date('Y-m-d', strtotime($eleve->naissance)) : '') }}" 
                                            required
                                        >
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Lieu de Naissance</label>
                                        <input type="text" class="form-control text-uppercase" name="lieu_naissance" value="{{ old('lieu_naissance', $eleve->lieu_naissance) }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Sexe <span class="text-danger">*</span></label>
                                        <select class="form-select" name="sexe" required>
                                            <option value="">Sélectionner</option>
                                            <option value="Masculin" {{ old('sexe', $eleve->sexe) == 'Masculin' ? 'selected' : '' }}>Masculin</option>
                                            <option value="Féminin" {{ old('sexe', $eleve->sexe) == 'Féminin' ? 'selected' : '' }}>Féminin</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Nationalité</label>
                                        <input type="text" class="form-control text-uppercase" name="nationalite" value="{{ old('nationalite', $eleve->nationalite ?? 'Ivoirienne') }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">N° Extrait</label>
                                        <input type="text" class="form-control text-uppercase" name="num_extrait" value="{{ old('num_extrait', $eleve->num_extrait) }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Code National</label>
                                        <input 
                                            type="text" 
                                            name="code_national"
                                            class="form-control text-uppercase" 
                                            value="{{ old('code_national', $eleve->code_national) }}">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">Infos Médicales</label>
                                        <textarea class="form-control" name="infos_medicales" rows="2">{{ old('infos_medicales', $eleve->infos_medicales) }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Carte Informations Parents -->
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Informations des Parents/Tuteurs</h5>
                </div>
                <div class="card-body">


                    <!-- Père -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Nom du Père</label>
                                <input type="text" class="form-control text-uppercase" name="pere_nom" value="{{ old('pere_nom', $eleve->pere_nom) }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Contact 01</label>
                                <input type="text" class="form-control" name="pere_contact" value="{{ old('pere_contact', $eleve->pere_contact) }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Contact 02</label>
                                <input type="text" class="form-control" name="pere_contact02" value="{{ old('pere_contact02', $eleve->pere_contact02) }}">
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- Mère -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Nom de la Mère</label>
                                <input type="text" class="form-control text-uppercase" name="mere_nom" value="{{ old('mere_nom', $eleve->mere_nom) }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Contact 01</label>
                                <input type="text" class="form-control" name="mere_contact" value="{{ old('mere_contact', $eleve->mere_contact) }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Contact 02</label>
                                <input type="text" class="form-control" name="mere_contact02" value="{{ old('mere_contact02', $eleve->mere_contact02) }}">
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- Adresse -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Adresse</label>
                                <textarea class="form-control" name="parent_adresse" rows="2">{{ old('parent_adresse', $eleve->parent_adresse) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Colonne de droite - Scolarité et Options -->
        <div class="col-md-5">
            <!-- Carte Scolarité -->
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <h4 class="text-dark">Scolarité</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Classe <span class="text-danger">*</span></label>
                                <select class="form-select" name="classe_id" required id="classe_id">
                                    <option value="">Sélectionner</option>
                                    @foreach($classes as $classe)
                                        <option value="{{ $classe->id }}" 
                                            {{ old('classe_id', $eleve->classe_id) == $classe->id ? 'selected' : '' }}>
                                            {{ $classe->nom }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input"
                                    name="transport_active" id="transport_active" value="1"
                                    {{ old('transport_active', $eleve->transport_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="transport_active">
                                    Transport scolaire
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input"
                                    name="cantine_active" id="cantine_active" value="1"
                                    {{ old('cantine_active', $eleve->cantine_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="cantine_active">
                                    Cantine scolaire
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Carte Statut -->
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Statut</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Statut</label>
                                <select class="form-select" name="statut">
                                    <option value="active" {{ old('statut', $eleve->statut) == 'active' ? 'selected' : '' }}>Actif</option>
                                    <option value="termine" {{ old('statut', $eleve->statut) == 'termine' ? 'selected' : '' }}>Terminé</option>
                                    <option value="annule" {{ old('statut', $eleve->statut) == 'annule' ? 'selected' : '' }}>Annulé</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Boutons de soumission -->
    <div class="row mt-3">
        <div class="col-md-12 text-end">
            <a href="{{ route('eleves.index') }}" class="btn btn-light me-2">Annuler</a>
            <button type="submit" class="btn btn-primary">Mettre à jour</button>
        </div>
    </div>
</form>

@endsection

@section('scripts')
<style>
.avatar-upload {
    position: relative;
    max-width: 150px;
}
.avatar-upload .avatar-edit {
    position: absolute;
    right: 10px;
    z-index: 1;
    bottom: 10px;
}
.avatar-upload .avatar-edit input {
    display: none;
}
.avatar-upload .avatar-edit input + label {
    display: inline-block;
    width: 34px;
    height: 34px;
    margin-bottom: 0;
    border-radius: 100%;
    background: #FFFFFF;
    border: 1px solid transparent;
    box-shadow: 0px 2px 4px 0px rgba(0, 0, 0, 0.12);
    cursor: pointer;
    font-weight: normal;
    transition: all 0.2s ease-in-out;
    display: flex;
    align-items: center;
    justify-content: center;
}
.avatar-upload .avatar-edit input + label:hover {
    background: #f1f1f1;
    border-color: #d6d6d6;
}
.avatar-upload .avatar-preview {
    width: 150px;
    height: 150px;
    position: relative;
    border-radius: 100%;
    border: 6px solid #F8F8F8;
    box-shadow: 0px 2px 4px 0px rgba(0, 0, 0, 0.1);
}
.avatar-upload .avatar-preview > div {
    width: 100%;
    height: 100%;
    border-radius: 100%;
    background-size: cover;
    background-repeat: no-repeat;
    background-position: center;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Upload d'image
    const avatarInput = document.getElementById('avatarUpload');
    const avatarPreview = document.getElementById('avatarPreview');

    avatarInput.addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function (ev) {
            avatarPreview.style.backgroundImage = `url(${ev.target.result})`;
        };
        reader.readAsDataURL(file);
    });
});
</script>
@endsection