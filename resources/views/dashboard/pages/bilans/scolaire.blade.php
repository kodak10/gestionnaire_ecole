@extends('dashboard.layouts.master')
@section('content')
<!-- Page Header -->
<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
    <div class="my-auto">
        <h3 class="mb-1">Bilan Scolaire</h3>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Tableau de Bord</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Bilan Scolaire</li>
            </ol>
        </nav>
    </div>
    <div>
        <button class="btn btn-primary" id="print-btn"><i class="ti ti-printer me-2"></i>Imprimer</button>
        <button class="btn btn-success" id="export-btn"><i class="ti ti-file-spreadsheet me-2"></i>Exporter</button>
    </div>
</div>
<!-- /Page Header -->

<div class="row">
    <!-- Cartes de statistiques -->
    <div class="col-md-3">
        <div class="card card-body bg-primary bg-opacity-10 border-primary">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <h2 class="fw-bold mb-0">{{ $statistiques['total_eleves'] }}</h2>
                    <span>Total Élèves</span>
                </div>
                <div class="flex-shrink-0">
                    <i class="ti ti-users fs-1 text-primary"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-body bg-success bg-opacity-10 border-success">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <h2 class="fw-bold mb-0">{{ $statistiques['total_classes'] }}</h2>
                    <span>Total Classes</span>
                </div>
                <div class="flex-shrink-0">
                    <i class="ti ti-school fs-1 text-success"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-body bg-info bg-opacity-10 border-info">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <h2 class="fw-bold mb-0">{{ $statistiques['total_niveaux'] }}</h2>
                    <span>Total Niveaux</span>
                </div>
                <div class="flex-shrink-0">
                    <i class="ti ti-layers fs-1 text-info"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-body bg-warning bg-opacity-10 border-warning">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <h2 class="fw-bold mb-0">{{ $statistiques['moyenne_par_classe'] }}</h2>
                    <span>Moyenne/Classe</span>
                </div>
                <div class="flex-shrink-0">
                    <i class="ti ti-chart-bar fs-1 text-warning"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <!-- Graphique Répartition par Classe -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">Répartition par Classe</h5>
            </div>
            <div class="card-body">
                <canvas id="chartClasse" height="250"></canvas>
            </div>
        </div>
    </div>

    <!-- Graphique Répartition par Niveau -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">Répartition par Niveau</h5>
            </div>
            <div class="card-body">
                <canvas id="chartNiveau" height="250"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <!-- Graphique Répartition par Sexe -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">Répartition par Sexe</h5>
            </div>
            <div class="card-body">
                <canvas id="chartSexe" height="250"></canvas>
            </div>
        </div>
    </div>

    <!-- Graphique Services (Transport/Cantine) -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">Services Scolaires</h5>
            </div>
            <div class="card-body">
                <canvas id="chartServices" height="250"></canvas>
                <div class="mt-3">
                    <div class="row text-center">
                        <div class="col-4">
                            <h6>Transport</h6>
                            <p class="fw-bold text-primary">{{ $services['transport']['total'] }} élèves</p>
                            <small>{{ $services['transport']['pourcentage'] }}%</small>
                        </div>
                        <div class="col-4">
                            <h6>Cantine</h6>
                            <p class="fw-bold text-success">{{ $services['cantine']['total'] }} élèves</p>
                            <small>{{ $services['cantine']['pourcentage'] }}%</small>
                        </div>
                        <div class="col-4">
                            <h6>Total</h6>
                            <p class="fw-bold">{{ $services['total_eleves'] }} élèves</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <!-- Évolution des inscriptions -->
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">Évolution des Inscriptions</h5>
            </div>
            <div class="card-body">
                <canvas id="chartEvolution" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <!-- Tableau détaillé par classe -->
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">Détail par Classe</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Classe</th>
                                <th>Effectif</th>
                                <th>% Total</th>
                                <th>Transport</th>
                                <th>Cantine</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $totalEleves = $statistiques['total_eleves']; @endphp
                            @foreach($repartitionClasse as $classe)
                                <tr>
                                    <td>{{ $classe->nom }}</td>
                                    <td>{{ $classe->total }}</td>
                                    <td>{{ $totalEleves > 0 ? round(($classe->total / $totalEleves) * 100, 1) : 0 }}%</td>
                                    <td>
                                        @php
                                            $transport = \App\Models\Inscription::where('classe_id', $classe->id)
                                                ->where('transport_active', true)
                                                ->where('statut', 'active')
                                                ->count();
                                        @endphp
                                        {{ $transport }}
                                    </td>
                                    <td>
                                        @php
                                            $cantine = \App\Models\Inscription::where('classe_id', $classe->id)
                                                ->where('cantine_active', true)
                                                ->where('statut', 'active')
                                                ->count();
                                        @endphp
                                        {{ $cantine }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('styles')
<style>
.card-body {
    position: relative;
}
.fw-bold {
    font-weight: 600;
}
</style>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    // Données pour les graphiques
    const chartData = {
        classes: {
            labels: {!! $repartitionClasse->pluck('nom')->toJson() !!},
            data: {!! $repartitionClasse->pluck('total')->toJson() !!}
        },
        niveaux: {
            labels: {!! $repartitionNiveau->pluck('nom')->toJson() !!},
            data: {!! $repartitionNiveau->pluck('total')->toJson() !!}
        },
        sexe: {
            labels: {!! $repartitionSexe->pluck('sexe')->toJson() !!},
            data: {!! $repartitionSexe->pluck('total')->toJson() !!}
        },
        services: {
            labels: ['Transport', 'Cantine'],
            data: [
                {{ $services['transport']['total'] }},
                {{ $services['cantine']['total'] }}
            ]
        },
        evolution: {
            labels: {!! $evolutionInscriptions->map(function($item) { 
                return date('F Y', mktime(0, 0, 0, $item->mois, 1, $item->annee)); 
            })->toJson() !!},
            data: {!! $evolutionInscriptions->pluck('total')->toJson() !!}
        }
    };

    // Couleurs
    const colors = {
        primary: '#0d6efd',
        success: '#198754',
        info: '#0dcaf0',
        warning: '#ffc107',
        danger: '#dc3545',
        secondary: '#6c757d',
        dark: '#212529'
    };

    // Graphique Répartition par Classe
    new Chart($('#chartClasse'), {
        type: 'bar',
        data: {
            labels: chartData.classes.labels,
            datasets: [{
                label: 'Nombre d\'élèves',
                data: chartData.classes.data,
                backgroundColor: 'rgba(13, 110, 253, 0.5)',
                borderColor: 'rgba(13, 110, 253, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Graphique Répartition par Niveau
    new Chart($('#chartNiveau'), {
        type: 'doughnut',
        data: {
            labels: chartData.niveaux.labels,
            datasets: [{
                data: chartData.niveaux.data,
                backgroundColor: [
                    'rgba(13, 110, 253, 0.7)',
                    'rgba(25, 135, 84, 0.7)',
                    'rgba(255, 193, 7, 0.7)',
                    'rgba(220, 53, 69, 0.7)',
                    'rgba(108, 117, 125, 0.7)',
                    'rgba(13, 202, 240, 0.7)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Graphique Répartition par Sexe
    new Chart($('#chartSexe'), {
        type: 'pie',
        data: {
            labels: chartData.sexe.labels,
            datasets: [{
                data: chartData.sexe.data,
                backgroundColor: [
                    'rgba(13, 110, 253, 0.7)',
                    'rgba(220, 53, 69, 0.7)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Graphique Services
    new Chart($('#chartServices'), {
        type: 'bar',
        data: {
            labels: chartData.services.labels,
            datasets: [{
                label: 'Nombre d\'élèves',
                data: chartData.services.data,
                backgroundColor: [
                    'rgba(13, 110, 253, 0.5)',
                    'rgba(25, 135, 84, 0.5)'
                ],
                borderColor: [
                    'rgba(13, 110, 253, 1)',
                    'rgba(25, 135, 84, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Graphique Évolution
    new Chart($('#chartEvolution'), {
        type: 'line',
        data: {
            labels: chartData.evolution.labels,
            datasets: [{
                label: 'Nouvelles inscriptions',
                data: chartData.evolution.data,
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                borderColor: 'rgba(13, 110, 253, 1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});
</script>
@endsection