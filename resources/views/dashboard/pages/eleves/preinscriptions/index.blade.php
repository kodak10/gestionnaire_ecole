@extends('dashboard.layouts.master')

@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
    <div class="my-auto mb-2">
        <h3 class="page-title mb-1">Gestion des Préinscriptions</h3>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Tableau de bord</a></li>
                <li class="breadcrumb-item active" aria-current="page">Préinscriptions</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex my-xl-auto right-content align-items-center flex-wrap">
        <div class="pe-1 mb-2">
            <a href="{{ route('preinscriptions.create') }}" class="btn btn-primary">
                <i class="ti ti-plus me-2"></i>Nouvelle préinscription
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
                        <th>Nom Complet</th>
                        <th>Date Naissance</th>
                        <th>Classe Demandée</th>
                        <th>Parent</th>
                        <th>Date Préinscription</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($preinscriptions as $preinscription)
                    <tr>
                        <td>
                            <strong>{{ $preinscription->nom }} {{ $preinscription->prenom }}</strong>
                            <div class="small text-muted">{{ $preinscription->sexe }}</div>
                        </td>
                        <td>{{ $preinscription->date_naissance->format('d/m/Y') }}</td>
                        <td>{{ $preinscription->classe_demandee }}</td>
                        <td>
                            {{ $preinscription->nom_parent }}
                            <div class="small">{{ $preinscription->telephone_parent }}</div>
                        </td>
                        <td>{{ $preinscription->date_preinscription->format('d/m/Y') }}</td>
                        <td>
                            <span class="badge bg-{{ $preinscription->statut_color }}">
                                {{ ucfirst($preinscription->statut) }}
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="actions">
                                <a href="{{ route('preinscriptions.show', $preinscription->id) }}" class="btn btn-sm bg-info-light me-2">
                                    <i class="ti ti-eye"></i>
                                </a>
                                <a href="{{ route('preinscriptions.edit', $preinscription->id) }}" class="btn btn-sm bg-primary-light me-2">
                                    <i class="ti ti-edit"></i>
                                </a>
                                @if($preinscription->statut == 'en_attente')
                                    <form action="{{ route('preinscriptions.valider', $preinscription->id) }}" method="POST" style="display: inline-block;">
                                        @csrf
                                        <button type="submit" class="btn btn-sm bg-success-light me-2" title="Valider">
                                            <i class="ti ti-check"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('preinscriptions.refuser', $preinscription->id) }}" method="POST" style="display: inline-block;">
                                        @csrf
                                        <button type="submit" class="btn btn-sm bg-danger-light me-2" title="Refuser">
                                            <i class="ti ti-x"></i>
                                        </button>
                                    </form>
                                @endif
                                <form action="{{ route('preinscriptions.destroy', $preinscription->id) }}" method="POST" style="display: inline-block;">
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
    {{ $preinscriptions->links() }}
</div>
@endsection