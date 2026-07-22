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
                                    <label class="form-label">Année scolaire source <span class="text-danger">*</span></label>
                                    <select class="form-select" id="annee_source_id" name="annee_source_id" required>
                                        <option value="">Sélectionner une année</option>
                                        @foreach($anneescolaires as $anneescolaire)
                                            <option value="{{ $anneescolaire->id }}">{{ $anneescolaire->annee }}</option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Sélectionnez l'année où les élèves sont actuellement inscrits</small>
                                </div>
                                <div>
                                    <label class="form-label mb-2">Classe d'origine<span class="text-danger"> *</span></label>
                                    <div class="d-block d-md-flex">
                                        <div class="mb-3 flex-fill me-md-3 me-0">
                                            <label class="form-label">Classe</label>
                                            <select id="classe-origin" class="form-select" required disabled>
                                                <option value="">Sélectionnez d'abord une année</option>
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
                                    <input type="text" class="form-control" value="{{ $annee }}" readonly>
                                    <input type="hidden" name="annee_destination_id" value="{{ $anneeId }}">
                                    <small class="text-muted">Les élèves seront réinscrits pour cette année</small>
                                </div>

                                <div>
                                    <label class="form-label mb-2">Classe de destination<span class="text-danger"> *</span></label>
                                    <div class="d-block d-md-flex">
                                        <div class="mb-3 flex-fill me-md-3 me-0">
                                            <label class="form-label">Classe</label>
                                            <select name="classe_id" class="form-select" required>
                                                <option value="">Sélectionner une classe</option>
                                                @foreach($classesNouvelles as $classe)
                                                    <option value="{{ $classe->id }}">
                                                        {{ $classe->nom }}
                                                    </option>
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
                    <p>Les élèves sélectionnés seront réinscrits pour l'année scolaire {{ $annee }}</p>
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
// Utiliser un flag pour éviter l'exécution multiple
if (typeof window.reinscriptionScriptLoaded === 'undefined') {
    window.reinscriptionScriptLoaded = true;

    document.addEventListener('DOMContentLoaded', function() {
        
        // Charger les classes lorsqu'une année est sélectionnée
        const anneeSourceSelect = document.getElementById('annee_source_id');
        const classeOriginSelect = document.getElementById('classe-origin');
        const loadStudentsBtn = document.getElementById('load-students');
        const studentsList = document.getElementById('students-list');
        const studentsSection = document.getElementById('students-section');
        const selectAllCheckbox = document.getElementById('select-all');
        const submitBtn = document.getElementById('submit-reinscription');
        const reinscriptionForm = document.getElementById('reinscription-form');
        const resetBtn = document.querySelector('button[type="reset"]');
        
        // Variable pour empêcher les doublons de confirmation
        let isSubmitting = false;
        
        // Fonction pour mettre à jour le compteur
        function updateCounter() {
            const checked = document.querySelectorAll('.student-checkbox:checked');
            if (checked.length > 0) {
                submitBtn.innerHTML = `<i class="ti ti-users me-2"></i>Réinscrire ${checked.length} élève(s) sélectionné(s)`;
            } else {
                submitBtn.innerHTML = `<i class="ti ti-users me-2"></i>Réinscrire les élèves sélectionnés`;
            }
        }
        
        // Charger les classes par année
        anneeSourceSelect.addEventListener('change', function() {
            const anneeId = this.value;
            
            if (!anneeId) {
                classeOriginSelect.innerHTML = '<option value="">Sélectionnez d\'abord une année</option>';
                classeOriginSelect.disabled = true;
                return;
            }
            
            classeOriginSelect.innerHTML = '<option value="">Chargement des classes...</option>';
            classeOriginSelect.disabled = true;
            
            const url = "/reinscriptions/get-classes-by-annee?annee_id=" + anneeId;
            
            fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur HTTP: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                classeOriginSelect.innerHTML = '<option value="">Sélectionner une classe</option>';
                
                if (data.error) {
                    classeOriginSelect.innerHTML += `<option value="">Erreur: ${data.error}</option>`;
                } else if (data.length === 0) {
                    classeOriginSelect.innerHTML += '<option value="">Aucune classe trouvée</option>';
                } else {
                    data.forEach(classe => {
                        const option = document.createElement('option');
                        option.value = classe.id;
                        option.textContent = classe.nom;
                        classeOriginSelect.appendChild(option);
                    });
                }
                
                classeOriginSelect.disabled = false;
            })
            .catch(error => {
                console.error('Erreur:', error);
                classeOriginSelect.innerHTML = `<option value="">Erreur: ${error.message}</option>`;
                classeOriginSelect.disabled = false;
            });
        });
        
        // Charger les élèves
        loadStudentsBtn.addEventListener('click', function() {
            const anneeSourceId = anneeSourceSelect.value;
            const classeId = classeOriginSelect.value;
            const classeDestination = document.querySelector('select[name="classe_id"]').value;

            if (!anneeSourceId) {
                alert('Veuillez sélectionner l\'année scolaire source');
                anneeSourceSelect.focus();
                return;
            }

            if (!classeId) {
                alert('Veuillez sélectionner une classe d\'origine');
                classeOriginSelect.focus();
                return;
            }
            
            if (!classeDestination) {
                alert('Veuillez sélectionner une classe de destination');
                document.querySelector('select[name="classe_id"]').focus();
                return;
            }
            
            // Supprimer les anciens messages d'information
            document.querySelectorAll('.student-info-message').forEach(el => el.remove());
            
            // Afficher le loader
            studentsList.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                        <br>
                        <span class="text-muted">Chargement des élèves...</span>
                    </td>
                </tr>
            `;
            
            const url = "/reinscriptions/eleves-by-classe/" + classeId + "?annee_source_id=" + anneeSourceId;

            fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur HTTP: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                studentsList.innerHTML = '';
                
                if (data.error) {
                    studentsList.innerHTML = `
                        <tr>
                            <td colspan="4" class="text-center">
                                <div class="alert alert-danger mb-0">
                                    <i class="ti ti-alert-circle me-2"></i>
                                    Erreur: ${data.error}
                                </div>
                            </td>
                        </tr>
                    `;
                } else if (data.length === 0) {
                    studentsList.innerHTML = `
                        <tr>
                            <td colspan="4" class="text-center">
                                <div class="alert alert-warning mb-0">
                                    <i class="ti ti-alert-circle me-2"></i>
                                    Aucun élève trouvé dans cette classe pour l'année sélectionnée.
                                </div>
                            </td>
                        </tr>
                    `;
                } else {
                    data.forEach(eleve => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>
                                <div class="form-check form-check-md">
                                    <input class="form-check-input student-checkbox" type="checkbox" name="eleves[]" value="${eleve.id}">
                                </div>
                            </td>
                            <td><strong>${eleve.matricule}</strong></td>
                            <td>${eleve.nom} ${eleve.prenom}</td>
                            <td><span class="badge bg-info">${eleve.classe}</span></td>
                        `;
                        studentsList.appendChild(row);
                    });
                    
                    // Ajouter UN SEUL message d'information
                    const existingInfo = document.querySelector('.student-info-message');
                    if (!existingInfo) {
                        const infoDiv = document.createElement('div');
                        infoDiv.className = 'alert alert-info mt-2 student-info-message';
                        infoDiv.innerHTML = `
                            <i class="ti ti-users me-2"></i>
                            <strong>${data.length}</strong> élève(s) trouvé(s) dans cette classe.
                            <br><small>Cochez ceux que vous souhaitez réinscrire vers la nouvelle année.</small>
                        `;
                        
                        const tableContainer = studentsList.closest('.table-responsive');
                        if (tableContainer) {
                            const parent = tableContainer.parentNode;
                            if (parent) {
                                parent.insertBefore(infoDiv, tableContainer.nextSibling);
                            }
                        }
                    } else {
                        existingInfo.innerHTML = `
                            <i class="ti ti-users me-2"></i>
                            <strong>${data.length}</strong> élève(s) trouvé(s) dans cette classe.
                            <br><small>Cochez ceux que vous souhaitez réinscrire vers la nouvelle année.</small>
                        `;
                    }
                    
                    updateCounter();
                }
                
                studentsSection.style.display = 'block';
            })
            .catch(error => {
                console.error('Erreur:', error);
                studentsList.innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center">
                            <div class="alert alert-danger mb-0">
                                <i class="ti ti-alert-circle me-2"></i>
                                <strong>Erreur de chargement</strong><br>
                                ${error.message}
                            </div>
                        </td>
                    </tr>
                `;
            });
        });
        
        // Sélectionner/désélectionner tous les élèves
        selectAllCheckbox.addEventListener('change', function() {
            document.querySelectorAll('.student-checkbox').forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateCounter();
        });
        
        // Mise à jour du compteur lors du changement de checkbox
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('student-checkbox')) {
                updateCounter();
            }
        });
        
        // Validation du formulaire avec flag pour éviter les doublons
        reinscriptionForm.addEventListener('submit', function(e) {
            // Empêcher les soumissions multiples
            if (isSubmitting) {
                e.preventDefault();
                return false;
            }
            
            const selectedStudents = document.querySelectorAll('.student-checkbox:checked');
            
            if (selectedStudents.length === 0) {
                e.preventDefault();
                alert('Veuillez sélectionner au moins un élève');
                return false;
            }
            
            // Confirmation UNIQUE
            e.preventDefault(); // Empêcher la soumission immédiate
            
            const confirmMsg = confirm(
                `Vous êtes sur le point de réinscrire ${selectedStudents.length} élève(s).\n` +
                `Confirmez-vous cette opération ?`
            );
            
            if (confirmMsg) {
                isSubmitting = true;
                // Soumettre le formulaire manuellement
                reinscriptionForm.submit();
            }
            return false;
        });
        
        // Réinitialisation
        resetBtn.addEventListener('click', function(e) {
            e.preventDefault();
            isSubmitting = false;
            anneeSourceSelect.value = '';
            classeOriginSelect.innerHTML = '<option value="">Sélectionnez d\'abord une année</option>';
            classeOriginSelect.disabled = true;
            document.querySelector('select[name="classe_id"]').value = '';
            studentsSection.style.display = 'none';
            studentsList.innerHTML = '';
            selectAllCheckbox.checked = false;
            submitBtn.innerHTML = `<i class="ti ti-users me-2"></i>Réinscrire les élèves sélectionnés`;
            document.querySelectorAll('.student-info-message').forEach(el => el.remove());
        });
    });
}
</script>
@endsection