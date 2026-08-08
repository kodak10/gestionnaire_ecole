@extends('dashboard.layouts.master')

@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
    <div class="my-auto mb-2">
        <h3 class="page-title mb-1">📄 Modèles de Documents</h3>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Tableau de bord</a></li>
                <li class="breadcrumb-item active" aria-current="page">Modèles de documents</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex my-xl-auto right-content align-items-center flex-wrap">
        <div class="pe-1 mb-2">
            <a href="{{ route('documents.templates.create') }}" class="btn btn-outline-primary">
                <i class="ti ti-plus me-2"></i>Nouveau modèle
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
                        <th>#</th>
                        <th>Type de document</th>
                        <th>Contenu</th>
                        <th>Créé le</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($templates as $template)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            <span class="badge bg-primary">
                                {{ $types[$template->type] ?? $template->type }}
                            </span>
                        </td>
                        <td>{{ Str::limit(strip_tags($template->content), 100) }}</td>
                        <td>{{ $template->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            <div class="d-flex">
                                <a href="{{ route('documents.templates.edit', $template->id) }}" 
                                   class="btn btn-sm btn-outline-primary me-2">
                                    <i class="ti ti-edit me-1"></i>Modifier
                                </a>
                                <form action="{{ route('documents.templates.destroy', $template->id) }}" 
                                      method="POST" 
                                      onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce modèle ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="ti ti-trash me-1"></i>Supprimer
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-4">
                            <i class="ti ti-file-empty fs-1 text-muted"></i>
                            <p class="text-muted mt-2">Aucun modèle de document trouvé</p>
                            <a href="{{ route('documents.templates.create') }}" class="btn btn-primary mt-2">
                                <i class="ti ti-plus me-2"></i>Créer un modèle
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection