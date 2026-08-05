@extends('dashboard.layouts.master')

@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
    <div class="my-auto mb-2">
        <h3 class="page-title mb-1">Saisie groupée des Notes</h3>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Tableau de bord</a></li>
                <li class="breadcrumb-item"><a href="{{ route('notes.index') }}">Notes</a></li>
                <li class="breadcrumb-item active" aria-current="page">Saisie groupée</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <form id="notesForm">
                    @csrf
                    
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Classe <span class="text-danger">*</span></label>
                                <select name="classe_id" id="classe_id" class="form-select" required>
                                    <option value="">Sélectionner une classe</option>
                                    @foreach($classes as $classe)
                                        <option value="{{ $classe->id }}" 
                                            {{ old('classe_id') == $classe->id ? 'selected' : '' }}>
                                            {{ $classe->nom }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Matière <span class="text-danger">*</span></label>
                                <select name="matiere_id" id="matiere_id" class="form-select" required>
                                    <option value="">Sélectionner une matière</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="mb-3">
                                <label class="form-label">Coefficient <span class="text-danger">*</span></label>
                                <input type="number" name="coefficient" id="coefficient" class="form-control" min="1" 
                                    value="{{ old('coefficient', 1) }}" readonly required>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Mois / Trimestre <span class="text-danger">*</span></label>
                                <select name="mois_id" id="mois_id" class="form-select" required>
                                    <option value="">Sélectionner un mois</option>
                                    @foreach($moisScolaire as $mois)
                                        <option value="{{ $mois->id }}"
                                            {{ old('mois_id') == $mois->id ? 'selected' : '' }}>
                                            {{ $mois->nom }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div id="eleves-container">
                        @if(old('classe_id'))
                            <div class="text-center"><div class="spinner-border text-primary"></div><p>Chargement des élèves...</p></div>
                        @else
                            <div class="alert alert-info">
                                Veuillez sélectionner une classe pour afficher la liste des élèves
                            </div>
                        @endif
                    </div>
                    
                    <div class="text-end mt-4">
                        <button type="button" class="btn btn-primary" id="submit-btn" {{ old('classe_id') ? '' : 'disabled' }}>
                            <i class="ti ti-check me-2"></i>Enregistrer toutes les notes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    var matieresData = {};
    var oldValues = {
        classe_id: "{{ old('classe_id') }}",
        matiere_id: "{{ old('matiere_id') }}",
        mois_id: "{{ old('mois_id') }}",
        coefficient: "{{ old('coefficient', 1) }}"
    };

    // ==================== CHARGEMENT DES MATIÈRES ====================
    function chargerMatieres(classeId, matiereId = null) {
        if(classeId) {
            var matSelect = $('#matiere_id');
            matSelect.html('<option value="">Chargement...</option>');
            
            $.ajax({
                url: '{{ route("notes.matieres_by_classe") }}',
                type: 'GET',
                data: { classe_id: classeId },
                success: function(matieres) {
                    matSelect.empty().append('<option value="">Sélectionner une matière</option>');

                    if (matieres.length === 0) {
                        matSelect.append('<option value="">Aucune matière trouvée</option>');
                        toastr.warning("Aucune matière trouvée pour cette classe");
                        return;
                    }

                    matieres.forEach(function(m) {
                        var selected = (matiereId && m.id == matiereId) ? 'selected' : '';
                        matSelect.append('<option value="'+m.id+'" data-coef="'+m.coefficient+'" data-base="'+m.base+'" '+selected+'>'+m.nom+'</option>');
                        matieresData[m.id] = {
                            coef: m.coefficient,
                            base: m.base
                        };
                    });

                    if (matiereId && matieresData[matiereId]) {
                        var coef = matieresData[matiereId].coef;
                        var base = matieresData[matiereId].base;
                        $('#coefficient').val(coef);
                    }
                },
                error: function(xhr) {
                    toastr.error("Erreur lors du chargement des matières ❌");
                    matSelect.empty().append('<option value="">Sélectionner une matière</option>');
                }
            });
        }
    }

    // ==================== CHARGEMENT DES ÉLÈVES ====================
    function chargerEleves(classeId) {
        if(classeId) {
            $('#eleves-container').html('<div class="text-center"><div class="spinner-border text-primary"></div><p>Chargement des élèves...</p></div>');
            
            $.ajax({
                url: '{{ route("notes.eleves_by_classe") }}',
                type: 'GET',
                data: { classe_id: classeId },
                success: function(data) {
                    var html = '';
                    if (data.length > 0) {
                        html += '<div class="table-responsive"><table class="table table-bordered"><tbody>';
                        $.each(data, function(i, e) {
                            var oldNote = getOldNoteValue(e.id);
                            html += '<tr>';
                            html += '<td>'+(i+1)+'</td>';
                            html += '<td>'+e.nom_complet+'</td>';
                            html += '<td>';
                            html += '<input type="hidden" name="notes['+i+'][eleve_id]" value="'+e.id+'">';
                            html += '<input type="number" name="notes['+i+'][valeur]" class="form-control note-input" step="0.01" min="0" value="'+oldNote+'" style="width:80px; display:inline-block;">';
                            html += ' / <input type="number" class="form-control note-base" readonly style="width:50px; display:inline-block;">';
                            html += '</td></tr>';
                        });

                        html += '</tbody></table></div>';
                        $('#eleves-container').html(html);
                        $('#submit-btn').prop('disabled', false);
                        
                        var matiereId = $('#matiere_id').val();
                        if(matiereId && matieresData[matiereId]) {
                            var base = matieresData[matiereId].base;
                            $('#eleves-container tr').each(function() {
                                $(this).find('input.note-base').val(base);
                            });
                        }
                    } else {
                        $('#eleves-container').html('<div class="alert alert-warning">Aucun élève trouvé dans cette classe</div>');
                        $('#submit-btn').prop('disabled', true);
                        toastr.warning("Aucun élève trouvé pour cette classe ⚠️");
                    }
                },
                error: function(xhr) {
                    toastr.error("Erreur lors du chargement des élèves ❌");
                    $('#eleves-container').html('<div class="alert alert-danger">Erreur lors du chargement des élèves</div>');
                }
            });
        }
    }

    function getOldNoteValue(eleveId) {
        var oldNotes = {!! json_encode(old('notes', [])) !!};
        for (var i in oldNotes) {
            if (oldNotes[i] && oldNotes[i].eleve_id == eleveId) {
                return oldNotes[i].valeur !== undefined && oldNotes[i].valeur !== null ? oldNotes[i].valeur : '';
            }
        }
        return '';
    }

    // ==================== CHARGEMENT DES NOTES EXISTANTES ====================
    function chargerNotes() {
        var classeId = $('#classe_id').val();
        var matiereId = $('#matiere_id').val();
        var moisId = $('#mois_id').val();

        if(classeId && matiereId && moisId) {
            $.ajax({
                url: '{{ route("notes.byClasse") }}',
                type: 'GET',
                data: {
                    classe_id: classeId,
                    matiere_id: matiereId,
                    mois_id: moisId
                },
                success: function(notes) {
                    if (notes && notes.length > 0) {
                        notes.forEach(function(note) {
                            var hidden = $('input[type="hidden"][name*="[eleve_id]"][value="'+note.eleve_id+'"]');
                            if (hidden.length) {
                                var td = hidden.closest('td');
                                td.find('input.note-input').val(note.valeur);
                            }
                        });
                        // Supprimé toastr.success ici pour éviter le double message
                    } else {
                        $('.note-input').val('');
                    }
                },
                error: function() {
                    toastr.error("Erreur lors du chargement des notes ❌");
                }
            });
        }
    }

    // ==================== RESTAURATION DES VALEURS ====================
    function restaurerValeurs() {
        if (oldValues.classe_id) {
            $('#classe_id').val(oldValues.classe_id);
            chargerMatieres(oldValues.classe_id, oldValues.matiere_id);
            chargerEleves(oldValues.classe_id);
            
            if (oldValues.mois_id) {
                $('#mois_id').val(oldValues.mois_id);
            }
            
            setTimeout(function() {
                if (oldValues.matiere_id && oldValues.mois_id) {
                    chargerNotes();
                }
            }, 1000);
        }
    }

    // ==================== INITIALISATION ====================
    restaurerValeurs();

    // ==================== ÉVÉNEMENTS ====================
    $('#classe_id').change(function() {
        var classeId = $(this).val();
        if(classeId) {
            matieresData = {};
            chargerMatieres(classeId);
            chargerEleves(classeId);
            $('input[name^="notes"][name$="[valeur]"]').val('');
        } else {
            $('#eleves-container').html('<div class="alert alert-info">Veuillez sélectionner une classe pour afficher la liste des élèves</div>');
            $('#submit-btn').prop('disabled', true);
            $('#matiere_id').html('<option value="">Sélectionner une matière</option>');
        }
    });

    $('#matiere_id').change(function() {
        var matId = $(this).val();
        if(matId && matieresData[matId]) {
            var coef = matieresData[matId].coef;
            var base = matieresData[matId].base;

            $('#coefficient').val(coef);

            $('#eleves-container tr').each(function() {
                var noteInput = $(this).find('input.note-input');
                var baseInput = $(this).find('input.note-base');

                baseInput.val(base);
                noteInput.attr('max', base);
            });

            chargerNotes();
        } else {
            $('#coefficient').val(1);
        }
    });

    $('#mois_id').change(function() {
        chargerNotes();
    });

    // ==================== VALIDATION DES NOTES ====================
    $('#eleves-container').on('input', '.note-input', function() {
        var max = parseFloat($(this).attr('max')) || 20;
        var val = parseFloat($(this).val());

        if(val > max) {
            $(this).val(max);
            toastr.warning("La note ne peut pas dépasser la base (" + max + ")");
        } else if(val < 0) {
            $(this).val(0);
        }
    });

    // ==================== ENREGISTREMENT AJAX ====================
    $('#submit-btn').click(function(e) {
        e.preventDefault();
        
        var classeId = $('#classe_id').val();
        var matiereId = $('#matiere_id').val();
        var moisId = $('#mois_id').val();
        var coefficient = $('#coefficient').val();
        var notes = [];

        if (!classeId) {
            toastr.error("Veuillez sélectionner une classe");
            return;
        }
        if (!matiereId) {
            toastr.error("Veuillez sélectionner une matière");
            return;
        }
        if (!moisId) {
            toastr.error("Veuillez sélectionner un mois");
            return;
        }

        $('#eleves-container .note-input').each(function() {
            var eleveId = $(this).closest('td').find('input[name*="[eleve_id]"]').val();
            var valeur = $(this).val();
            
            if (eleveId) {
                notes.push({
                    eleve_id: eleveId,
                    valeur: valeur || ''
                });
            }
        });

        var hasNotes = notes.some(function(n) { 
            return n.valeur !== '' && n.valeur !== null; 
        });

        if (!hasNotes) {
            toastr.warning("Veuillez saisir au moins une note");
            return;
        }

        $('#submit-btn').prop('disabled', true);
        $('#submit-btn').html('<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement...');

        $.ajax({
            url: '{{ route("notes.store") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                classe_id: classeId,
                matiere_id: matiereId,
                mois_id: moisId,
                coefficient: coefficient,
                notes: notes
            },
            success: function(response) {
                toastr.success("Notes enregistrées avec succès !");
                $('#submit-btn').html('<i class="ti ti-check me-2"></i>Enregistrer toutes les notes');
                $('#submit-btn').prop('disabled', false);
                chargerNotes();
            },
            error: function(xhr) {
                $('#submit-btn').html('<i class="ti ti-check me-2"></i>Enregistrer toutes les notes');
                $('#submit-btn').prop('disabled', false);
                
                var errorMsg = "Erreur lors de l'enregistrement ❌";
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    var errors = Object.values(xhr.responseJSON.errors).flat();
                    errorMsg = errors.join(', ');
                }
                toastr.error(errorMsg);
            }
        });
    });
});
</script>
@endsection