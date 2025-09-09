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
                                <label class="form-label">Mois / Trimestre <span class="text-danger">*</span></label>
                                <select name="mois_id" class="form-select" required>
                                    <option value="">Sélectionner un mois</option>
                                    @foreach($moisScolaire as $mois)
                                        <option value="{{ $mois->id }}">{{ $mois->nom }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Matière <span class="text-danger">*</span></label>
                                <select name="matiere_id" class="form-select" required>
                                    <option value="">Sélectionner une matière</option>
                                    @foreach($matieres as $matiere)
                                        <option value="{{ $matiere->id }}">{{ $matiere->nom }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Coefficient <span class="text-danger">*</span></label>
                                <input type="number" name="coefficient" class="form-control" min="1" value="1" required>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Année scolaire <span class="text-danger">*</span></label>
                                <input type="text" name="annee_scolaire" class="form-control" value="{{ $anneeScolaire }}" readonly required>
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
        
        if (classeId) {
            $.ajax({
                url: '{{ route("notes.inscriptions_by_classe") }}',
                type: 'GET',
                data: { 
                    classe_id: classeId 
                },
                beforeSend: function() {
                    $('#eleves-container').html('<div class="alert alert-info">Chargement des élèves...</div>');
                },
                success: function(data) {
                    if (data.length > 0) {
                        var html = '<div class="table-responsive"><table class="table table-bordered">';
                        html += '<thead><tr><th width="5%">#</th><th>Élève</th><th>Matricule</th><th width="20%">Note (sur 20)</th></tr></thead><tbody>';
                        
                        $.each(data, function(index, inscription) {
                            html += '<tr>';
                            html += '<td>' + (index + 1) + '</td>';
                            html += '<td>' + inscription.nom_complet + '</td>';
                            html += '<td>' + inscription.matricule + '</td>';
                            html += '<td>';
                            html += '<input type="hidden" name="notes[' + index + '][inscription_id]" value="' + inscription.id + '">';
                            html += '<input type="number" name="notes[' + index + '][valeur]" class="form-control" step="0.01" min="0" max="20" required>';
                            html += '</td>';
                            html += '</tr>';
                        });
                        
                        html += '</tbody></table></div>';
                        $('#eleves-container').html(html);
                        $('#submit-btn').prop('disabled', false);
                    } else {
                        $('#eleves-container').html('<div class="alert alert-warning">Aucun élève trouvé dans cette classe</div>');
                        $('#submit-btn').prop('disabled', true);
                    }
                },
                error: function() {
                    $('#eleves-container').html('<div class="alert alert-danger">Erreur lors du chargement des élèves</div>');
                    $('#submit-btn').prop('disabled', true);
                }
            });
        } else {
            $('#eleves-container').html('<div class="alert alert-info">Veuillez sélectionner une classe pour afficher la liste des élèves</div>');
            $('#submit-btn').prop('disabled', true);
        }
    });
});
</script>
@endsection