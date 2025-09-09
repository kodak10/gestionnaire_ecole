@extends('dashboard.layouts.master')
@section('content')
<!-- Page Header -->
<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
    <div class="my-auto mb-2">
        <h3 class="mb-1">Inscription Élève</h3>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Tableau de Bord</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('eleves.index') }}">Élèves</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Nouvelle Inscription</li>
            </ol>
        </nav>
    </div>
</div>
<!-- /Page Header -->

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
        
<form action="{{ route('eleves.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="row">
        <!-- Colonne de gauche - Informations Élève/Parents -->
        <div class="col-md-7">
            <!-- Carte Informations Élève/Parents -->
            <div class="card">
                <div class="card-header bg-light">
                    <ul class="nav nav-tabs nav-tabs-bottom">
                        <li class="nav-item">
                            <a class="nav-link active" href="#eleve-tab" data-bs-toggle="tab">Élève</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#parents-tab" data-bs-toggle="tab">Parents</a>
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
                                                <input type='file' id="avatarUpload" name="photo" accept=".png, .jpg, .jpeg"/>
                                                <label for="avatarUpload">
                                                    <i class="ti ti-camera fs-16"></i>
                                                </label>
                                            </div>
                                            <div class="avatar-preview">
                                                <div id="avatarPreview" style="background-image: url({{ asset('assets/images/default-avatar.png') }});">
                                                </div>
                                            </div>
                                        </div>
                                        <p class="fs-12 ms-3">Format JPG, PNG - Max 4MB</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Informations de base -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Nom <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control text-uppercase" name="nom" value="{{ old('nom') }}" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Prénoms <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control text-uppercase" name="prenom" value="{{ old('prenom') }}" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Date de Naissance <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" name="naissance" value="{{ old('naissance') }}" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Lieu de Naissance</label>
                                        <input type="text" class="form-control text-uppercase" name="lieu_naissance" value="{{ old('lieu_naissance') }}">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Sexe <span class="text-danger">*</span></label>
                                        <select class="form-select" name="sexe" required>
                                            <option value="">Sélectionner</option>
                                            <option value="Masculin" {{ old('sexe') == 'Masculin' ? 'selected' : '' }}>Masculin</option>
                                            <option value="Féminin" {{ old('sexe') == 'Féminin' ? 'selected' : '' }}>Féminin</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Nationalité</label>
                                        <input type="text" class="form-control text-uppercase" name="nationalite" value="{{ old('nationalite', 'Ivoirienne') }}">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">N° Extrait</label>
                                        <input type="text" class="form-control text-uppercase" name="extrait" value="{{ old('extrait') }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Code National</label>
                                        <input type="text" class="form-control text-uppercase" name="code_national" value="{{ old('code_national') }}">
                                    </div>
                                </div>
                            </div>

                            
                        </div>

                        <!-- Onglet Parents -->
                        <div class="tab-pane fade" id="parents-tab">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">Nom du Parent <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control text-uppercase" name="parent_nom" value="{{ old('parent_nom') }}" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Téléphone <span class="text-danger">*</span></label>
                                        <input type="tel" class="form-control" name="parent_telephone" value="{{ old('parent_telephone') }}" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="parent_email" value="{{ old('parent_email') }}">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">Adresse</label>
                                        <textarea class="form-control" name="parent_adresse" rows="2">{{ old('parent_adresse') }}</textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Profession</label>
                                        <input type="text" class="form-control text-uppercase" name="parent_profession" value="{{ old('parent_profession') }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Lien de parenté</label>
                                        <input type="text" class="form-control" name="parent_lien" value="{{ old('parent_lien', 'Parent') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Colonne de droite - Scolarité et Paiement -->
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
                                            data-niveau="{{ $classe->niveau_id }}"
                                            data-scolarite="{{ $tarifs->where('type_frais_id', $scolarite->id)->where('niveau_id', $classe->niveau_id)->first()->montant ?? 0 }}"
                                            data-inscription="{{ $tarifs->where('type_frais_id', $fraisInscription->id)->where('niveau_id', $classe->niveau_id)->first()->montant ?? 0 }}"
                                            data-cantine="{{ $tarifs->where('type_frais_id', $cantines->id)->where('niveau_id', $classe->niveau_id)->first()->montant ?? 0 }}"
                                            data-transport="{{ $tarifs->where('type_frais_id', $transports->id)->where('niveau_id', $classe->niveau_id)->first()->montant ?? 0 }}"
                                            {{ old('classe_id') == $classe->id ? 'selected' : '' }}>
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
                                <input class="form-check-input" type="checkbox" name="transport_active" id="transport_active" {{ old('transport_active') ? 'checked' : '' }}>
                                <label class="form-check-label" for="transport_active">
                                    Utilise le transport scolaire
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="cantine_active" id="cantine_active" {{ old('cantine_active') ? 'checked' : '' }}>
                                <label class="form-check-label" for="cantine_active">
                                    Utilise la cantine scolaire
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Carte Paiement -->
            <div class="card">
                <div class="card-header bg-light">
                    <ul class="nav nav-tabs nav-tabs-bottom">
                        <li class="nav-item">
                            <a class="nav-link active" href="#recap-tab" data-bs-toggle="tab">Récapitulatif</a>
                        </li>
                        {{-- <li class="nav-item">
                            <a class="nav-link" href="#paiement-tab" data-bs-toggle="tab">Paiement</a>
                        </li> --}}
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        <!-- Onglet Récapitulatif -->
                        <div class="tab-pane fade show active" id="recap-tab">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Frais d'Inscription</label>
                                        <input type="number" class="form-control" name="frais_inscription" id="frais_inscription" value="{{ old('frais_inscription', 0) }}" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Frais de Scolarité</label>
                                        <input type="number" class="form-control" name="frais_scolarite" id="frais_scolarite" value="{{ old('frais_scolarite', 0) }}" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Frais de Transport</label>
                                        <input type="number" class="form-control" name="frais_transport" id="frais_transport" value="{{ old('frais_transport', 0) }}" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Frais de Cantine</label>
                                        <input type="number" class="form-control" name="frais_cantine" id="frais_cantine" value="{{ old('frais_cantine', 0) }}" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                {{-- <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Reduction</label>
                                        <input type="number" class="form-control fw-bold fs-16" name="reduction" id="reduction" value="{{ old('reduction', 0) }}">
                                    </div>
                                </div> --}}
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">Total à Payer</label>
                                        <input type="number" class="form-control fw-bold fs-16" name="total_paiement" id="total_paiement" value="{{ old('total_paiement', 0) }}" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Onglet Paiement -->
                        {{-- <div class="tab-pane fade" id="paiement-tab">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">Scolarité <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" name="montant_scolarite" value="{{ old('montant_scolarite', 0) }}" min="0" step="0.01">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Transport <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" name="montant_transport" value="{{ old('montant_transport', 0) }}" min="0" step="0.01">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Cantine <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" name="montant_cantine" value="{{ old('montant_cantine', 0) }}" min="0" step="0.01">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Date de Paiement <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" name="date_paiement" value="{{ old('date_paiement', date('Y-m-d')) }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Mode de Paiement <span class="text-danger">*</span></label>
                                        <select class="form-select" name="mode_paiement">
                                            <option value="">Sélectionner</option>
                                            <option value="especes" {{ old('mode_paiement') == 'especes' ? 'selected' : '' }}>Espèces</option>
                                            <option value="cheque" {{ old('mode_paiement') == 'cheque' ? 'selected' : '' }}>Chèque</option>
                                            <option value="virement" {{ old('mode_paiement') == 'virement' ? 'selected' : '' }}>Virement</option>
                                            <option value="mobile_money" {{ old('mode_paiement') == 'mobile_money' ? 'selected' : '' }}>Mobile Money</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div> --}}

                    </div>
            </div>
        </div>
    </div>

    <!-- Boutons de soumission -->
    <div class="row mt-3">
        <div class="col-md-12 text-end">
            <button type="reset" class="btn btn-light me-2">Annuler</button>
            <button type="submit" class="btn btn-primary">Enregistrer</button>
        </div>
    </div>
</form>

@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Gestion de l'upload de photo
        function readURL(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    $('#avatarPreview').css('background-image', 'url('+e.target.result +')');
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        $("#avatarUpload").change(function() {
            readURL(this);
        });

        // Gestion des frais dynamiques
        function updateFrais() {
            const classeOption = $('#classe_id option:selected');
            const fraisInscription = parseFloat(classeOption.data('inscription')) || 0;
            const fraisScolarite = parseFloat(classeOption.data('scolarite')) || 0;
            const fraisCantine = $('#cantine_active').is(':checked') ? parseFloat(classeOption.data('cantine')) || 0 : 0;
            const fraisTransport = $('#transport_active').is(':checked') ? parseFloat(classeOption.data('transport')) || 0 : 0;

            // Récupérer la réduction en cours (parseFloat + gestion NaN)
            const reduction = parseFloat($('#reduction').val()) || 0;

            $('#frais_inscription').val(fraisInscription);
            $('#frais_scolarite').val(fraisScolarite);
            $('#frais_cantine').val(fraisCantine);
            $('#frais_transport').val(fraisTransport);
            // La réduction est un input modifiable, on ne la reset pas ici
            // $('#reduction').val(reduction);

            const total = fraisInscription + fraisScolarite + fraisCantine + fraisTransport - reduction;

            $('#total_paiement').val(total >= 0 ? total.toFixed(2) : 0);
            $('#montant_paye').val(total >= 0 ? total.toFixed(2) : 0);
        }

        // Écouteurs d'événements
        $('#classe_id').change(updateFrais);
        $('#transport_active, #cantine_active').change(updateFrais);
        $('#reduction').on('input', updateFrais);  // Recalcule si réduction changée

        // Initialisation
        updateFrais();
    });

</script>


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
@endsection