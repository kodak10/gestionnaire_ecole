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
            <a href="" class="btn btn-outline-primary">
                <i class="ti ti-file-spreadsheet me-2"></i>Imprimer Bulletin
            </a>
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
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($notes as $note)
                    <tr>
                        <td>{{ $note->inscription->eleve->nom_complet }}</td> {{-- Modification ici --}}
                        <td>{{ $note->matiere->nom }}</td>
                        <td>{{ $note->classe->nom }}</td>
                        <td>
                            <span class="fw-bold {{ $note->valeur < 10 ? 'text-danger' : 'text-success' }}">
                                {{ number_format($note->valeur, 2) }}
                            </span>
                        </td>
                        <td>{{ $note->coefficient }}</td>
                        <td>{{ $note->mois->nom }}</td>
                        <td class="text-end">
                            <div class="actions">
                                <a href="{{ route('notes.show', $note->id) }}" class="btn btn-sm bg-info-light me-2">
                                    <i class="ti ti-eye"></i>
                                </a>
                                <a href="{{ route('notes.edit', $note->id) }}" class="btn btn-sm bg-primary-light me-2">
                                    <i class="ti ti-edit"></i>
                                </a>
                                <form action="{{ route('notes.destroy', $note->id) }}" method="POST" style="display: inline-block;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm bg-danger-light" onclick="return confirm('Êtes-vous sûr ?')">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
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
@endsection