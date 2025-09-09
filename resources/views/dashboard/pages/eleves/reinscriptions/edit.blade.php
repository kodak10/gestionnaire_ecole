@extends('dashboard.layouts.master')

@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
    <div class="my-auto mb-2">
        <h3 class="page-title mb-1">Modifier Réinscription</h3>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Tableau de bord</a></li>
                <li class="breadcrumb-item"><a href="{{ route('reinscriptions.index') }}">Réinscriptions</a></li>
                <li class="breadcrumb-item active" aria-current="page">Modifier</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('reinscriptions.update', $reinscription->id) }}">
                    @csrf
                    @method('PUT')
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Élève</label>
                                <input type="text" class="form-control" value="{{ $reinscription->eleve->nom_complet }} ({{ $reinscription->eleve->matricule }})" readonly>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Classe <span class="text-danger">*</span></label>
                                <select name="classe_id" class="form-select" required>
                                    @foreach($classes as $classe)
                                        <option value="{{ $classe->id }}" {{ $reinscription->classe_id == $classe->id ? 'selected' : '' }}>
                                            {{ $classe->nom }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Année scolaire <span class="text-danger">*</span></label>
                                <input type="text" name="annee_scolaire" class="form-control" value="{{ old('annee_scolaire', $reinscription->annee_scolaire) }}" required>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Date de réinscription <span class="text-danger">*</span></label>
                                <input type="date" name="date_reinscription" class="form-control" 
                                       value="{{ old('date_reinscription', $reinscription->date_reinscription->format('Y-m-d')) }}" required>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Montant <span class="text-danger">*</span></label>
                                <input type="number" name="montant" class="form-control" 
                                       value="{{ old('montant', $reinscription->montant) }}" min="0" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Statut <span class="text-danger">*</span></label>
                                <select name="statut" class="form-select" required>
                                    <option value="en_attente" {{ $reinscription->statut == 'en_attente' ? 'selected' : '' }}>En attente</option>
                                    <option value="validée" {{ $reinscription->statut == 'validée' ? 'selected' : '' }}>Validée</option>
                                    <option value="refusée" {{ $reinscription->statut == 'refusée' ? 'selected' : '' }}>Refusée</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="2">{{ old('notes', $reinscription->notes) }}</textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-end mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-check me-2"></i>Mettre à jour
                        </button>
                        <a href="{{ route('reinscriptions.index') }}" class="btn btn-secondary">
                            <i class="ti ti-x me-2"></i>Annuler
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection