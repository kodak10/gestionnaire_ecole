@extends('dashboard.layouts.master')

@section('content')
<div class="row">

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif


    <div class="col-md-12">
        <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
            <div class="my-auto mb-2">
                <h3 class="page-title mb-1">Réinscription Groupée</h3>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Tableau de bord</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('reinscriptions.index') }}">Réinscriptions</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Groupée</li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex my-xl-auto right-content align-items-center flex-wrap">
                <div class="pe-1 mb-2">
                    <button type="button" onclick="location.reload()" class="btn btn-outline-light bg-white btn-icon me-1" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Actualiser" data-bs-original-title="Actualiser">
                        <i class="ti ti-refresh"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <div class="alert alert-outline-primary bg-primary-transparent p-2 d-flex align-items-center flex-wrap row-gap-2 mb-4">
            <i class="ti ti-info-circle me-1"></i><strong>Note :</strong> La réinscription groupée permet de réinscrire plusieurs élèves en même temps dans une nouvelle classe pour l'année scolaire suivante.
        </div>
        
        <form method="POST" action="{{ route('reinscriptions.store') }}" id="reinscription-form">
            @csrf
            <div class="card">
                <div class="card-header border-0 pb-0">
                    <div class="bg-light-gray p-3 rounded">
                        <h4>Réinscription</h4>
                        <p>Sélectionnez une classe de destination pour la réinscription</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-md-flex align-items-center justify-content-between">
                        <div class="card flex-fill w-100">
                            <div class="card-body pb-1">
                                <div class="mb-3">
                                    <label class="form-label">Année scolaire actuelle <span class="text-danger">*</span></label>
                                    <div class="form-control-plaintext p-0">{{ $anneeScolaire }}</div>
                                    <input type="hidden" name="annee_scolaire" value="{{ $anneeScolaire }}">
                                </div>
                                <div>
                                    <label class="form-label mb-2">Classe d'origine<span class="text-danger"> *</span></label>
                                    <div class="d-block d-md-flex">
                                        <div class="mb-3 flex-fill me-md-3 me-0">
                                            <label class="form-label">Classe</label>
                                            <select class="form-select" id="classe-origin" required>
                                                <option value="">Sélectionner une classe</option>
                                                @foreach($classes as $classe)
                                                    <option value="{{ $classe->id }}">{{ $classe->nom }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="badge bg-primary badge-xl exchange-link text-white d-flex align-items-center justify-content-center mx-md-4 mx-auto my-md-0 my-4 flex-shrink-0">
                            <span><i class="ti ti-arrows-exchange fs-16"></i></span>
                        </div>
                        <div class="card flex-fill w-100">
                            <div class="card-body pb-1">
                                <div class="mb-3">
                                    <label class="form-label">Réinscription pour l'année <span class="text-danger"> *</span></label>
                                    <input type="text" class="form-control" value="{{ $anneeScolaire }}" readonly>
                                </div>
                                <div>
                                    <label class="form-label mb-2">Classe de destination<span class="text-danger"> *</span></label>
                                    <div class="d-block d-md-flex">
                                        <div class="mb-3 flex-fill me-md-3 me-0">
                                            <label class="form-label">Classe</label>
                                            <select name="classe_id" class="form-select" required>
                                                <option value="">Sélectionner une classe</option>
                                                @foreach($classes as $classe)
                                                    <option value="{{ $classe->id }}">{{ $classe->nom }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-12">
                        <div class="manage-promote-btn d-flex justify-content-center flex-wrap row-gap-2 mt-4">
                            <button type="reset" class="btn btn-light reset-promote me-3">Réinitialiser</button>
                            <button type="button" class="btn btn-primary promote-students-btn" id="load-students">Charger les élèves</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="promote-card-main mt-4" id="students-section" style="display: none;">
                <div class="card">
                    <div class="card-header border-0 pb-0">
                        <div class="bg-light-gray p-3 rounded">
                            <h4>Liste des élèves</h4>
                            <p>Sélectionnez les élèves à réinscrire</p>
                        </div>
                    </div>
                    <div class="card-body p-0 py-3">
                        <div class="custom-datatable-filter table-responsive">
                            <table class="table">
                                <thead class="thead-light">
                                    <tr>
                                        <th class="no-sort">
                                            <div class="form-check form-check-md">
                                                <input class="form-check-input" type="checkbox" id="select-all">
                                            </div>
                                        </th>
                                        <th>Matricule</th>
                                        <th>Nom complet</th>
                                        <th>Classe actuelle</th>
                                    </tr>
                                </thead>
                                <tbody id="students-list">
                                    <!-- Les élèves seront chargés ici dynamiquement -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="promoted-year text-center mt-4">
                    <p>Les élèves sélectionnés seront réinscrits pour l'année scolaire {{ $anneeScolaire }}</p>
                    <button type="submit" class="btn btn-primary" id="submit-reinscription">
                        <i class="ti ti-users me-2"></i>Réinscrire les élèves sélectionnés
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Charger les élèves lorsqu'une classe d'origine est sélectionnée
        document.getElementById('load-students').addEventListener('click', function() {
            const classeId = document.getElementById('classe-origin').value;
            const classeDestination = document.querySelector('select[name="classe_id"]').value;
            
            if (!classeId) {
                alert('Veuillez sélectionner une classe d\'origine');
                return;
            }
            
            if (!classeDestination) {
                alert('Veuillez sélectionner une classe de destination');
                return;
            }
            
            // Afficher le loader
            const studentsList = document.getElementById('students-list');
            studentsList.innerHTML = '<tr><td colspan="4" class="text-center">Chargement des élèves...</td></tr>';
            
            
            // CORRECTION: Utilisez la route nommée avec l'URL correcte
            const url = "/reinscriptions/eleves-by-classe/" + classeId;

            console.log("URL AJAX :", url);

            // Charger les élèves via AJAX
            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erreur de réseau');
                    }
                    return response.json();
                })
                .then(data => {
                    studentsList.innerHTML = '';
                    
                    if (data.length === 0) {
                        studentsList.innerHTML = '<tr><td colspan="4" class="text-center">Aucun élève trouvé dans cette classe ou tous les élèves ont déjà été réinscrits</td></tr>';
                    } else {
                        data.forEach(eleve => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input student-checkbox" type="checkbox" name="eleves[]" value="${eleve.id}">
                                    </div>
                                </td>
                                <td>${eleve.matricule}</td>
                                <td>${eleve.nom} ${eleve.prenom}</td>
                                <td>${document.getElementById('classe-origin').options[document.getElementById('classe-origin').selectedIndex].text}</td>
                            `;
                            studentsList.appendChild(row);
                        });
                    }
                    
                    // Afficher la section des élèves
                    document.getElementById('students-section').style.display = 'block';
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    studentsList.innerHTML = '<tr><td colspan="4" class="text-center">Erreur lors du chargement des élèves</td></tr>';
                });
        });
        
        // Sélectionner/désélectionner tous les élèves
        document.getElementById('select-all').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.student-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
        
        // Validation du formulaire
        document.getElementById('reinscription-form').addEventListener('submit', function(e) {
            const selectedStudents = document.querySelectorAll('.student-checkbox:checked');
            if (selectedStudents.length === 0) {
                e.preventDefault();
                alert('Veuillez sélectionner au moins un élève');
                return false;
            }
        });
    });
</script>
@endsection