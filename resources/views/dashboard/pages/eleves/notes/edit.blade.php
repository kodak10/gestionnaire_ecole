@extends('dashboard.layouts.master')

@section('content')
<div class="container-fluid">
    <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
        <div class="my-auto">
            <h3 class="page-title mb-1">Modifier une Note</h3>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Tableau de bord</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('notes.index') }}">Notes</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Modifier</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('notes.update', $note->id) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Élève</label>
                            <select name="inscription_id" class="form-select" required>
                                @foreach($inscriptions as $inscription)
                                    <option value="{{ $inscription->id }}" 
                                        {{ $note->inscription_id == $inscription->id ? 'selected' : '' }}>
                                        {{ $inscription->eleve->nom }} {{ $inscription->eleve->prenom }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Matière</label>
                            <select name="matiere_id" class="form-select" required>
                                @foreach($matieres as $matiere)
                                    <option value="{{ $matiere->id }}" 
                                        {{ $note->matiere_id == $matiere->id ? 'selected' : '' }}>
                                        {{ $matiere->nom }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Classe</label>
                            <select name="classe_id" class="form-select" required>
                                @foreach($classes as $classe)
                                    <option value="{{ $classe->id }}" 
                                        {{ $note->classe_id == $classe->id ? 'selected' : '' }}>
                                        {{ $classe->nom }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Mois</label>
                            <select name="mois_id" class="form-select" required>
                                @foreach($moisScolaire as $mois)
                                    <option value="{{ $mois->id }}" 
                                        {{ $note->mois_id == $mois->id ? 'selected' : '' }}>
                                        {{ $mois->nom }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Coefficient</label>
                            <input type="number" name="coefficient" class="form-control" 
                                value="{{ $note->coefficient }}" min="1" required>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Note (sur 20)</label>
                            <input type="number" name="valeur" class="form-control" 
                                value="{{ $note->valeur }}" min="0" max="20" step="0.01" required>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Appréciation</label>
                            <input type="text" name="appreciation" class="form-control" 
                                value="{{ $note->appreciation }}">
                        </div>
                    </div>
                </div>
                
                <div class="text-end">
                    <a href="{{ route('notes.index') }}" class="btn btn-secondary">Annuler</a>
                    <button type="submit" class="btn btn-primary">Mettre à jour</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection