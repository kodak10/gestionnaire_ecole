@extends('dashboard.layouts.master')

@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
    <div class="my-auto mb-2">
        <h3 class="page-title mb-1">Fiches d'Inscription</h3>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Tableau de bord</a></li>
                <li class="breadcrumb-item active" aria-current="page">Fiches Inscription</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Filter -->
<div class="bg-white p-3 border rounded-1 d-flex align-items-center justify-content-between flex-wrap mb-4 pb-0">
    <h4 class="mb-3">Liste des Élèves Inscrits</h4>
    <div class="d-flex align-items-center flex-wrap">		
        <form method="GET" action="{{ route('documents.inscriptions') }}" class="d-flex flex-wrap">
            <div class="input-group mb-3 me-2" style="width: 200px;">
                <input type="text" name="nom" class="form-control" placeholder="Nom élève..." value="{{ request('nom') }}">
                <button class="btn btn-primary" type="submit"><i class="ti ti-search"></i></button>
            </div>
            
            <div class="dropdown mb-3 me-2">
                <a href="javascript:void(0);" class="btn btn-outline-light bg-white dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                    <i class="ti ti-filter me-2"></i>Filtrer
                </a>
                <div class="dropdown-menu drop-width p-3">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Classe</label>
                                <select class="form-select" name="classe_id">
                                    <option value="">Toutes</option>
                                    @foreach($classes as $classe)
                                        <option value="{{ $classe->id }}" {{ request('classe_id') == $classe->id ? 'selected' : '' }}>{{ $classe->nom }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end">
                        <a href="{{ route('documents.inscriptions') }}" class="btn btn-light me-3">Réinitialiser</a>
                        <button type="submit" class="btn btn-primary">Appliquer</button>
                    </div>
                </div>
            </div>
        </form>
    </div>	
</div>
<!-- /Filter -->

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-center mb-0">
                <thead>
                    <tr>
                        <th>Matricule</th>
                        <th>Élève</th>
                        <th>Date Nais.</th>
                        <th>Classe</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($inscriptions as $inscription)
                    <tr>
                        <td>
                            <span class="fw-bold text-primary">{{ $inscription->eleve->matricule }}</span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <img src="{{ $inscription->eleve->photo_url }}" alt="Photo" class="rounded-circle" width="40" height="40">
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-0">{{ $inscription->eleve->nom }} {{ $inscription->eleve->prenom }}</h6>
                                    <small class="text-muted">{{ $inscription->eleve->sexe }}</small>
                                </div>
                            </div>
                        </td>
                        <td>{{ $inscription->eleve->naissance_formattee }}</td>
                        <td>
                            <span class="badge bg-light text-dark">{{ $inscription->classe->nom }}</span>
                        </td>
                        <td>
                            <div class="d-flex">
                                <a href="{{ route('documents.generer-fiche-inscription', $inscription->eleve) }}" 
                                   class="btn btn-sm btn-outline-primary me-2" target="_blank">
                                    <i class="ti ti-printer me-1"></i>Imprimer
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center">Aucun Elève trouvé pour cette année scolaire</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="col-md-12 text-center mt-4">
    {{ $inscriptions->appends(request()->query())->links() }}
</div>
@endsection