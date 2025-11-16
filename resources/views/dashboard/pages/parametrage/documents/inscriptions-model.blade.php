@extends('dashboard.layouts.master')

@section('content')
<div class="container mt-4" style="display:flex; gap:20px;">
    <!-- Éditeur -->
    <div style="flex:3; background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.1);">
        <h2 class="mb-3">Éditeur de document</h2>
        <form action="{{ route('documents.inscriptions.save') }}" method="POST">
            @csrf
            <input type="hidden" name="document_id" value="{{ $document->id ?? '' }}">
            <textarea name="content" id="editor" style="min-height:400px;">{{ old('content', $document->content ?? '') }}</textarea>
            <button type="submit" class="btn btn-primary mt-3">Enregistrer</button>
        </form>
    </div>

    <!-- Liste des variables -->
    <div style="flex:1; background:#f8f9fa; padding:20px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.05);">
        <h4 class="mb-3">Variables disponibles</h4>
        <ul id="variables-list" style="list-style:none; padding:0; margin:0;">
            @php
                $variables = ['%NOM%', '%PRENOM%', '%CLASSE%', '%ANNEE%', '%DATE_INSCRIPTION%'];
            @endphp
            @foreach($variables as $var)
                <li data-variable="{{ $var }}" 
                    style="cursor:pointer; padding:8px 12px; margin-bottom:5px; background:#fff; border-radius:4px; border:1px solid #ddd; transition:0.2s;">
                    {{ $var }}
                </li>
            @endforeach
        </ul>
        <small class="text-muted">Double-cliquez sur une variable pour l'insérer.</small>
    </div>
</div>

<!-- CKEditor 5 CDN -->
<script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>

<script>
let editorInstance;

ClassicEditor
    .create(document.querySelector('#editor'), {
        toolbar: [
            'heading', '|', 'bold', 'italic', 'underline', 'link',
            'bulletedList', 'numberedList', 'blockQuote', 'undo', 'redo',
            'insertImage'
        ],
        simpleUpload: {
            uploadUrl: '/documents/upload-image',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        }
    })
    .then(editor => {
        editorInstance = editor;
    })
    .catch(error => console.error(error));

// Hover effect et double-clic pour insérer les variables
document.querySelectorAll('#variables-list li').forEach(li => {
    li.addEventListener('mouseover', () => li.style.background = '#e9ecef');
    li.addEventListener('mouseout', () => li.style.background = '#fff');

    li.addEventListener('dblclick', function() {
        const variable = this.getAttribute('data-variable');
        if(editorInstance) {
            editorInstance.model.change(writer => {
                const selection = editorInstance.model.document.selection;
                const position = selection.getFirstPosition();

                // Insérer la variable
                writer.insertText(variable, position);
                
                // Déplacer le curseur après l’insertion
                writer.setSelection(position.getShiftedBy(variable.length));
            });
            editorInstance.editing.view.focus(); // remet le focus sur l'éditeur
        }
    });
});
</script>
@endsection
