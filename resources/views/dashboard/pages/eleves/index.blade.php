@extends('dashboard.layouts.master')

@section('content')
<!-- Page Header -->
<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
    <div class="my-auto mb-2">
        <h3 class="page-title mb-1">Gestion des Élèves</h3>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Tableau de bord</a></li>
                <li class="breadcrumb-item active" aria-current="page">Liste des Élèves</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex my-xl-auto right-content align-items-center flex-wrap">
        <div class="pe-1 mb-2">
            <a href="{{ route('eleves.index') }}" class="btn btn-outline-light bg-white btn-icon me-1" data-bs-toggle="tooltip" data-bs-placement="top" title="Actualiser">
                <i class="ti ti-refresh"></i>
            </a>
        </div>
        <div class="pe-1 mb-2">
            <button type="button" class="btn btn-outline-light bg-white btn-icon me-1" data-bs-toggle="tooltip" data-bs-placement="top" title="Imprimer" onclick="window.print()">
                <i class="ti ti-printer"></i>
            </button>
        </div>    
        <div class="dropdown me-2 mb-2">
            <a href="javascript:void(0);" class="dropdown-toggle btn btn-light fw-medium d-inline-flex align-items-center" data-bs-toggle="dropdown">
                <i class="ti ti-file-export me-2"></i>Exporter
            </a>
            <ul class="dropdown-menu dropdown-menu-end p-3">
                <li>
                    <a href="{{ route('eleves.export', array_merge(request()->query(), ['format' => 'pdf'])) }}" class="dropdown-item rounded-1"><i class="ti ti-file-type-pdf me-2"></i>PDF</a>
                </li>
                <li>
                    <a href="{{ route('eleves.export', array_merge(request()->query(), ['format' => 'excel'])) }}" class="dropdown-item rounded-1"><i class="ti ti-file-type-xls me-2"></i>Excel</a>
                </li>
            </ul>	
        </div>                  
        <div class="mb-2">
            <a href="{{ route('eleves.create') }}" class="btn btn-primary d-flex align-items-center">
                <i class="ti ti-square-rounded-plus me-2"></i>Ajouter Élève
            </a>
        </div>
    </div>
</div>
<!-- /Page Header -->

<!-- Filter -->
<div class="bg-white p-3 border rounded-1 d-flex align-items-center justify-content-between flex-wrap mb-4 pb-0">
    <h4 class="mb-3">Liste des Élèves</h4>
    <div class="d-flex align-items-center flex-wrap">		
        <form method="GET" action="{{ route('eleves.index') }}" class="d-flex flex-wrap">
            <div class="input-group mb-3 me-2" style="width: 200px;">
                <input type="text" name="nom" class="form-control" placeholder="Rechercher..." value="{{ request('nom') }}">
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
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Genre</label>
                                <select class="form-select" name="sexe">
                                    <option value="">Tous</option>
                                    <option value="Masculin" {{ request('sexe') == 'Masculin' ? 'selected' : '' }}>Masculin</option>
                                    <option value="Féminin" {{ request('sexe') == 'Féminin' ? 'selected' : '' }}>Féminin</option>
                                </select>
                            </div>
                        </div>

                         <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Cantine</label>
                                <select name="cantine" class="form-select">
                                    <option value="">Tous</option>
                                    <option value="1" {{ request('cantine') == '1' ? 'selected' : '' }}>Oui</option>
                                    <option value="0" {{ request('cantine') == '0' ? 'selected' : '' }}>Non</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Transport</label>
                                <select name="transport" class="form-select">
                                    <option value="">Tous</option>
                                    <option value="1" {{ request('transport') == '1' ? 'selected' : '' }}>Oui</option>
                                    <option value="0" {{ request('transport') == '0' ? 'selected' : '' }}>Non</option>
                                </select>
                            </div>
                        </div>

                        
                    </div>
                    <div class="d-flex justify-content-end">
                        <a href="{{ route('eleves.index') }}" class="btn btn-light me-3">Réinitialiser</a>
                        <button type="submit" class="btn btn-primary">Appliquer</button>
                    </div>
                </div>
            </div>
        </form>
        
        <div class="d-flex align-items-center bg-white border rounded-2 p-1 mb-3 me-2">
            <a href="{{ route('eleves.index', array_merge(request()->query(), ['view_mode' => 'list'])) }}" class="btn btn-icon btn-sm me-1 bg-light primary-hover {{ $viewMode == 'list' ? 'active' : '' }}">
                <i class="ti ti-list-tree"></i>
            </a>
            <a href="{{ route('eleves.index', array_merge(request()->query(), ['view_mode' => 'grid'])) }}" class="btn btn-icon btn-sm primary-hover {{ $viewMode == 'grid' ? 'active' : '' }}">
                <i class="ti ti-grid-dots"></i>
            </a>
        </div>
        
        <div class="dropdown mb-3">
            <a href="javascript:void(0);" class="btn btn-outline-light bg-white dropdown-toggle" data-bs-toggle="dropdown">
                <i class="ti ti-sort-ascending-2 me-2"></i>Trier par 
            </a>
            <ul class="dropdown-menu p-3">
                <li>
                    <a href="{{ route('eleves.index', array_merge(request()->query(), ['sort_by' => 'nom', 'sort' => 'asc'])) }}" 
                       class="dropdown-item rounded-1 {{ request('sort_by') == 'nom' && request('sort') == 'asc' ? 'active' : '' }}">
                       Nom (A-Z)
                    </a>
                </li>
                <li>
                    <a href="{{ route('eleves.index', array_merge(request()->query(), ['sort_by' => 'nom', 'sort' => 'desc'])) }}" 
                       class="dropdown-item rounded-1 {{ request('sort_by') == 'nom' && request('sort') == 'desc' ? 'active' : '' }}">
                       Nom (Z-A)
                    </a>
                </li>
                <li>
                    <a href="{{ route('eleves.index', array_merge(request()->query(), ['sort_by' => 'created_at', 'sort' => 'desc'])) }}" 
                       class="dropdown-item rounded-1 {{ request('sort_by') == 'created_at' && request('sort') == 'desc' ? 'active' : '' }}">
                       Récemment ajoutés
                    </a>
                </li>
            </ul>
        </div>
    </div>	
