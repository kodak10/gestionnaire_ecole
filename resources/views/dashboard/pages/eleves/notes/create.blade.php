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
                        <div class="col-md-4">
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

                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Matière <span class="text-danger">*</span></label>
                                <select name="matiere_id" class="form-select" required>
                                    <option value="">Sélectionner une matière</option>
                                    <!-- Les options seront ajoutées dynamiquement via AJAX -->
                                </select>
                            </div>
                        </div>


                        <div class="col-md-3">
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
                    html += '<div class="table-responsive"><table class="table table-bordered">';
                    $.each(data, function(i, e) {
                        html += '<tr>';
                        html += '<td>'+(i+1)+'</td>';
                        html += '<td>'+e.nom_complet+'</td>';
                        html += '<td>';
                        html += '<input type="hidden" name="notes['+i+'][inscription_id]" value="'+e.id+'">';
                        html += '<input type="number" name="notes['+i+'][valeur]" class="form-control" step="0.01" min="0" max="20" required>';
                        html += '</td></tr>';
                    });
                    html += '</tbody></table></div>';
                    $('#eleves-container').html(html);
                    $('#submit-btn').prop('disabled', false);
                } else {
                    $('#eleves-container').html('<div class="alert alert-warning">Aucun élève trouvé</div>');
                    $('#submit-btn').prop('disabled', true);
                }
            }
        });

        // Charger les matières du niveau
        $.ajax({
            url: '{{ route("notes.matieres_by_classe") }}',
            type: 'GET',
            data: { classe_id: classeId },
            success: function(matieres) {
                var matSelect = $('select[name="matiere_id"]');
                matSelect.empty().append('<option value="">Sélectionner une matière</option>');
                matieres.forEach(function(m) {
                    matSelect.append('<option value="'+m.id+'" data-coef="'+m.coefficient+'">'+m.nom+'</option>');
                });
            }
        });
    } else {
        $('#eleves-container').html('<div class="alert alert-info">Veuillez sélectionner une classe</div>');
        $('select[name="matiere_id"]').empty().append('<option value="">Sélectionner une matière</option>');
        $('#submit-btn').prop('disabled', true);
    }
});

// Quand la matière change
$('select[name="matiere_id"]').change(function() {
    var coef = $(this).find(':selected').data('coef') || 1;
    $('input[name="coefficient"]').val(coef);
});


});

</script>
@endsection