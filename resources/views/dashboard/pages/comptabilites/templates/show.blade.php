@extends('dashboard.layouts.master')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Détails du modèle : {{ $template->nom }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('templates.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Retour
                        </a>
                        <a href="{{ route('templates.edit', $template->id) }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i> Modifier
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <!-- Informations générales -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Informations générales</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th style="width: 30%;">Nom</th>
                                            <td>{{ $template->nom }}</td>
                                        </tr>
                                        <tr>
                                            <th>Type</th>
                                            <td>
                                                <span class="badge badge-info">
                                                    {{ $types[$template->type] ?? $template->type }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Description</th>
                                            <td>{{ $template->description ?? 'Non renseignée' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Statut</th>
                                            <td>
                                                @if($template->is_active)
                                                    <span class="badge badge-success">Actif</span>
                                                @else
                                                    <span class="badge badge-danger">Inactif</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Modèle par défaut</th>
                                            <td>
                                                @if($template->is_default)
                                                    <span class="badge badge-warning">Oui</span>
                                                @else
                                                    <span class="badge badge-secondary">Non</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Créé le</th>
                                            <td>{{ $template->created_at->format('d/m/Y à H:i:s') }}</td>
                                        </tr>
                                        <tr>
                                            <th>Dernière modification</th>
                                            <td>{{ $template->updated_at->format('d/m/Y à H:i:s') }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <!-- Variables disponibles -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Variables disponibles</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        @foreach($variables as $var => $label)
                                            <div class="col-md-4 col-sm-6">
                                                <span class="badge badge-primary" style="font-size: 14px; margin: 3px; padding: 8px 12px;">
                                                    {{ $var }}
                                                    <small class="text-light">({{ $label }})</small>
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <!-- Aperçu -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Aperçu du modèle</h5>
                                    <button type="button" class="btn btn-primary btn-sm float-right" id="preview-btn">
                                        <i class="fas fa-eye"></i> Aperçu
                                    </button>
                                </div>
                                <div class="card-body" id="preview-container" style="max-height: 400px; overflow-y: auto;">
                                    <div class="text-muted text-center p-5">
                                        <i class="fas fa-file-alt fa-3x"></i>
                                        <p class="mt-3">Cliquez sur "Aperçu" pour visualiser le modèle</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions rapides -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Actions</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <a href="{{ route('templates.edit', $template->id) }}" class="btn btn-warning">
                                            <i class="fas fa-edit"></i> Modifier
                                        </a>
                                        <button type="button" class="btn btn-danger delete-template" 
                                                data-id="{{ $template->id }}" 
                                                data-nom="{{ $template->nom }}">
                                            <i class="fas fa-trash"></i> Supprimer
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Aperçu -->
<div class="modal fade" id="previewModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Aperçu du modèle : {{ $template->nom }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="preview-modal-body">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Chargement...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmation de suppression</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer le modèle : <strong id="delete-template-name"></strong> ?</p>
                <p class="text-danger">Cette action est irréversible.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                <form id="delete-form" method="POST" style="display: inline-block;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Supprimer</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Aperçu
    $('#preview-btn').click(function() {
        var content = `{!! addslashes($template->content) !!}`;

        // Remplacer les variables par des données de test
        var previewContent = content;
        var testData = {
            'ECOLE': 'Mon École',
            'ECOLE_ADRESSE': '123 Rue de l\'École',
            'ECOLE_TELEPHONE': '+225 07 00 00 00 00',
            'ECOLE_EMAIL': 'contact@ecole.com',
            'DATE': '{{ date("d/m/Y") }}',
            'DATE_FR': '{{ date("d/m/Y") }}',
            'ANNEE': '2024-2025',
            'NOM': 'KOUASSI',
            'PRENOM': 'Jean',
            'MATRICULE': '2024-001',
            'CLASSE': 'CM2 A',
            'NUMERO_RECU': 'REC-2024-001',
            'MONTANT': '25 000',
            'MONTANT_LETTRES': 'Vingt-cinq mille',
            'RESTE': '0',
            'TOTAL': '25 000',
            'TYPE_FRAIS': 'Scolarité',
            'MODE_PAIEMENT': 'Espèces',
            'REFERENCE': 'REF-2024-001',
            'MENSUALITE': '25 000',
            'MOIS': 'Janvier',
            'MONTANT_DU': '25 000',
            'DATE_ECHEANCE': '{{ date("d/m/Y") }}',
            'RETARD': '5',
            'MOIS_CONCERNE': 'Janvier',
            'NOMBRE_RELANCE': '1',
            'DELAI': '48 heures',
            'SANCTION': 'Exclusion temporaire',
            'NOM_RESPONSABLE': 'KOUASSI',
            'PRENOM_RESPONSABLE': 'Paul',
            'EVENEMENT': 'Réunion de parents',
            'DATE_EVENEMENT': '{{ date("d/m/Y", strtotime("+1 week")) }}',
            'LIEU': 'Salle polyvalente',
            'HEURE': '15h00',
            'OBJET': 'Information importante',
            'DETAIL': 'Nous vous informons de la tenue de...',
            'MOYENNE': '15.5',
            'RANG': '3',
            'EFFECTIF': '25',
            'APPRECIATION': 'Excellent travail',
        };

        for (var key in testData) {
            previewContent = previewContent.replace(new RegExp('%' + key + '%', 'g'), testData[key]);
        }

        $('#preview-modal-body').html(previewContent);
        $('#previewModal').modal('show');
    });

    // Gestion de la suppression
    $('.delete-template').click(function() {
        var id = $(this).data('id');
        var nom = $(this).data('nom');
        
        $('#delete-template-name').text(nom);
        $('#delete-form').attr('action', '/templates/' + id);
        $('#deleteModal').modal('show');
    });
});
</script>
@endpush