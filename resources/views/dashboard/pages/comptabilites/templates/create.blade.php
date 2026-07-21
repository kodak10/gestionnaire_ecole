@extends('dashboard.layouts.master')

@section('content')
<div class="container mt-4" style="display:flex; gap:20px;">
    <div style="flex:3; background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.1);">
        <h2 class="mb-3">Éditeur de modèle SMS</h2>
        <form action="{{ route('templates.store') }}" method="POST" id="template-form">
            @csrf
            <input type="hidden" name="document_id" value="{{ $document->id ?? '' }}">
            
            <div class="form-group">
                <label for="nom">Nom du modèle</label>
                <input type="text" class="form-control" id="nom" name="nom" placeholder="Nom du modèle" required>
            </div>
            
            <div class="form-group">
                <label for="type">Type de document</label>
                <select class="form-control" id="type" name="type" required>
                    <option value="recu_paiement" {{ $type == 'recu_paiement' ? 'selected' : '' }}>Reçu de paiement</option>
                    <option value="relance" {{ $type == 'relance' ? 'selected' : '' }}>Relance</option>
                    <option value="information" {{ $type == 'information' ? 'selected' : '' }}>Information</option>
                    <option value="bulletin">Bulletin</option>
                    <option value="autre">Autre</option>
                </select>
            </div>
            
           <div class="form-group">
    <label for="editor">Contenu du message <span class="text-muted">(Texte brut - Les retours à la ligne sont conservés)</span></label>
    <textarea name="content" id="editor" 
        style="min-height:400px; width:100%; font-family:'Courier New', monospace; font-size:14px; padding:10px; border:1px solid #ddd; border-radius:4px; resize:vertical; background:#fafafa; line-height:1.8;"
        placeholder="Saisissez votre message ici...&#10;Utilisez les variables pour personnaliser le SMS.">Relance %TYPE_FRAIS% %MOIS%: Parent %NOM_RESPONSABLE%, élève %NOM% %PRENOM% %CLASSE%. Attendu:%MONTANT_ATTENDU% Payé:%MONTANT_PAYE% Reste:%RESTE_MOIS% Total:%RESTE_TOTAL% FCFA. Réglez avant %DATE_ECHEANCE%.</textarea>
