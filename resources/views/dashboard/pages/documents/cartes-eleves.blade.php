@extends('dashboard.layouts.master')

@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
    <div class="my-auto mb-2">
        <h3 class="page-title mb-1">Cartes Élèves</h3>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Tableau de bord</a></li>
                <li class="breadcrumb-item active" aria-current="page">Cartes Élèves</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Sélection de la classe -->
<div class="bg-white p-3 border rounded-1 mb-4">
    <h4 class="mb-3">Sélectionner une classe</h4>
    <form method="GET" action="{{ route('documents.cartes-eleves') }}" class="d-flex align-items-end flex-wrap gap-2">
        <div style="min-width: 250px;">
            <label class="form-label">Classe</label>
            <select class="form-select" name="classe_id" required>
                <option value="">-- Choisir une classe --</option>
                @foreach($classes as $classe)
                    <option value="{{ $classe->id }}" {{ request('classe_id') == $classe->id ? 'selected' : '' }}>
                        {{ $classe->nom }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <button type="submit" class="btn btn-primary">
                <i class="ti ti-search me-1"></i>Afficher les élèves
            </button>
        </div>
    </form>
</div>

@if($classeSelectionnee)
<div class="card">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h4 class="mb-0">
                Élèves de la classe <span class="text-primary">{{ $classeSelectionnee->nom }}</span>
                <span class="badge bg-light text-dark ms-2">{{ $eleves->count() }} élève(s)</span>
            </h4>

            @if($eleves->count() > 0)
                <a href="{{ route('documents.generer-cartes-classe', $classeSelectionnee->id) }}"
                   class="btn btn-success" target="_blank">
                    <i class="ti ti-id-badge-2 me-1"></i>Générer toutes les cartes ({{ $eleves->count() }})
                </a>
            @endif
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-center mb-0">
                <thead>
                    <tr>
                        <th>Matricule</th>
                        <th>Élève</th>
                        <th>Date Nais.</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($eleves as $eleve)
                    <tr>
                        <td>
                            <span class="fw-bold text-primary">{{ $eleve->code_national ?? $eleve->matricule ?? '-' }}</span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    @php
                                        $photoPath = $eleve->photo_path ?? null;
                                        $photoExists = $photoPath && file_exists(storage_path('app/public/' . $photoPath));
                                        $photoUrl = $photoExists ? asset('storage/' . $photoPath) : asset('assets/img/user.jpg');
                                    @endphp
                                    <img src="{{ $photoUrl }}" alt="Photo" class="rounded-circle" width="40" height="40">
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-0">{{ $eleve->nom ?? '' }} {{ $eleve->prenom ?? '' }}</h6>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($eleve->naissance)
                                {{ \Carbon\Carbon::parse($eleve->naissance)->format('d/m/Y') }}
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('documents.generer-carte-eleve', $eleve->id) }}"
                               class="btn btn-sm btn-outline-primary" target="_blank">
                                <i class="ti ti-id-badge-2 me-1"></i>Carte individuelle
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-4">
                            <i class="ti ti-user-off fs-1 text-muted"></i>
                            <p class="text-muted mt-2">Aucun élève actif dans cette classe</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection