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
                <form method="POST" action="{{ route('notes.store') }}">
                    @csrf
                    
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Classe <span class="text-danger">*</span></label>
                                <select name="classe_id" id="classe_id" class="form-select" required>
                                    <option value="">Sélectionner une classe</option>
                                    @foreach($classes as $classe)
                                        <option value="{{ $classe->id }}">{{ $classe->nom }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Matière <span class="text-danger">*</span></label>
                                <select name="matiere_id" class="form-select" required>
                                    <option value="">Sélectionner une matière</option>
                                    <!-- Les options seront ajoutées dynamiquement via AJAX -->
                                </select>
                            </div>
                        </div>


                        <div class="col-md-2">
                            <div class="mb-3">
                                <label class="form-label">Coefficient <span class="text-danger">*</span></label>
                                <input type="number" name="coefficient" class="form-control" min="1" value="1" readonly required>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Mois / Trimestre <span class="text-danger">*</span></label>
                                <select name="mois_id" class="form-select" required>
                                    <option value="">Sélectionner un mois</option>
                                    @foreach($moisScolaire as $mois)
                                        <option value="{{ $mois->id }}">{{ $mois->nom }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        
                        
                        
                        
                    </div>
                    
                    <div id="eleves-container">
                        <!-- Les élèves seront chargés ici via AJAX -->
                        <div class="alert alert-info">
                            Veuillez sélectionner une classe pour afficher la liste des élèves
                        </div>
                    </div>
                    
                    <div class="text-end mt-4">
                        <button type="submit" class="btn btn-primary" id="submit-btn" disabled>
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
    toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "timeOut": "4000"
    };
    var matieresData = {}; // clé = id de la matière


    // Charger élèves et matières quand la classe change
    $('#classe_id').change(function() {
        var classeId = $(this).val();

        if(classeId) {
            // Charger les élèves
            $.ajax({
                url: '{{ route("notes.inscriptions_by_classe") }}',
                type: 'GET',
                data: { classe_id: classeId },
                success: function(data) {
                    var html = '';
                    if (data.length > 0) {
                        html += '<div class="table-responsive"><table class="table table-bordered"><tbody>';
                        $.each(data, function(i, e) {
                            html += '<tr>';
                            html += '<td>'+(i+1)+'</td>';
                            html += '<td>'+e.nom_complet+'</td>';
                            html += '<td>';
                            html += '<input type="hidden" name="notes['+i+'][inscription_id]" value="'+e.id+'">';
                            html += '<input type="number" name="notes['+i+'][valeur]" class="form-control note-input" step="0.01" min="0" style="width:70px; display:inline-block;">';

                            // Champ base vide au départ, sera rempli après le choix de la matière
                            html += ' / <input type="number" class="form-control note-base" readonly style="width:50px; display:inline-block;">';

                            html += '</td></tr>';
                        });

                        html += '</tbody></table></div>';
                        $('#eleves-container').html(html);
                        $('#submit-btn').prop('disabled', false);
                    } else {
                        $('#eleves-container').html('<div class="alert alert-warning">Aucun élève trouvé</div>');
                        $('#submit-btn').prop('disabled', true);
                        toastr.warning("Aucun élève trouvé pour cette classe ⚠️");
                    }
                },
                error: function() {
                    toastr.error("Erreur lors du chargement des élèves ❌");
                }
            });


            // Charger les matières
            $.ajax({
                url: '{{ route("notes.matieres_by_classe") }}',
                type: 'GET',
                data: { classe_id: classeId },
                success: function(matieres) {
                    var matSelect = $('select[name="matiere_id"]');
                    matSelect.empty().append('<option value="">Sélectionner une matière</option>');

                    matieres.forEach(function(m) {
                        matSelect.append('<option value="'+m.id+'" data-coef="'+m.coefficient+'" data-base="'+m.base+'">'+m.nom+'</option>');
                        matieresData[m.id] = {
                            coef: m.coefficient,
                            base: m.base
                        };
                    });
                },
                error: function() {
                    toastr.error("Erreur lors du chargement des matières ❌");
                }
            });
        }
    });

    // Quand la matière change → maj du coef
    $('select[name="matiere_id"]').change(function() {
        var matId = $(this).val();
        if(matId && matieresData[matId]) {
            var coef = matieresData[matId].coef;
            var base = matieresData[matId].base;

            // Met à jour le coefficient
            $('input[name="coefficient"]').val(coef);

            // Met à jour les bases et max des notes
            $('#eleves-container tr').each(function() {
                var noteInput = $(this).find('input.note-input');
                var baseInput = $(this).find('input.note-base');

                // Update readonly display
                baseInput.val(base);

                // Update max de l’input note
                noteInput.attr('max', base);
            });

            // Charger les notes existantes pour cette matière
            chargerNotes();
        }
    });

    $('form').on('submit', function(e) {
        var mois = $('select[name="mois_id"]').val();
        if(!mois) {
            e.preventDefault();
            toastr.error("Veuillez sélectionner un mois ou trimestre avant d'enregistrer ⚠️");
            return false;
        }

        var valid = true;

        $('#eleves-container .note-input').each(function() {
            var val = $(this).val();
            var max = parseFloat($(this).attr('max')) || 20;

            if(val === '' || val === null) {
                valid = false;
                toastr.warning("Toutes les notes doivent être saisies ⚠️");
                return false; // sort de la boucle each
            }

            val = parseFloat(val);
            if(val < 0 || val > max) {
                valid = false;
                toastr.warning("Chaque note doit être entre 0 et la base ("+max+") ⚠️");
                return false; // sort de la boucle each
            }
        });

        if(!valid) {
            e.preventDefault();
            return false;
        }
    });



    $('#eleves-container').on('input', '.note-input', function() {
        var max = parseFloat($(this).attr('max')) || 20; // récupère le max défini
        var val = parseFloat($(this).val());

        if(val > max) {
            $(this).val(max); // remet au maximum si dépassé
            toastr.warning("La note ne peut pas dépasser la base (" + max + ")");
        } else if(val < 0) {
            $(this).val(0); // empêche les valeurs négatives
        }
    });



    // Quand le mois change → charger les notes
    $('select[name="mois_id"]').change(function() {
        chargerNotes();
    });

    // Fonction pour charger les notes existantes
    function chargerNotes() {
        var classeId = $('#classe_id').val();
        var matiereId = $('select[name="matiere_id"]').val();
        var moisId = $('select[name="mois_id"]').val();

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
                    if (notes.length > 0) {
                        notes.forEach(function(note) {
                            // On trouve l'input hidden qui correspond à l'inscription
                            var hidden = $('input[type="hidden"][name^="notes"][name$="[inscription_id]"][value="'+note.inscription_id+'"]');
                            
                            if (hidden.length) {
                                // On prend l'input note juste après (dans la même cellule <td>)
                                hidden.closest('td').find('input[name^="notes"][name$="[valeur]"]').val(note.valeur);
                            }
                        });
                        toastr.success("Notes existantes chargées et pré-remplies 📑");
                    } else {
                        toastr.info("Aucune note enregistrée pour ce mois/matière");
                        $('input[name^="notes"][name$="[valeur]"]').val('');
                    }
                },
                error: function() {
                    toastr.error("Erreur lors du chargement des notes ❌");
                }
            });
        }
    }

    // Messages Laravel backend
    @if(session('success'))
        toastr.success("{{ session('success') }}");
    @endif
    @if($errors->any())
        @foreach($errors->all() as $error)
            toastr.error("{{ $error }}");
        @endforeach
    @endif
});
</script>

@endsection