</div>
            
            <div style="margin-top: 15px; display: flex; gap: 10px; flex-wrap: wrap;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
                <button type="button" class="btn btn-success" id="preview-btn">
                    <i class="fas fa-eye"></i> Visualiser en SMS
                </button>
                <button type="button" class="btn btn-info" id="clear-btn">
                    <i class="fas fa-eraser"></i> Effacer
                </button>
            </div>
        </form>
    </div>

    <div style="flex:1; background:#f8f9fa; padding:20px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.05);">
        <h4 class="mb-3">Variables disponibles</h4>
        
        @if($type == 'recu_paiement')
            <div style="background:#e7f3ff; padding:8px 12px; border-radius:4px; margin-bottom:15px; font-size:12px;">
                <strong>📋 Reçu de paiement</strong>
            </div>
        @elseif($type == 'relance')
            <div style="background:#fff3cd; padding:8px 12px; border-radius:4px; margin-bottom:15px; font-size:12px;">
                <strong>📋 Relance de paiement</strong>
            </div>
        @elseif($type == 'information')
            <div style="background:#d4edda; padding:8px 12px; border-radius:4px; margin-bottom:15px; font-size:12px;">
                <strong>📋 Message d'information</strong>
            </div>
        @else
            <div style="background:#e9ecef; padding:8px 12px; border-radius:4px; margin-bottom:15px; font-size:12px;">
                <strong>📋 Variables générales</strong>
            </div>
        @endif
        
        <ul id="variables-list" style="list-style:none; padding:0; margin:0;">
            @php
                if($type == 'recu_paiement') {
                    $variables = [
                        '%NOM%' => 'Nom de l\'élève',
                        '%PRENOM%' => 'Prénom de l\'élève',
                        '%MATRICULE%' => 'Matricule',
                        '%CLASSE%' => 'Classe',
                        '%NUMERO_RECU%' => 'Numéro du reçu',
                        '%MONTANT%' => 'Montant payé',
                        '%MONTANT_LETTRES%' => 'Montant en lettres',
                        '%RESTE%' => 'Reste à payer',
                        '%TOTAL%' => 'Total à payer',
                        '%TYPE_FRAIS%' => 'Type de frais',
                        '%MODE_PAIEMENT%' => 'Mode de paiement',
                        '%REFERENCE%' => 'Référence du paiement',
                        '%MENSUALITE%' => 'Mensualité',
                        '%MOIS%' => 'Mois de paiement',
                        '%ECOLE%' => 'Nom de l\'école',
                        '%ECOLE_ADRESSE%' => 'Adresse de l\'école',
                        '%ECOLE_TELEPHONE%' => 'Téléphone de l\'école',
                        '%ECOLE_EMAIL%' => 'Email de l\'école',
                        '%DATE%' => 'Date du jour',
                        '%DATE_FR%' => 'Date en français',
                        '%ANNEE%' => 'Année scolaire',
                    ];
                }
                elseif($type == 'relance') {
                    $variables = [
                        '%NOM%' => 'Nom de l\'élève',
                        '%PRENOM%' => 'Prénom de l\'élève',
                        '%MATRICULE%' => 'Matricule',
                        '%CLASSE%' => 'Classe',
                        '%TYPE_FRAIS%' => 'Type de frais',
                        '%MOIS_CONCERNE%' => 'Mois concerné',
                        '%NOM_RESPONSABLE%' => 'Nom du responsable légal',
                        '%PRENOM_RESPONSABLE%' => 'Prénom du responsable légal',
                        '%MONTANT_ATTENDU%' => 'Montant attendu pour le mois',
                        '%MONTANT_PAYE%' => 'Montant déjà payé',
                        '%RESTE_MOIS%' => 'Reste à payer sur le mois',
                        '%RESTE_TOTAL%' => 'Reste total',
                        '%DATE_ECHEANCE%' => 'Date d\'échéance',
                        '%ECOLE%' => 'Nom de l\'école',
                        '%ECOLE_ADRESSE%' => 'Adresse de l\'école',
                        '%ECOLE_TELEPHONE%' => 'Téléphone de l\'école',
                        '%ECOLE_EMAIL%' => 'Email de l\'école',
                        '%DATE%' => 'Date du jour',
                        '%DATE_FR%' => 'Date en français',
                        '%ANNEE%' => 'Année scolaire',
                    ];
                }
                elseif($type == 'information') {
                    $variables = [
                        '%NOM%' => 'Nom de l\'élève',
                        '%PRENOM%' => 'Prénom de l\'élève',
                        '%CLASSE%' => 'Classe',
                        '%EVENEMENT%' => 'Événement',
                        '%DATE_EVENEMENT%' => 'Date de l\'événement',
                        '%LIEU%' => 'Lieu de l\'événement',
                        '%HEURE%' => 'Heure de l\'événement',
                        '%OBJET%' => 'Objet du message',
                        '%DETAIL%' => 'Détails supplémentaires',
                        '%PIECE_JOINTE%' => 'Pièce jointe',
                        '%DATE_DEBUT%' => 'Date de début',
                        '%DATE_FIN%' => 'Date de fin',
                        '%NOM_RESPONSABLE%' => 'Nom du responsable légal',
                        '%PRENOM_RESPONSABLE%' => 'Prénom du responsable légal',
                        '%CONTACT_RESPONSABLE%' => 'Téléphone du responsable',
                        '%ECOLE%' => 'Nom de l\'école',
                        '%ECOLE_ADRESSE%' => 'Adresse de l\'école',
                        '%ECOLE_TELEPHONE%' => 'Téléphone de l\'école',
                        '%ECOLE_EMAIL%' => 'Email de l\'école',
                        '%DATE%' => 'Date du jour',
                        '%DATE_FR%' => 'Date en français',
                        '%ANNEE%' => 'Année scolaire',
                    ];
                }
                else {
                    $variables = [
                        '%NOM%' => 'Nom de l\'élève',
                        '%PRENOM%' => 'Prénom de l\'élève',
                        '%CLASSE%' => 'Classe',
                        '%ECOLE%' => 'Nom de l\'école',
                        '%ECOLE_ADRESSE%' => 'Adresse de l\'école',
                        '%ECOLE_TELEPHONE%' => 'Téléphone',
                        '%DATE%' => 'Date du jour',
                        '%ANNEE%' => 'Année scolaire',
                    ];
                }
            @endphp
            
            @foreach($variables as $var => $label)
                <li data-variable="{{ $var }}" 
                    style="cursor:pointer; padding:8px 12px; margin-bottom:5px; background:#fff; border-radius:4px; border:1px solid #ddd; transition:0.2s; display:flex; justify-content:space-between; align-items:center;">
                    <span>
                        <code style="background:#f8f9fa; padding:2px 8px; border-radius:3px; font-weight:bold; font-size:13px; pointer-events:none;">
                            {{ $var }}
                        </code>
                    </span>
                    <span style="font-size:11px; color:#6c757d;">
                        {{ $label }}
                    </span>
                </li>
            @endforeach
        </ul>
        <small class="text-muted" style="display:block; margin-top:10px;">
            💡 Cliquez sur une variable pour l'insérer à la position du curseur
        </small>
    </div>
