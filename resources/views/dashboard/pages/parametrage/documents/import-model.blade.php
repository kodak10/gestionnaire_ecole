@extends('dashboard.layouts.master')

@section('content')
<div class="container mt-4">
    <h2>Importer un modèle Word</h2>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form action="{{ route('documents.inscriptions.import.save') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="mb-3">
            <label for="model" class="form-label">Choisir un fichier Word (.docx)</label>
            <input type="file" name="model" id="model" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Importer</button>
    </form>
</div>
@endsection
