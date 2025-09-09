@extends('dashboard.layouts.master')

@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
    <div class="my-auto mb-2">
        <h3 class="page-title mb-1">Gestion des Réinscriptions</h3>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Tableau de bord</a></li>
                <li class="breadcrumb-item active" aria-current="page">Réinscriptions</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex my-xl-auto right-content align-items-center flex-wrap">
       
        <div class="pe-1 mb-2">
            <a href="{{ route('reinscriptions.create') }}" class="btn btn-outline-primary">
                <i class="ti ti-users me-2"></i>Nouvelle Réinscription
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
                        <th>Classe</th>
                        <th>Année scolaire</th>
                        <th>Date</th>
                        <th>Montant</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reinscriptions as $reinscription)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <img src="{{ $reinscription->eleve->photo_url }}" 
                                     class="rounded-circle border border-2 me-2 {{ $reinscription->eleve->sexe === 'Masculin' ? 'border-danger' : 'border-primary' }}" 
                                     style="width: 40px; height: 40px; object-fit: cover;">
                                <div>
                                    <h6 class="mb-0">{{ $reinscription->eleve->nom_complet }}</h6>
                                    <small>{{ $reinscription->eleve->matricule }}</small>
                                </div>
                            </div>
                        </td>
                        <td>{{ $reinscription->classe->nom }}</td>
                        <td>{{ $reinscription->annee_scolaire }}</td>
                        <td></td>
                        <td>{{ number_format($reinscription->montant, 0, ',', ' ') }} FCFA</td>
                        <td>
                            @if($reinscription->statut == 'validée')
                                <span class="badge bg-success">{{ $reinscription->statut }}</span>
                            @elseif($reinscription->statut == 'refusée')
                                <span class="badge bg-danger">{{ $reinscription->statut }}</span>
                            @else
                                <span class="badge bg-warning">{{ $reinscription->statut }}</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="actions">
                                <a href="{{ route('reinscriptions.show', $reinscription->id) }}" class="btn btn-sm bg-info-light me-2">
                                    <i class="ti ti-eye"></i>
                                </a>
                                <a href="{{ route('reinscriptions.edit', $reinscription->id) }}" class="btn btn-sm bg-primary-light me-2">
                                    <i class="ti ti-edit"></i>
                                </a>
                                <form action="{{ route('reinscriptions.destroy', $reinscription->id) }}" method="POST" style="display: inline-block;">
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
    {{ $reinscriptions->links() }}
</div>
@endsection