</div>

<!-- Modal de visualisation SMS -->
<div class="modal fade" id="previewModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-sms"></i> Aperçu du message SMS
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div style="background: #e8e8e8; border-radius: 15px; padding: 25px; max-width: 450px; margin: 0 auto;">
                    <div style="background: #f5f5f5; border-radius: 10px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; padding-bottom:10px; border-bottom:1px solid #ddd;">
                            <span style="font-size:13px; color:#666;">
                                <i class="fas fa-phone"></i> +225 07 00 00 00 00
                            </span>
                            <span style="font-size:13px; color:#666;">
                                <i class="far fa-clock"></i> Aujourd'hui
                            </span>
                        </div>
                        <div id="preview-content" 
                            style="white-space: pre-wrap; word-wrap: break-word; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 15px; line-height: 1.8; min-height: 150px; background: white; padding: 15px; border-radius: 8px; color: #1a1a1a;">
                            ...
                        </div>
                        <div style="margin-top:15px; padding-top:10px; border-top:1px solid #ddd; display:flex; justify-content:space-between; align-items:center;">
                            <span style="font-size:12px; color:#999;" id="char-count">0 caractères</span>
                            <span style="font-size:12px; color:#999;">
                                <i class="fas fa-sms"></i> <span id="sms-count">1</span> SMS
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" id="copy-preview">
                    <i class="fas fa-copy"></i> Copier le message
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const textarea = document.getElementById('editor');
    const variablesList = document.querySelectorAll('#variables-list li');
    let processing = false;

    function insertVariable(variable) {
        if (processing || !textarea) return;
        processing = true;

        const startPos = textarea.selectionStart;
        const endPos = textarea.selectionEnd;
        const text = textarea.value;
        
        const beforeText = text.substring(0, startPos);
        const afterText = text.substring(endPos, text.length);
        let newText = beforeText + variable + afterText;
        
        textarea.value = newText;
        
        const newCursorPos = startPos + variable.length;
        textarea.selectionStart = newCursorPos;
        textarea.selectionEnd = newCursorPos;
        textarea.focus();
        
        const li = document.querySelector(`#variables-list li[data-variable="${variable}"]`);
        if (li) {
            li.style.background = '#d4edda';
            li.style.borderColor = '#28a745';
            setTimeout(() => {
                li.style.background = '#fff';
                li.style.borderColor = '#ddd';
            }, 400);
        }

        setTimeout(() => {
            processing = false;
        }, 200);
    }

    variablesList.forEach(li => {
        const variable = li.getAttribute('data-variable');
        
        li.removeEventListener('click', li._clickHandler);
        
        const clickHandler = function(e) {
            e.stopPropagation();
            e.preventDefault();
            insertVariable(variable);
        };
        
        li._clickHandler = clickHandler;
        li.addEventListener('click', clickHandler);
        
        li.addEventListener('mouseover', () => {
            li.style.background = '#e9ecef';
            li.style.borderColor = '#007bff';
            li.style.transform = 'translateX(5px)';
        });
        
        li.addEventListener('mouseout', () => {
            li.style.background = '#fff';
            li.style.borderColor = '#ddd';
            li.style.transform = 'translateX(0)';
        });
    });

    document.getElementById('type').addEventListener('change', function() {
        const type = this.value;
        window.location.href = '{{ route("templates.create") }}?type=' + type;
    });

    document.getElementById('clear-btn').addEventListener('click', function() {
        if (confirm('Voulez-vous vraiment effacer tout le contenu ?')) {
            textarea.value = '';
            textarea.focus();
            toastr.info('Contenu effacé');
        }
    });

    document.getElementById('template-form').addEventListener('submit', function(e) {
        const content = textarea.value;
        if (!content.trim()) {
            e.preventDefault();
            toastr.error('Le contenu du modèle ne peut pas être vide.');
        }
    });

    document.getElementById('preview-btn').addEventListener('click', function() {
        let content = textarea.value;
        
        if (!content.trim()) {
            toastr.error('Le contenu du modèle est vide.');
            return;
        }

        const testData = {
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
            'ECOLE': 'École Saint Joseph',
            'ECOLE_ADRESSE': '123 Rue de l\'École, Abidjan',
            'ECOLE_TELEPHONE': '+225 07 00 00 00 00',
            'ECOLE_EMAIL': 'contact@ecole.ci',
            'DATE': '{{ date("d/m/Y") }}',
            'DATE_FR': '{{ date("d/m/Y") }}',
            'ANNEE': '2024-2025',
            'MONTANT_DU': '25 000',
            'MONTANT_DU_LETTRES': 'Vingt-cinq mille',
            'DATE_ECHEANCE': '15/01/2026',
            'RETARD': '5',
            'MOIS_CONCERNE': 'Janvier',
            'NOMBRE_RELANCE': '1',
            'DELAI': '48 heures',
            'SANCTION': 'Exclusion temporaire',
            'NOM_RESPONSABLE': 'KOUASSI',
            'PRENOM_RESPONSABLE': 'Paul',
            'CONTACT_RESPONSABLE': '07 00 00 00 00',
            'MONTANT_ATTENDU': '25 000',
            'MONTANT_PAYE': '10 000',
            'RESTE_MOIS': '15 000',
            'RESTE_TOTAL': '45 000',
            'EVENEMENT': 'Réunion de parents',
            'DATE_EVENEMENT': '{{ date("d/m/Y", strtotime("+1 week")) }}',
            'LIEU': 'Salle polyvalente',
            'HEURE': '15h00',
            'OBJET': 'Information importante',
            'DETAIL': 'Nous vous informons de la tenue d\'une réunion importante.',
            'PIECE_JOINTE': 'Aucune',
            'DATE_DEBUT': '{{ date("d/m/Y") }}',
            'DATE_FIN': '{{ date("d/m/Y", strtotime("+1 month")) }}'
        };

        let previewContent = content;
        for (const [key, value] of Object.entries(testData)) {
            const regex = new RegExp('%' + key + '%', 'g');
            previewContent = previewContent.replace(regex, value);
        }

        previewContent = previewContent.replace(/%[^%]+%/g, '');

        document.getElementById('preview-content').textContent = previewContent;
        
        const charCount = previewContent.length;
        document.getElementById('char-count').textContent = charCount + ' caractères';
        
        const smsCount = Math.ceil(charCount / 160);
        document.getElementById('sms-count').textContent = smsCount;
        
        $('#previewModal').modal('show');
    });

    document.getElementById('copy-preview').addEventListener('click', function() {
        const content = document.getElementById('preview-content').textContent;
        navigator.clipboard.writeText(content).then(() => {
            toastr.success('Message copié !');
        }).catch(() => {
            const textarea = document.createElement('textarea');
            textarea.value = content;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            toastr.success('Message copié !');
        });
    });
});
</script>

<style>
#variables-list li {
    cursor: pointer;
    user-select: none;
    transition: all 0.2s ease;
}

#variables-list li:hover {
    background: #e9ecef !important;
    border-color: #007bff !important;
    transform: translateX(5px);
}

#variables-list li:active {
    transform: scale(0.98);
}

#variables-list li code {
    background: #f8f9fa;
    padding: 2px 8px;
    border-radius: 3px;
    font-weight: bold;
    font-size: 13px;
    pointer-events: none;
}

#editor {
    font-family: 'Courier New', monospace;
    font-size: 14px;
    line-height: 1.8;
    resize: vertical;
    tab-size: 4;
}

#editor::placeholder {
    color: #999;
    font-style: italic;
}

#preview-content {
    white-space: pre-wrap;
    word-wrap: break-word;
    line-height: 1.8;
}
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

<script>
toastr.options = {
    closeButton: true,
    progressBar: true,
    positionClass: "toast-top-right",
    timeOut: "3000"
};
</script>

@endsection