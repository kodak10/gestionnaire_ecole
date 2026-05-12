@extends('dashboard.layouts.master')

@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
    <div class="my-auto mb-2">
        <h3 class="page-title mb-1">Gestion des Parchemins</h3>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Tableau de bord</a></li>
                <li class="breadcrumb-item active" aria-current="page">Parchemins</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="ti ti-certificate text-primary me-2"></i>
                    Générer un Parchemin de Fin d'Année
                </h5>
            </div>
            <div class="card-body">
                <form action="{{ route('parchemin.generer') }}" method="GET" target="_blank">
                    <div class="mb-3">
                        <label class="form-label">Classe <span class="text-danger">*</span></label>
                        <select name="classe_id" class="form-select" required>
                            <option value="">Sélectionner une classe</option>
                            @foreach($classes as $classe)
                                <option value="{{ $classe->id }}">{{ $classe->nom }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Type de document <span class="text-danger">*</span></label>
                        <select name="type_parchemin" class="form-select" required>
                            <option value="pdf">PDF (Aperçu)</option>
                            <option value="word">Word (Téléchargement)</option>
                        </select>
                    </div>

                    <div class="alert alert-info">
                        <i class="ti ti-info-circle me-2"></i>
                        Le parchemin sera généré pour tous les élèves de la classe avec leur mention respective.
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="ti ti-download me-2"></i>Générer les Parchemins
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection