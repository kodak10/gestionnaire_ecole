@extends('dashboard.layouts.master')

@section('content')
<div class="container mt-4" style="display:flex; gap:20px;">
    <div style="flex:3; background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.1);">
        <h2 class="mb-3">📄 Créer un modèle de document</h2>
        <form action="{{ route('documents.templates.store') }}" method="POST" id="template-form">
            @csrf
            
            <div class="form-group">
                <label for="type">Type de document <span class="text-danger">*</span></label>
                <select class="form-control" id="type" name="type" required>
                    <option value="">Sélectionner un type</option>
                    <option value="certificat_scolaire" {{ $type == 'certificat_scolaire' ? 'selected' : '' }}>Certificat de Scolarité</option>
                    <option value="attestation_frequentation" {{ $type == 'attestation_frequentation' ? 'selected' : '' }}>Attestation de Fréquentation</option>
                    <option value="fiche_inscription" {{ $type == 'fiche_inscription' ? 'selected' : '' }}>Fiche d'Inscription</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="editor">Contenu du modèle <span class="text-muted">(HTML - Utilisez l'éditeur ci-dessous)</span></label>
                <textarea name="content" id="editor" 
                    style="min-height:400px; width:100%; padding:10px; border:1px solid #ddd; border-radius:4px; resize:vertical; background:#fafafa;"
                    placeholder="Saisissez votre contenu ici...">{{ old('content', $document->content ?? '') }}</textarea>
            </div>
            
            <div style="margin-top: 15px; display: flex; gap: 10px; flex-wrap: wrap;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
                <button type="button" class="btn btn-success" id="preview-btn">
                    <i class="fas fa-eye"></i> Visualiser le document
                </button>
                <button type="button" class="btn btn-info" id="clear-btn">
                    <i class="fas fa-eraser"></i> Effacer
                </button>
                <a href="{{ route('documents.templates.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
            </div>
        </form>
    </div>

    <div style="flex:1; background:#f8f9fa; padding:20px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.05);">
        <h4 class="mb-3">📋 Variables disponibles</h4>
        
        @if($type == 'certificat_scolaire')
            <div style="background:#e7f3ff; padding:8px 12px; border-radius:4px; margin-bottom:15px; font-size:12px;">
                <strong>📋 Certificat de Nationalité</strong>
            </div>
        @elseif($type == 'attestation_frequentation')
            <div style="background:#fff3cd; padding:8px 12px; border-radius:4px; margin-bottom:15px; font-size:12px;">
                <strong>📋 Attestation de Fréquentation</strong>
            </div>
        @else
            <div style="background:#d4edda; padding:8px 12px; border-radius:4px; margin-bottom:15px; font-size:12px;">
                <strong>📋 Fiche d'Inscription</strong>
            </div>
        @endif
        
        <ul id="variables-list" style="list-style:none; padding:0; margin:0;">
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

<!-- Modal de visualisation -->
<div class="modal fade" id="previewModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-eye"></i> Aperçu du document
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div style="background: #f8f9fa; border-radius: 10px; padding: 25px;">
                    <div style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                        <div id="preview-content" 
                            style="font-family: 'Georgia', Times, serif; font-size: 14px; line-height: 1.8; min-height: 200px; padding: 15px; color: #1a1a1a;">
                            ...
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" id="copy-preview">
                    <i class="fas fa-copy"></i> Copier le contenu
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// =========================================================
// UNIQUE SCRIPT - PROTECTION CONTRE DOUBLE EXECUTION
// =========================================================
(function() {
    'use strict';

    // PROTECTION ULTRA ROBUSTE
    if (window.__TEMPLATE_DOCUMENT_LOADED === true) {
        console.warn('⚠️ Script déjà exécuté, abandon.');
        return;
    }
    window.__TEMPLATE_DOCUMENT_LOADED = true;

    console.log('🔵 [DEBUG] Démarrage du script...');

    // =========================================================
    // INITIALISATION DE CKEDITOR
    // =========================================================
    function initEditor() {
        // Vérifier si CKEditor est déjà chargé
        if (typeof ClassicEditor === 'undefined') {
            console.log('🟢 [DEBUG] Chargement de CKEditor...');
            var script = document.createElement('script');
            script.src = 'https://cdn.ckeditor.com/ckeditor5/40.0.0/classic/ckeditor.js';
            script.onload = function() {
                console.log('✅ [DEBUG] CKEditor chargé');
                createEditor();
            };
            script.onerror = function() {
                console.error('❌ [DEBUG] Erreur chargement CKEditor');
            };
            document.head.appendChild(script);
        } else {
            createEditor();
        }
    }

    function createEditor() {
        // Vérifier si déjà initialisé
        if (window.__CKEDITOR_CREATED === true) {
            console.warn('⚠️ CKEditor déjà créé');
            return;
        }

        var editorEl = document.getElementById('editor');
        if (!editorEl) {
            console.error('❌ #editor introuvable');
            return;
        }

        // Vérifier si CKEditor est déjà attaché
        if (editorEl.classList.contains('ck-editor__editable')) {
            console.warn('⚠️ CKEditor déjà attaché');
            return;
        }

        // Vérifier si un éditeur existe dans le DOM
        if (document.querySelector('.ck-editor')) {
            console.warn('⚠️ Un éditeur existe déjà dans le DOM');
            return;
        }

        console.log('🟢 [DEBUG] Création de CKEditor...');
        window.__CKEDITOR_CREATED = true;

        // TOOLBAR SANS underline ET strikethrough
        ClassicEditor
            .create(editorEl, {
                toolbar: {
                    items: [
                        'heading', '|',
                        'bold', 'italic', '|',
                        'bulletedList', 'numberedList', '|',
                        'outdent', 'indent', '|',
                        'undo', 'redo', '|',
                        'link', 'blockQuote', 'insertTable'
                    ]
                },
                language: 'fr'
            })
            .then(function(editor) {
                window.__EDITOR_INSTANCE = editor;
                console.log('✅ [DEBUG] CKEditor créé UNE SEULE FOIS !');
                initVariables(editor);
                initButtons(editor);
            })
            .catch(function(error) {
                console.error('❌ [DEBUG] Erreur :', error);
                window.__CKEDITOR_CREATED = false;
            });
    }

    // =========================================================
    // VARIABLES
    // =========================================================
    function initVariables(editor) {
        var list = document.getElementById('variables-list');
        if (!list) return;
        if (list.dataset.inited === '1') return;
        list.dataset.inited = '1';

        var items = list.querySelectorAll('li');
        console.log('🔵 [DEBUG] Variables :', items.length);

        items.forEach(function(li) {
            var varName = li.getAttribute('data-variable');
            if (!varName) return;

            li.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('🟢 [DEBUG] Click :', varName);
                insertVar(editor, varName);
            });

            li.addEventListener('mouseenter', function() {
                this.style.background = '#e9ecef';
                this.style.borderColor = '#007bff';
                this.style.transform = 'translateX(5px)';
            });

            li.addEventListener('mouseleave', function() {
                this.style.background = '#fff';
                this.style.borderColor = '#ddd';
                this.style.transform = 'translateX(0)';
            });
        });
    }

    function insertVar(editor, varName) {
        if (!editor) return;

        try {
            editor.model.change(function(writer) {
                var pos = editor.model.document.selection.getFirstPosition();
                writer.insertText(varName, pos);
            });

            // Effet visuel
            document.querySelectorAll('#variables-list li').forEach(function(li) {
                if (li.getAttribute('data-variable') === varName) {
                    li.style.background = '#d4edda';
                    li.style.borderColor = '#28a745';
                    setTimeout(function() {
                        li.style.background = '#fff';
                        li.style.borderColor = '#ddd';
                    }, 400);
                }
            });

        } catch (e) {
            console.error('❌ Erreur insertion :', e);
        }
    }

    // =========================================================
    // BOUTONS
    // =========================================================
    function initButtons(editor) {
        // Type
        var typeSelect = document.getElementById('type');
        if (typeSelect) {
            typeSelect.addEventListener('change', function() {
                if (this.value) {
                    window.location.href = '{{ route("documents.templates.create") }}?type=' + encodeURIComponent(this.value);
                }
            });
        }

        // Effacer
        document.getElementById('clear-btn').addEventListener('click', function() {
            if (confirm('Effacer tout le contenu ?')) {
                editor.setData('');
                if (typeof toastr !== 'undefined') toastr.info('Contenu effacé');
            }
        });

        // Submit
        document.getElementById('template-form').addEventListener('submit', function(e) {
            if (!editor.getData().trim()) {
                e.preventDefault();
                if (typeof toastr !== 'undefined') toastr.error('Le contenu est vide');
            }
        });

        // Aperçu
        document.getElementById('preview-btn').addEventListener('click', function() {
            var content = editor.getData();
            if (!content.trim()) {
                if (typeof toastr !== 'undefined') toastr.error('Le contenu est vide');
                return;
            }

            var data = {
                'NOM': 'KOUASSI',
                'PRENOM': 'Jean',
                'MATRICULE': '2024-001',
                'SEXE': 'Masculin',
                'NAISSANCE': '05/08/2016',
                'LIEU_NAISSANCE': 'Bouaké',
                'NATIONALITE': 'Ivoirienne',
                'CLASSE': 'CM2 A',
                'ANNEE': '2025-2026',
                'DATE_INSCRIPTION': '{{ date("d/m/Y") }}',
                'DATE_EDITION': '{{ date("d/m/Y") }}',
                'PARENT_NOM': 'KOUASSI Paul',
                'PARENT_TELEPHONE': '+225 07 00 00 00 00',
                'PARENT_EMAIL': 'parent@email.com',
                'ADRESSE': '01 BP 1234 Abidjan',
                'ECOLE': 'École Saint Joseph',
                'VILLE': 'Korhogo',
                'DIRECTEUR': 'M. KONE',
                'DATE_FR': '{{ date("d/m/Y") }}',
                'NUMERO_CERTIFICAT': 'CN-2025-001',
                'MOYENNE_ANNUELLE': '15,50',
                'RANG': '1er',
                'EFFECTIF': '25',
                'MENTION': 'Très Bien'
            };

            var preview = content;
            for (var key in data) {
                preview = preview.replace(new RegExp('%' + key + '%', 'g'), data[key]);
            }
            preview = preview.replace(/%[^%]+%/g, '');

            var el = document.getElementById('preview-content');
            if (el) el.innerHTML = preview;

            if (typeof $ !== 'undefined') {
                $('#previewModal').modal('show');
            }
        });

        // Copier
        document.getElementById('copy-preview').addEventListener('click', function() {
            var content = document.getElementById('preview-content').innerHTML;
            navigator.clipboard.writeText(content).then(function() {
                if (typeof toastr !== 'undefined') toastr.success('Copié !');
            }).catch(function() {
                var ta = document.createElement('textarea');
                ta.value = content;
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
                if (typeof toastr !== 'undefined') toastr.success('Copié !');
            });
        });

        // Toastr
        if (typeof toastr !== 'undefined') {
            toastr.options = {
                closeButton: true,
                progressBar: true,
                positionClass: 'toast-top-right',
                timeOut: '3000'
            };
        }

        console.log('✅ [DEBUG] Initialisation terminée');
    }

    // =========================================================
    // DEMARRAGE
    // =========================================================
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initEditor);
    } else {
        initEditor();
    }

})();
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
#preview-content {
    white-space: pre-wrap;
    word-wrap: break-word;
    line-height: 1.8;
}
#editor {
    font-family: 'Courier New', monospace;
    font-size: 14px;
    line-height: 1.8;
    resize: vertical;
}
</style>
@endsection