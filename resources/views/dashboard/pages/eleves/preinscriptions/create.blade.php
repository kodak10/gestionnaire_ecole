@extends('dashboard.layouts.master')

@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
    <div class="my-auto mb-2">
        <h3 class="page-title mb-1">{{ isset($preinscription) ? 'Modifier' : 'Nouvelle' }} Préinscription</h3>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Tableau de bord</a></li>
                <li class="breadcrumb-item"><a href="{{ route('preinscriptions.index') }}">Préinscriptions</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ isset($preinscription) ? 'Modifier' : 'Nouvelle' }}</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ isset($preinscription) ? route('preinscriptions.update', $preinscription->id) : route('preinscriptions.store') }}">
                    @csrf
                    @if(isset($preinscription)) @method('PUT') @endif
                    
                    <h5 class="mb-4">Informations de l'élève</h5>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Nom <span class="text-danger">*</span></label>
                                <input type="text" name="nom" class="form-control" 
                                       value="{{ old('nom', $preinscription->nom ?? '') }}" required>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Prénom <span class="text-danger">*</span></label>
                                <input type="text" name="prenom" class="form-control" 
                                       value="{{ old('prenom', $preinscription->prenom ?? '') }}" required>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Sexe <span class="text-danger">*</span></label>
                                <select name="sexe" class="form-select" required>
                                    <option value="Masculin" {{ (old('sexe', $preinscription->sexe ?? '') == 'Masculin') ? 'selected' : '' }}>Masculin</option>
                                    <option value="Féminin" {{ (old('sexe', $preinscription->sexe ?? '') == 'Féminin') ? 'selected' : '' }}>Féminin</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Date de naissance <span class="text-danger">*</span></label>
                                <input type="date" name="date_naissance" class="form-control" 
                                       value="{{ old('date_naissance', isset($preinscription) ? $preinscription->date_naissance->format('Y-m-d') : '') }}" required>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Lieu de naissance <span class="text-danger">*</span></label>
                                <input type="text" name="lieu_naissance" class="form-control" 
                                       value="{{ old('lieu_naissance', $preinscription->lieu_naissance ?? '') }}" required>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Classe demandée <span class="text-danger">*</span></label>
                                <select name="classe_demandee" class="form-select" required>
                                    @foreach($classes as $classe)
                                        <option value="{{ $classe->nom }}" {{ (old('classe_demandee', $preinscription->classe_demandee ?? '') == $classe->nom) ? 'selected' : '' }}>
                                            {{ $classe->nom }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Adresse</label>
                                <input type="text" name="adresse" class="form-control" 
                                       value="{{ old('adresse', $preinscription->adresse ?? '') }}">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">École de provenance</label>
                                <input type="text" name="ecole_provenance" class="form-control" 
                                       value="{{ old('ecole_provenance', $preinscription->ecole_provenance ?? '') }}">
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Téléphone</label>
                                <input type="text" name="telephone" class="form-control" 
                                       value="{{ old('telephone', $preinscription->telephone ?? '') }}">
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" 
                                       value="{{ old('email', $preinscription->email ?? '') }}">
                            </div>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <h5 class="mb-4">Informations du parent</h5>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Nom du parent <span class="text-danger">*</span></label>
                                <input type="text" name="nom_parent" class="form-control" 
                                       value="{{ old('nom_parent', $preinscription->nom_parent ?? '') }}" required>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Téléphone du parent <span class="text-danger">*</span></label>
                                <input type="text" name="telephone_parent" class="form-control" 
                                       value="{{ old('telephone_parent', $preinscription->telephone_parent ?? '') }}" required>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Email du parent</label>
                                <input type="email" name="email_parent" class="form-control" 
                                       value="{{ old('email_parent', $preinscription->email_parent ?? '') }}">
                            </div>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Statut <span class="text-danger">*</span></label>
                                <select name="statut" class="form-select" required>
                                    <option value="en_attente" {{ (old('statut', $preinscription->statut ?? '') == 'en_attente') ? 'selected' : '' }}>En attente</option>
                                    <option value="validée" {{ (old('statut', $preinscription->statut ?? '') == 'validée') ? 'selected' : '' }}>Validée</option>
                                    <option value="refusée" {{ (old('statut', $preinscription->statut ?? '') == 'refusée') ? 'selected' : '' }}>Refusée</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="2">{{ old('notes', $preinscription->notes ?? '') }}</textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-end mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-check me-2"></i>{{ isset($preinscription) ? 'Mettre à jour' : 'Enregistrer' }}
                        </button>
                        <a href="{{ route('preinscriptions.index') }}" class="btn btn-secondary">
                            <i class="ti ti-x me-2"></i>Annuler
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection