@extends('dashboard.layouts.master')

@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
    <div class="my-auto mb-2">
        <h3 class="page-title mb-1">📄 Modifier le modèle</h3>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Tableau de bord</a></li>
                <li class="breadcrumb-item"><a href="{{ route('documents.templates.index') }}">Modèles</a></li>
                <li class="breadcrumb-item active" aria-current="page">Modifier</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('documents.templates.update', $document->id) }}" method="POST" id="template-form">
                    @csrf
                    @method('PUT')
                    
                    <div class="mb-3">
                        <label class="form-label">Type de document <span class="text-danger">*</span></label>
                        <select name="type" id="type" class="form-select" required>
                            <option value="">Sélectionner un type</option>
                            <option value="fiche_inscription" {{ $document->type == 'fiche_inscription' ? 'selected' : '' }}>Fiche d'Inscription</option>
                            <option value="certificat_scolaire" {{ $document->type == 'certificat_scolaire' ? 'selected' : '' }}>Certificat de Scolarité</option>
                            <option value="attestation_frequentation" {{ $document->type == 'attestation_frequentation' ? 'selected' : '' }}>Attestation de Fréquentation</option>
                            <option value="certificat_scolaire" {{ $document->type == 'certificat_scolaire' ? 'selected' : '' }}>Certificat de Nationalité</option>
                            <option value="bulletin_mensuel" {{ $document->type == 'bulletin_mensuel' ? 'selected' : '' }}>Bulletin Mensuel</option>
                            <option value="bulletin_annuel" {{ $document->type == 'bulletin_annuel' ? 'selected' : '' }}>Bulletin Annuel</option>
                            <option value="fiche_presence" {{ $document->type == 'fiche_presence' ? 'selected' : '' }}>Fiche de Présence</option>
                            <option value="fiche_frequentation" {{ $document->type == 'fiche_frequentation' ? 'selected' : '' }}>Fiche de Fréquentation</option>
                            <option value="parchemin" {{ $document->type == 'parchemin' ? 'selected' : '' }}>Parchemin</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Contenu du modèle <span class="text-danger">*</span></label>
                        <textarea name="content" id="editor" class="form-control" rows="15" required>{{ $document->content }}</textarea>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy me-2"></i>Mettre à jour
                        </button>
                        <a href="{{ route('documents.templates.index') }}" class="btn btn-secondary">
                            <i class="ti ti-arrow-left me-2"></i>Retour
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">📋 Variables disponibles</h5>
                <hr>
                <div id="variables-container" style="max-height: 500px; overflow-y: auto;">
                    <!-- Les variables seront chargées dynamiquement -->
                </div>
                <small class="text-muted">💡 Cliquez sur une variable pour l'insérer à la position du curseur</small>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.ckeditor.com/ckeditor5/40.0.0/classic/ckeditor.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser CKEditor
    ClassicEditor
        .create(document.querySelector('#editor'), {
            toolbar: {
                items: [
                    'heading', '|',
                    'bold', 'italic', 'underline', 'strikethrough', '|',
                    'bulletedList', 'numberedList', '|',
                    'outdent', 'indent', '|',
                    'undo', 'redo', '|',
                    'link', 'blockQuote', 'insertTable', 'mediaEmbed'
                ]
            },
            language: 'fr',
        })
        .then(editor => {
            window.editor = editor;
        })
        .catch(error => {
            console.error(error);
        });

    // Charger les variables selon le type
    function loadVariables(type) {
        const container = document.getElementById('variables-container');
        
        const variables = {
            'fiche_inscription': [
                '%NOM%', '%PRENOM%', '%MATRICULE%', '%SEXE%',
                '%NAISSANCE%', '%LIEU_NAISSANCE%', '%NATIONALITE%',
                '%CLASSE%', '%ANNEE%', '%DATE_INSCRIPTION%',
                '%PARENT_NOM%', '%PARENT_TELEPHONE%', '%PARENT_EMAIL%', '%ADRESSE%',
                '%ECOLE%', '%VILLE%', '%DIRECTEUR%', '%DATE_FR%'
            ],
            'certificat_scolaire': [
                '%NOM%', '%PRENOM%', '%NAISSANCE%', '%LIEU_NAISSANCE%',
                '%CLASSE%', '%ANNEE%', '%ECOLE%', '%VILLE%',
                '%DIRECTEUR%', '%DATE_FR%'
            ],
            'bulletin': [
                '%NOM%', '%PRENOM%', '%MATRICULE%', '%CLASSE%',
                '%MOYENNE%', '%RANG%', '%EFFECTIF%', '%MENTION%',
                '%ANNEE%', '%MOIS%', '%ECOLE%', '%DATE_FR%'
            ],
            'parchemin': [
                '%NOM%', '%PRENOM%', '%MATRICULE%', '%CLASSE%',
                '%MOYENNE%', '%RANG%', '%EFFECTIF%', '%MENTION%',
                '%ANNEE%', '%ECOLE%', '%VILLE%', '%DATE_FR%'
            ]
        };

        let html = '<ul class="list-group">';
        const vars = variables[type] || variables['fiche_inscription'];
        vars.forEach(v => {
            html += `<li class="list-group-item variable-item" data-variable="${v}" style="cursor:pointer;">${v}</li>`;
        });
        html += '</ul>';
        container.innerHTML = html;

        document.querySelectorAll('.variable-item').forEach(item => {
            item.addEventListener('click', function() {
                const variable = this.dataset.variable;
                if (window.editor) {
                    window.editor.model.change(writer => {
                        writer.insertText(variable, window.editor.model.document.selection.getFirstPosition());
                    });
                }
            });
        });
    }

    const typeSelect = document.getElementById('type');
    loadVariables(typeSelect.value);

    typeSelect.addEventListener('change', function() {
        loadVariables(this.value);
    });
});
</script>
@endpush