@extends('dashboard.layouts.master')

@section('content')
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if ($errors->has('duplicate'))
    <div class="alert alert-warning">
        {{ $errors->first('duplicate') }}
    </div>
@endif

<h3 class="mb-4">Configuration des Frais Mensuels</h3>

<!-- Formulaire de filtres -->
<form method="GET" action="{{ route('tarifs-mensuels.index') }}" class="row mb-4 g-3" id="filterForm">
    <div class="col-md-4">
        <label for="filter_type_frais_id" class="form-label">Filtrer par Type de Frais</label>
        <select id="filter_type_frais_id" name="type_frais_id" class="form-select">
            <option value="">Tous les types</option>
            @foreach($typeFrais as $type)
                <option value="{{ $type->id }}" {{ $filters['type_frais_id'] == $type->id ? 'selected' : '' }}>
                    {{ $type->nom }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4">
        <label for="filter_niveau_id" class="form-label">Filtrer par Niveau</label>
        <select id="filter_niveau_id" name="niveau_id" class="form-select">
            <option value="">Tous les niveaux</option>
            @foreach($niveaux as $niveau)
                <option value="{{ $niveau->id }}" {{ $filters['niveau_id'] == $niveau->id ? 'selected' : '' }}>
                    {{ $niveau->nom }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4">
        <label for="filter_mois_id" class="form-label">Filtrer par Mois</label>
        <select id="filter_mois_id" name="mois_id" class="form-select">
            <option value="">Tous les mois</option>
            @foreach($moisScolaires as $mois)
                <option value="{{ $mois->id }}" {{ $filters['mois_id'] == $mois->id ? 'selected' : '' }}>
                    {{ $mois->nom }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-12 mt-3">
        <button type="submit" class="btn btn-primary">Appliquer les filtres</button>
        <a href="{{ route('tarifs-mensuels.index') }}" class="btn btn-secondary">Réinitialiser</a>
    </div>
</form>

<!-- Calendrier -->
<div class="card mb-4">
    <div class="card-body">
        <div id="calendar"></div>
    </div>
</div>

<!-- Formulaire d'ajout -->
<div class="card mt-4">
    <div class="card-body">
        <h5>Ajouter un tarif mensuel</h5>
        <form id="tarifForm" method="POST" action="{{ route('tarifs-mensuels.store') }}">
            @csrf
            <div class="row gy-3">
                <div class="col-md-3">
                    <label class="form-label">Type de Frais <span class="text-danger">*</span></label>
                    <select name="type_frais_id" id="type_frais_id" class="form-select" required>
                        <option value="" disabled selected>-- Sélectionnez --</option>
                        @foreach($typeFrais as $type)
                            <option value="{{ $type->id }}" {{ old('type_frais_id') == $type->id ? 'selected' : '' }}>
                                {{ $type->nom }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Niveau <span class="text-danger">*</span></label>
                    <select name="niveau_id" id="niveau_id" class="form-select" required disabled>
                        <option value="" disabled selected>-- Sélectionnez un type de frais d'abord --</option>
                        @if(old('niveau_id'))
                            @foreach($niveaux as $niveau)
                                <option value="{{ $niveau->id }}" {{ old('niveau_id') == $niveau->id ? 'selected' : '' }}>
                                    {{ $niveau->nom }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                    <div id="loading-niveaux" class="spinner-border spinner-border-sm text-primary mt-2" style="display: none;" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Mois <span class="text-danger">*</span></label>
                    <select name="mois_id" id="mois_id" class="form-select" required disabled>
                        <option value="" disabled selected>-- Sélectionnez un niveau d'abord --</option>
                        @foreach($moisScolaires as $mois)
                            <option value="{{ $mois->id }}" {{ old('mois_id') == $mois->id ? 'selected' : '' }}>
                                {{ $mois->nom }}
                            </option>
                        @endforeach
                    </select>
                    <div id="mois-info" class="form-text text-warning mt-1" style="display: none;">
                        Ce mois a déjà un tarif défini pour cette combinaison.
                    </div>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Montant (FCFA) <span class="text-danger">*</span></label>
                    <input type="number" name="montant" id="montant" class="form-control" value="{{ old('montant') }}" min="0" required>
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-success" id="submit-btn" disabled>Enregistrer</button>
                <button type="button" id="sync-from-filters" class="btn btn-outline-primary">
                    <i class="fas fa-sync-alt"></i> Remplir depuis les filtres
                </button>
            </div>
        </form>
        
        <!-- Affichage des tarifs existants -->
        <div id="existing-tarifs" class="mt-4" style="display: none;">
            <h6>Tarifs déjà définis pour cette combinaison :</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>Mois</th>
                            <th>Montant</th>
                        </tr>
                    </thead>
                    <tbody id="tarifs-list">
                        <!-- Les tarifs seront ajoutés dynamiquement ici -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal d'édition -->
@if(isset($selectedTarif))
<div class="modal fade show" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-modal="true" style="display: block; padding-right: 17px;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Modifier le tarif</h5>
                <a href="{{ route('tarifs-mensuels.index') }}" class="btn-close"></a>
            </div>
            <div class="modal-body">
                <form id="editTarifForm" method="POST" action="{{ route('tarifs-mensuels.update', $selectedTarif->id) }}">
                    @csrf
                    @method('PUT')
                    
                    <div class="mb-3">
                        <label class="form-label">Type de Frais</label>
                        <select name="type_frais_id" id="edit_type_frais_id" class="form-select" required>
                            @foreach($typeFrais as $type)
                                <option value="{{ $type->id }}" {{ $selectedTarif->type_frais_id == $type->id ? 'selected' : '' }}>
                                    {{ $type->nom }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Niveau</label>
                        <select name="niveau_id" id="edit_niveau_id" class="form-select" required>
                            @foreach($niveaux as $niveau)
                                <option value="{{ $niveau->id }}" {{ $selectedTarif->niveau_id == $niveau->id ? 'selected' : '' }}>
                                    {{ $niveau->nom }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Mois</label>
                        <select name="mois_id" id="edit_mois_id" class="form-select" required>
                            @foreach($moisScolaires as $mois)
                                <option value="{{ $mois->id }}" {{ $selectedTarif->mois_id == $mois->id ? 'selected' : '' }}>
                                    {{ $mois->nom }}
                                </option>
                            @endforeach
                        </select>
                        <div id="edit-mois-info" class="form-text text-warning mt-1" style="display: none;">
                            Ce mois a déjà un tarif défini pour cette combinaison.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Montant (FCFA)</label>
                        <input type="number" name="montant" id="edit_montant" class="form-control" value="{{ $selectedTarif->montant }}" min="0" required>
                    </div>
                    
                    <div class="modal-footer">
                        <a href="{{ route('tarifs-mensuels.index') }}" class="btn btn-secondary">Fermer</a>

                        <!-- Formulaire de suppression -->
                        <form action="{{ route('tarifs-mensuels.destroy', $selectedTarif->id) }}" method="POST" class="d-inline"
                            onsubmit="return confirm('Voulez-vous vraiment supprimer ce tarif ?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">Supprimer</button>
                        </form>

                        <!-- Bouton de mise à jour -->
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<div class="modal-backdrop fade show"></div>
@endif

@endsection

@section('scripts')
<!-- FullCalendar CSS -->
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
<!-- FullCalendar JS -->
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/fr.min.js'></script>
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var events = @json($events);

        var calendar = new FullCalendar.Calendar(calendarEl, {
            locale: 'fr',
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,listYear'
            },
            events: events,
            eventDisplay: 'block',
            eventDidMount: function(info) {
                var tooltipContent =
                    '<b>' + info.event.title + '</b><br>' +
                    'Niveau: ' + info.event.extendedProps.niveau + '<br>' +
                    'Mois: ' + info.event.extendedProps.mois;

                new bootstrap.Tooltip(info.el, {
                    title: tooltipContent,
                    html: true,
                    placement: 'top'
                });
            }
        });

        calendar.render();

        // Gestion AJAX pour vérifier les doublons
        const typeSelect = document.getElementById('type_frais_id');
        const niveauSelect = document.getElementById('niveau_id');
        const moisSelect = document.getElementById('mois_id');
        const moisInfo = document.getElementById('mois-info');
        const existingTarifsSection = document.getElementById('existing-tarifs');
        const tarifsList = document.getElementById('tarifs-list');
        const tarifForm = document.getElementById('tarifForm');
        const syncButton = document.getElementById('sync-from-filters');

        // Filtres
        const filterTypeSelect = document.getElementById('filter_type_frais_id');
        const filterNiveauSelect = document.getElementById('filter_niveau_id');
        const filterMoisSelect = document.getElementById('filter_mois_id');

        // Éléments pour l'édition
        const editTypeSelect = document.getElementById('edit_type_frais_id');
        const editNiveauSelect = document.getElementById('edit_niveau_id');
        const editMoisSelect = document.getElementById('edit_mois_id');
        const editMoisInfo = document.getElementById('edit-mois-info');
        const editTarifForm = document.getElementById('editTarifForm');

        // Fonction pour vérifier l'existence d'un tarif
        function checkExistingTarif(typeId, niveauId, moisId, excludeId = null) {
            if (!typeId || !niveauId || !moisId) return;

            const formData = new FormData();
            formData.append('type_frais_id', typeId);
            formData.append('niveau_id', niveauId);
            formData.append('mois_id', moisId);
            if (excludeId) {
                formData.append('exclude_id', excludeId);
            }

            fetch('{{ route("tarifs-mensuels.check-existing") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.exists) {
                    moisInfo.style.display = 'block';
                } else {
                    moisInfo.style.display = 'none';
                }
            });
        }

        // Fonction pour charger les tarifs existants
        function loadExistingTarifs(typeId, niveauId) {
            if (!typeId || !niveauId) {
                existingTarifsSection.style.display = 'none';
                return;
            }

            fetch(`{{ route("tarifs-mensuels.get-tarifs") }}?type_frais_id=${typeId}&niveau_id=${niveauId}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.tarifs.length > 0) {
                    tarifsList.innerHTML = '';
                    data.tarifs.forEach(tarif => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${tarif.mois_nom}</td>
                            <td>${tarif.montant.toLocaleString()} FCFA</td>
                        `;
                        tarifsList.appendChild(row);
                    });
                    existingTarifsSection.style.display = 'block';
                } else {
                    existingTarifsSection.style.display = 'none';
                }
            });
        }

        // Fonction pour charger les niveaux par type de frais
        function loadNiveauxByType(typeId) {
            if (!typeId) {
                niveauSelect.innerHTML = '<option value="" disabled selected>-- Sélectionnez un type de frais d\'abord --</option>';
                niveauSelect.disabled = true;
                moisSelect.disabled = true;
                document.getElementById('submit-btn').disabled = true;
                return;
            }

            const loadingElement = document.getElementById('loading-niveaux');
            
            loadingElement.style.display = 'inline-block';
            niveauSelect.disabled = true;

            fetch(`{{ route("tarifs-mensuels.niveaux-by-type") }}?type_frais_id=${typeId}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                niveauSelect.innerHTML = '<option value="" disabled selected>-- Sélectionnez un niveau --</option>';
                
                if (data.niveaux.length > 0) {
                    data.niveaux.forEach(niveau => {
                        const option = document.createElement('option');
                        option.value = niveau.id;
                        option.textContent = niveau.nom;
                        niveauSelect.appendChild(option);
                    });
                    niveauSelect.disabled = false;
                    
                    // Si un filtre de niveau est déjà sélectionné, pré-sélectionnez-le
                    if (filterNiveauSelect.value) {
                        niveauSelect.value = filterNiveauSelect.value;
                        moisSelect.disabled = false;
                        loadExistingTarifs(typeId, filterNiveauSelect.value);
                    }
                } else {
                    niveauSelect.innerHTML = '<option value="" disabled selected>Aucun niveau trouvé pour ce type de frais</option>';
                    niveauSelect.disabled = true;
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                niveauSelect.innerHTML = '<option value="" disabled selected>Erreur de chargement</option>';
            })
            .finally(() => {
                loadingElement.style.display = 'none';
            });
        }

        // Fonction pour synchroniser les filtres avec le formulaire
        function syncFromFilters() {
            if (filterTypeSelect.value) {
                typeSelect.value = filterTypeSelect.value;
                loadNiveauxByType(filterTypeSelect.value);
                
                // Attendre que les niveaux soient chargés
                setTimeout(() => {
                    if (filterNiveauSelect.value && niveauSelect.value) {
                        niveauSelect.value = filterNiveauSelect.value;
                        moisSelect.disabled = false;
                        
                        if (filterMoisSelect.value) {
                            moisSelect.value = filterMoisSelect.value;
                        }
                        
                        loadExistingTarifs(filterTypeSelect.value, filterNiveauSelect.value);
                        checkExistingTarif(filterTypeSelect.value, filterNiveauSelect.value, moisSelect.value);
                        
                        // Activer le bouton submit si tout est rempli
                        document.getElementById('submit-btn').disabled = !(
                            typeSelect.value && 
                            niveauSelect.value && 
                            moisSelect.value && 
                            document.getElementById('montant').value
                        );
                    }
                }, 500);
            }
        }

        // Événement pour le bouton de synchronisation
        if (syncButton) {
            syncButton.addEventListener('click', syncFromFilters);
        }

        // Événements pour le formulaire d'ajout
        if (typeSelect && niveauSelect && moisSelect) {
            // Chargement initial si un type est déjà sélectionné
            if (typeSelect.value) {
                loadNiveauxByType(typeSelect.value);
            }

            typeSelect.addEventListener('change', function() {
                const typeId = this.value;
                
                // Réinitialiser les champs dépendants
                niveauSelect.innerHTML = '<option value="" disabled selected>-- Chargement des niveaux --</option>';
                niveauSelect.disabled = true;
                moisSelect.disabled = true;
                document.getElementById('submit-btn').disabled = true;
                
                // Cacher les informations existantes
                existingTarifsSection.style.display = 'none';
                moisInfo.style.display = 'none';
                
                // Charger les niveaux
                loadNiveauxByType(typeId);
            });

            niveauSelect.addEventListener('change', function() {
                const typeId = typeSelect.value;
                const niveauId = this.value;
                const moisId = moisSelect.value;
                
                // Activer le mois
                moisSelect.disabled = !niveauId;
                
                // Activer le bouton submit si tous les champs sont remplis
                document.getElementById('submit-btn').disabled = !(typeId && niveauId && moisSelect.value && document.getElementById('montant').value);
                
                // Charger les tarifs existants
                loadExistingTarifs(typeId, niveauId);
                
                // Vérifier le mois si déjà sélectionné
                if (moisId) {
                    checkExistingTarif(typeId, niveauId, moisId);
                }
            });

            moisSelect.addEventListener('change', function() {
                const typeId = typeSelect.value;
                const niveauId = niveauSelect.value;
                const moisId = this.value;
                
                // Activer le bouton submit si tous les champs sont remplis
                document.getElementById('submit-btn').disabled = !(typeId && niveauId && moisId && document.getElementById('montant').value);
                
                if (moisId) {
                    checkExistingTarif(typeId, niveauId, moisId);
                }
            });

            // Écouter les changements sur le montant
            document.getElementById('montant').addEventListener('input', function() {
                const typeId = typeSelect.value;
                const niveauId = niveauSelect.value;
                const moisId = moisSelect.value;
                const montant = this.value;
                
                document.getElementById('submit-btn').disabled = !(typeId && niveauId && moisId && montant);
            });

            // Vérification initiale si des valeurs sont déjà sélectionnées
            if (typeSelect.value && niveauSelect.value && moisSelect.value) {
                checkExistingTarif(typeSelect.value, niveauSelect.value, moisSelect.value);
                loadExistingTarifs(typeSelect.value, niveauSelect.value);
            }
        }

        // Événements pour le formulaire d'édition
        if (editTypeSelect && editNiveauSelect && editMoisSelect) {
            const tarifId = {{ $selectedTarif->id ?? 'null' }};
            
            editTypeSelect.addEventListener('change', function() {
                const typeId = this.value;
                const niveauId = editNiveauSelect.value;
                const moisId = editMoisSelect.value;
                
                if (moisId) {
                    checkExistingTarif(typeId, niveauId, moisId, tarifId);
                }
            });

            editNiveauSelect.addEventListener('change', function() {
                const typeId = editTypeSelect.value;
                const niveauId = this.value;
                const moisId = editMoisSelect.value;
                
                if (moisId) {
                    checkExistingTarif(typeId, niveauId, moisId, tarifId);
                }
            });

            editMoisSelect.addEventListener('change', function() {
                const typeId = editTypeSelect.value;
                const niveauId = editNiveauSelect.value;
                const moisId = this.value;
                
                if (moisId) {
                    checkExistingTarif(typeId, niveauId, moisId, tarifId);
                }
            });

            // Vérification initiale pour l'édition
            if (editTypeSelect.value && editNiveauSelect.value && editMoisSelect.value) {
                checkExistingTarif(editTypeSelect.value, editNiveauSelect.value, editMoisSelect.value, tarifId);
            }
        }

        // Empêcher la soumission du formulaire si un doublon existe
        if (tarifForm) {
            tarifForm.addEventListener('submit', function(e) {
                const typeId = typeSelect.value;
                const niveauId = niveauSelect.value;
                const moisId = moisSelect.value;
                
                if (typeId && niveauId && moisId) {
                    // Vérification synchrone pour éviter la soumission
                    const formData = new FormData();
                    formData.append('type_frais_id', typeId);
                    formData.append('niveau_id', niveauId);
                    formData.append('mois_id', moisId);

                    fetch('{{ route("tarifs-mensuels.check-existing") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.exists) {
                            e.preventDefault();
                            alert('Un tarif existe déjà pour cette combinaison type/niveau/mois. Veuillez choisir une autre combinaison.');
                        }
                    });
                }
            });
        }

        // Même chose pour le formulaire d'édition
        if (editTarifForm) {
            editTarifForm.addEventListener('submit', function(e) {
                const typeId = editTypeSelect.value;
                const niveauId = editNiveauSelect.value;
                const moisId = editMoisSelect.value;
                const tarifId = {{ $selectedTarif->id ?? 'null' }};
                
                if (typeId && niveauId && moisId) {
                    const formData = new FormData();
                    formData.append('type_frais_id', typeId);
                    formData.append('niveau_id', niveauId);
                    formData.append('mois_id', moisId);
                    formData.append('exclude_id', tarifId);

                    fetch('{{ route("tarifs-mensuels.check-existing") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.exists) {
                            e.preventDefault();
                            alert('Un tarif existe déjà pour cette combinaison type/niveau/mois. Veuillez choisir une autre combinaison.');
                        }
                    });
                }
            });
        }

        // Synchronisation automatique si des filtres sont présents
        @if($filters['type_frais_id'] || $filters['niveau_id'] || $filters['mois_id'])
        setTimeout(syncFromFilters, 1000);
        @endif
    });
</script>

<style>
    /* Couleurs par type de frais */
    .fc-event-inscription {
        background-color: #3498db !important;
        border-color: #3498db !important;
        color: white !important;
    }
    .fc-event-scolarite {
        background-color: #2ecc71 !important;
        border-color: #2ecc71 !important;
        color: white !important;
    }
    .fc-event-cantine {
        background-color: #e74c3c !important;
        border-color: #e74c3c !important;
        color: white !important;
    }
    .fc-event-transport {
        background-color: #f39c12 !important;
        border-color: #f39c12 !important;
        color: white !important;
    }
    
    /* Style pour le modal affiché sans JavaScript */
    .modal.show {
        display: block;
        background-color: rgba(0,0,0,0.5);
    }
    
    /* Style pour le bouton de synchronisation */
    #sync-from-filters {
        margin-left: 10px;
    }
</style>
@endsection