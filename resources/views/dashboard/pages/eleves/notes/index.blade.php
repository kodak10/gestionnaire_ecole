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
            <select id="filter_classe" class="form-select">
                <option value="">Toutes les classes</option>
                @foreach($classes as $classe)
                    <option value="{{ $classe->id }}">{{ $classe->nom }}</option>
                @endforeach
            </select>
        </div>

        <div class="pe-1 mb-2">
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#bulletinModal">
                <i class="ti ti-file-spreadsheet me-2"></i>Générer Bulletin
            </button>
        </div>

        <div class="pe-1 mb-2">
            <a href="{{ route('notes.create') }}" class="btn btn-outline-primary">
                <i class="ti ti-file-spreadsheet me-2"></i>Saisie de Note
            </a>
        </div>
    </div>
</div>

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
                <tbody id="notesTableBody">
                    @foreach($notes as $note)
                    <tr>
                        <td>{{ $note->inscription->eleve->prenom }} {{ $note->inscription->eleve->nom }}</td>
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
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="col-md-12 text-center mt-4">
    {{ $notes->links() }}
</div>

<!-- Modal pour éditer une note -->
<div class="modal fade" id="editNoteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="editNoteForm">
        @csrf
        @method('PUT')
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier la note</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="note_id" id="note_id">
                <div class="mb-3">
                    <label>Valeur</label>
                    <input type="number" name="valeur" id="valeur" class="form-control" min="0" max="20" step="0.01" required>
                </div>
                <div class="mb-3">
                    <label>Coefficient</label>
                    <input type="number" name="coefficient" id="coefficient" class="form-control" min="1" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </div>
        </div>
    </form>
  </div>
</div>

<!-- Ajoutez ce modal à la fin du fichier -->
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

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {

    // Filtrer les notes par classe
    $('#filter_classe').change(function() {
        var classeId = $(this).val();
        $.ajax({
            url: '{{ route("notes.filterByClasse") }}',
            type: 'GET',
            data: { classe_id: classeId },
            success: function(data) {
                var tbody = '';
                if(data.length > 0){
                    $.each(data, function(i, note){
                        tbody += '<tr>';
                        tbody += '<td>'+note.eleve+'</td>';
                        tbody += '<td>'+note.matiere+'</td>';
                        tbody += '<td>'+note.classe+'</td>';
                        tbody += '<td>'+note.valeur.toFixed(2)+'</td>';
                        tbody += '<td>'+note.coefficient+'</td>';
                        tbody += '<td>'+note.mois+'</td>';
                        tbody += '</tr>';
                    });
                } else {
                    tbody = '<tr><td colspan="6" class="text-center">Aucune note trouvée</td></tr>';
                }
                $('#notesTableBody').html(tbody);
            }
        });
    });


    // Ouvrir le modal pour éditer une note
    // Ouvrir le modal pour éditer une note
    $(document).on('click', '.edit-btn', function() {
        var noteId = $(this).data('id');
        window.location.href = '/notes/' + noteId + '/edit';
    });


    // Soumettre le formulaire d'édition via AJAX
    $('#editNoteForm').submit(function(e) {
        e.preventDefault();
        var noteId = $('#note_id').val();
        var data = $(this).serialize();
        $.ajax({
            url: '/notes/' + noteId,
            type: 'PUT',
            data: data,
            success: function() {
                $('#editNoteModal').modal('hide');
                alert('Note mise à jour avec succès');
                $('#filter_classe').trigger('change'); // recharge la table
            },
            error: function(xhr) {
                alert('Erreur lors de la mise à jour');
            }
        });
    });

});
</script>


@endpush
@endsection
