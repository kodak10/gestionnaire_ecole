@extends('dashboard.layouts.master')

@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
    <div class="my-auto mb-2">
        <h3 class="page-title mb-1">Gestion des Notes</h3>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Tableau de bord</a></li>
                <li class="breadcrumb-item active" aria-current="page">Notes</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex my-xl-auto right-content align-items-center flex-wrap">
        <div class="pe-1 mb-2">
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#bulletinModal">
                <i class="ti ti-file-spreadsheet me-2"></i>Générer Bulletin
            </button>
        </div>

        <div class="pe-1 mb-2">
            <a href="{{ route('notes.create') }}" class="btn btn-outline-primary">
                <i class="ti ti-file-spreadsheet me-2"></i>Saisie de Moyenne
            </a>
        </div>
        <div class="pe-1 mb-2">
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#fichesMoyennesModal">
                <i class="ti ti-file-spreadsheet me-2"></i>Imprimer Fiche de Notes
            </button>
        </div>
    </div>
</div>

<!-- Filter -->
<div class="bg-white p-3 border rounded-1 d-flex align-items-center justify-content-between flex-wrap mb-4 pb-0">
    <h4 class="mb-3">Liste des Notes</h4>
    <div class="d-flex align-items-center flex-wrap">		
        <form method="GET" action="{{ route('notes.index') }}" class="d-flex flex-wrap">
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
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Matière</label>
                                <select name="matiere_id" class="form-select">
                                    <option value="">Toutes</option>
                                    @foreach($matieres as $matiere)
                                        <option value="{{ $matiere->id }}" {{ request('matiere_id') == $matiere->id ? 'selected' : '' }}>{{ $matiere->nom }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Mois</label>
                                <select name="mois_id" class="form-select">
                                    <option value="">Tous</option>
                                    @foreach($moisScolaire as $mois)
                                        <option value="{{ $mois->id }}" {{ request('mois_id') == $mois->id ? 'selected' : '' }}>{{ $mois->nom }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end">
                        <a href="{{ route('notes.index') }}" class="btn btn-light me-3">Réinitialiser</a>
                        <button type="submit" class="btn btn-primary">Appliquer</button>
                    </div>
                </div>
            </div>
        </form>
        
        <div class="dropdown mb-3">
            <a href="javascript:void(0);" class="btn btn-outline-light bg-white dropdown-toggle" data-bs-toggle="dropdown">
                <i class="ti ti-sort-ascending-2 me-2"></i>Trier par 
            </a>
            <ul class="dropdown-menu p-3">
                <li>
                    <a href="{{ route('notes.index', array_merge(request()->query(), ['sort_by' => 'valeur', 'sort' => 'desc'])) }}" 
                       class="dropdown-item rounded-1 {{ request('sort_by') == 'valeur' && request('sort') == 'desc' ? 'active' : '' }}">
                       Note (plus haute)
                    </a>
                </li>
                <li>
                    <a href="{{ route('notes.index', array_merge(request()->query(), ['sort_by' => 'valeur', 'sort' => 'asc'])) }}" 
                       class="dropdown-item rounded-1 {{ request('sort_by') == 'valeur' && request('sort') == 'asc' ? 'active' : '' }}">
                       Note (plus basse)
                    </a>
                </li>
                <li>
                    <a href="{{ route('notes.index', array_merge(request()->query(), ['sort_by' => 'created_at', 'sort' => 'desc'])) }}" 
                       class="dropdown-item rounded-1 {{ request('sort_by') == 'created_at' && request('sort') == 'desc' ? 'active' : '' }}">
                       Récemment ajoutés
                    </a>
                </li>
            </ul>
        </div>
    </div>	
</div>
<!-- /Filter -->

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-center mb-0">
                <thead>
                    <tr>
                        <th>Élève</th>
                        <th>Matière</th>
                        <th>Classe</th>
                        <th>Note</th>
                        <th>Coeff</th>
                        <th>Mois</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($notes as $note)
                    <tr>
                        <td>{{ $note->inscription->eleve->nom }} {{ $note->inscription->eleve->prenom }}</td>
                        <td>{{ $note->matiere->nom }}</td>
                        <td>{{ $note->classe->nom }}</td>
                        <td>
                            <span class="fw-bold {{ $note->valeur < 10 ? 'text-danger' : 'text-success' }}">
                                {{ number_format($note->valeur, 2) }}
                            </span>
                        </td>
                        <td>{{ $note->coefficient }}</td>
                        <td>{{ $note->mois->nom }}</td>
                        
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center">Aucune note trouvée</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="col-md-12 text-center mt-4">
    {{ $notes->appends(request()->query())->links() }}
</div>

<!-- Modal pour générer bulletin -->
<div class="modal fade" id="bulletinModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('notes.generateBulletin') }}" method="GET" target="_blank">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Générer un Bulletin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Classe</label>
                        <select name="classe_id" class="form-select" required>
                            <option value="">Sélectionner une classe</option>
                            @foreach($classes as $classe)
                                <option value="{{ $classe->id }}">{{ $classe->nom }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mois</label>
                        <select name="mois_id" class="form-select" required>
                            <option value="">Sélectionner un mois</option>
                            @foreach($moisScolaire as $mois)
                                <option value="{{ $mois->id }}">{{ $mois->nom }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Générer</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal pour générer les fiches de notes -->
<div class="modal fade" id="fichesMoyennesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('notes.generateFichesMoyennes') }}" method="GET" target="_blank">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Générer la Fiche de Notes</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Classe</label>
                        <select name="classe_id" class="form-select" required>
                            <option value="">Choisir une classe</option>
                            @foreach($classes as $classe)
                                <option value="{{ $classe->id }}">{{ $classe->nom }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mois</label>
                        <select name="mois_id" class="form-select" required>
                            <option value="">Choisir un mois</option>
                            @foreach($moisScolaire as $mois)
                                <option value="{{ $mois->id }}">{{ $mois->nom }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Générer</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
@endpush