</div>
<!-- /Filter -->

@if($viewMode == 'grid')
    <!-- Grid View -->
    <div class="row">
        @foreach($inscriptions as $eleve)
        <div class="col-xxl-3 col-xl-4 col-md-6 d-flex">
            <div class="card flex-fill">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <a href="{{ route('eleves.show', $eleve->eleve->id) }}" class="link-primary">{{ $eleve->eleve->code_national ?? $eleve->eleve->matricule }}</a>
                    <div class="d-flex align-items-center">
                        
                        <div class="dropdown">
                            <a href="#" class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0" data-bs-toggle="dropdown">
                                <i class="ti ti-dots-vertical fs-14"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end p-3">
                                <li>
                                    <a class="dropdown-item rounded-1" href="{{ route('eleves.show', $eleve->eleve->id) }}">
                                        <i class="ti ti-eye me-2"></i>Voir
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item rounded-1" href="{{ route('eleves.edit', $eleve->id) }}">
                                        <i class="ti ti-edit me-2"></i>Modifier
                                    </a>
                                </li>
                                <li>
                                    <form action="{{ route('eleves.destroy', $eleve->id) }}" method="POST" id="delete-form-{{ $eleve->id }}">
                                        @csrf
                                        @method('DELETE')
                                        <a class="dropdown-item rounded-1" href="#" onclick="event.preventDefault(); if(confirm('Êtes-vous sûr ?')) document.getElementById('delete-form-{{ $eleve->id }}').submit();">
                                            <i class="ti ti-trash me-2"></i>Supprimer
                                        </a>
                                    </form>
                                </li>
                            </ul>	
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="bg-light-300 rounded-2 p-3 mb-3">
                        <div class="d-flex align-items-center">
                            <a href="{{ route('eleves.show', $eleve->eleve->id) }}" class="avatar avatar-lg flex-shrink-0">
                                <img 
                                    src="{{ $eleve->eleve->photo_url }}" 
                                    class="img-fluid rounded-circle border border-2
                                        {{ $eleve->eleve->sexe === 'Masculin' ? 'border-danger' : 'border-primary' }}" 
                                    alt="{{ $eleve->eleve->nom_complet }}">
                            </a>

                            <div class="ms-2">
                                <h5 class="mb-0 text-dark"><a href="{{ route('eleves.show', $eleve->eleve->id) }}">{{ $eleve->eleve->nom_complet }}</a></h5>
                                <p>{{ $eleve->classe->nom }}</p>
                            </div>
                        </div>	
                    </div>
                    <div class="d-flex align-items-center justify-content-between gx-2">
                        <div>
                           
                            <p class="mb-0">Date de nais.</p>
                            <p class="text-dark">{{ $eleve->eleve->naissance_formattee }}</p>
                        </div>
                        <div>
                            <p class="mb-0">Genre</p>
                            <p class="text-dark">{{ ucfirst($eleve->eleve->sexe) }}</p>
                        </div>
                        <div>
                            <p class="mb-0">Inscrit le</p>
                            <p class="text-dark">{{ $eleve->created_at->format('d/m/Y') }}</p>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
        @endforeach
    </div>
    <!-- /Grid View -->
@else
    <!-- List View -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-center mb-0">
                    <thead>
                        <tr>
                            <th>Matricule</th>
                            <th>Nom Complet</th>
                            <th>Classe</th>
                            <th>Parent</th>
                            <th>Téléphone</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($inscriptions as $eleve)
                        <tr>
                            <td>{{ $eleve->eleve->code_national ?? $eleve->eleve->matricule }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img 
                                        src="{{ $eleve->eleve->photo_url }}" 
                                        alt="Photo"
                                        class="rounded-circle border border-2 me-2 {{ $eleve->sexe === 'Masculin' ? 'border-danger' : 'border-primary' }}" 
                                        style="width: 50px; height: 50px; object-fit: cover;">
                                    <div>{{ $eleve->eleve->nom_complet }}</div>
                                </div>
                            </td>


                            <td>{{ $eleve->classe->nom }}</td>
                            <td>{{ $eleve->eleve->parent_nom }}</td>
                            <td>{{ $eleve->eleve->parent_telephone }}</td>
                            
                            <td class="text-end">
                                <div class="actions">
                                    <a href="{{ route('eleves.show', $eleve->eleve->id) }}" class="btn btn-sm bg-success-light me-2">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                    <a href="{{ route('eleves.edit', $eleve->eleve->id) }}" class="btn btn-sm bg-primary-light me-2">
                                        <i class="ti ti-edit"></i>
                                    </a>
                                    <form action="{{ route('eleves.destroy', $eleve->id) }}" method="POST" style="display: inline-block;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm bg-danger-light" onclick="return confirm('Êtes-vous sûr ?')">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- /List View -->
@endif

<div class="d-flex justify-content-center mt-4 mb-5">
    {{ $inscriptions->appends(request()->query())->links() }}
</div>

@endsection