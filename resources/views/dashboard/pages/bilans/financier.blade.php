@extends('dashboard.layouts.master')
@section('content')
<!-- Page Header -->
<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
    <div class="my-auto">
        <h3 class="mb-1">Bilan Financier</h3>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Tableau de Bord</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Bilan Financier</li>
            </ol>
        </nav>
    </div>
    <div>
        <button class="btn btn-primary" id="print-btn"><i class="ti ti-printer me-2"></i>Imprimer</button>
        <button class="btn btn-success" id="export-btn"><i class="ti ti-file-spreadsheet me-2"></i>Exporter</button>
    </div>
</div>
<!-- /Page Header -->

<!-- Filtres -->
<div class="row mb-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('bilans.financier') }}" class="row align-items-end">
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Période</label>
                            <select class="form-select" name="periode" id="periode">
                                <option value="annuelle" {{ $periode == 'annuelle' ? 'selected' : '' }}>Annuelle</option>
                                <option value="mensuelle" {{ $periode == 'mensuelle' ? 'selected' : '' }}>Mensuelle</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Mois</label>
                            <select class="form-select" name="mois_id" id="mois_id">
                                <option value="">-- Sélectionner un mois --</option>
                                @foreach($moisScolaires as $mois)
                                    <option value="{{ $mois->id }}" {{ $moisId == $mois->id ? 'selected' : '' }}>
                                        {{ $mois->nom }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ti ti-filter me-2"></i>Filtrer
                        </button>
                    </div>
                    <div class="col-md-2">
                        <a href="{{ route('bilans.financier') }}" class="btn btn-secondary w-100">
                            <i class="ti ti-refresh me-2"></i>Réinitialiser
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Cartes de statistiques -->
    <div class="col-md-3">
        <div class="card card-body bg-primary bg-opacity-10 border-primary">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <h2 class="fw-bold mb-0">{{ number_format($statistiques['total_paiements'], 0, ',', ' ') }} FCFA</h2>
                    <span>Total Payé</span>
                </div>
                <div class="flex-shrink-0">
                    <i class="ti ti-currency-dollar fs-1 text-primary"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-body bg-success bg-opacity-10 border-success">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <h2 class="fw-bold mb-0">{{ number_format($statistiques['total_attendu'], 0, ',', ' ') }} FCFA</h2>
                    <span>Total Attendu</span>
                </div>
                <div class="flex-shrink-0">
                    <i class="ti ti-coin fs-1 text-success"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-body bg-info bg-opacity-10 border-info">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <h2 class="fw-bold mb-0">{{ $statistiques['nombre_paiements'] }}</h2>
                    <span>Nombre de Paiements</span>
                </div>
                <div class="flex-shrink-0">
                    <i class="ti ti-receipt fs-1 text-info"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-body bg-warning bg-opacity-10 border-warning">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <h2 class="fw-bold mb-0">{{ $tauxRecouvrement['taux'] }}%</h2>
                    <span>Taux de Recouvrement</span>
                </div>
                <div class="flex-shrink-0">
                    <i class="ti ti-percentage fs-1 text-warning"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <!-- Détail du taux de recouvrement -->
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="text-center">
                            <h6 class="text-muted">Total Attendu</h6>
                            <h4 class="fw-bold">{{ number_format($tauxRecouvrement['total_attendu'], 0, ',', ' ') }} FCFA</h4>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <h6 class="text-muted">Total Payé</h6>
                            <h4 class="fw-bold text-success">{{ number_format($tauxRecouvrement['total_paye'], 0, ',', ' ') }} FCFA</h4>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <h6 class="text-muted">Reste à Payer</h6>
                            <h4 class="fw-bold text-danger">{{ number_format($tauxRecouvrement['reste'], 0, ',', ' ') }} FCFA</h4>
                        </div>
                    </div>
                </div>
                <!-- Barre de progression du taux de recouvrement -->
                <div class="mt-3">
                    <div class="progress" style="height: 25px;">
                        @php
                            $taux = $tauxRecouvrement['taux'];
                            $color = 'bg-success';
                            if ($taux < 30) $color = 'bg-danger';
                            elseif ($taux < 60) $color = 'bg-warning';
                            elseif ($taux < 80) $color = 'bg-info';
                        @endphp
                        <div class="progress-bar {{ $color }} progress-bar-striped progress-bar-animated" 
                             role="progressbar" 
                             style="width: {{ min($taux, 100) }}%;" 
                             aria-valuenow="{{ $taux }}" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                            {{ $taux }}%
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <!-- Graphique Répartition par Type de Frais -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">Répartition par Type de Frais</h5>
            </div>
            <div class="card-body">
                <canvas id="chartTypeFrais" height="250"></canvas>
            </div>
        </div>
    </div>

    <!-- Graphique Évolution des Paiements -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">Évolution des Paiements</h5>
            </div>
            <div class="card-body">
                <canvas id="chartEvolutionPaiements" height="250"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <!-- Graphique Répartition par Mode de Paiement -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">Mode de Paiement</h5>
            </div>
            <div class="card-body">
                <canvas id="chartModePaiement" height="250"></canvas>
            </div>
        </div>
    </div>

    <!-- Résumé par Classe (Graphique) -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">Taux de Recouvrement par Classe</h5>
            </div>
            <div class="card-body">
                <canvas id="chartRecouvrementClasse" height="250"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <!-- Paiements par Classe avec détails -->
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-light">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Détail des Paiements par Classe</h5>
                    <span class="badge bg-primary">
                        {{ $periode == 'annuelle' ? 'Période Annuelle' : 'Période Mensuelle' }}
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="classe-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Classe</th>
                                <th class="text-center">Effectif</th>
                                <th class="text-end">Montant Attendu</th>
                                <th class="text-end">Montant Payé</th>
                                <th class="text-end">Reste à Payer</th>
                                <th class="text-center">Taux de Recouvrement</th>
                                <th class="text-center">Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $totalAttenduGeneral = 0; $totalPayeGeneral = 0; @endphp
                            @foreach($paiementsParClasse as $index => $classe)
                                @php 
                                    $totalAttenduGeneral += $classe->total_attendu;
                                    $totalPayeGeneral += $classe->total_paye;
                                    $tauxClasse = $classe->taux_recouvrement;
                                    
                                    $statutColor = 'bg-success';
                                    $statutText = 'Bon';
                                    if ($tauxClasse < 30) {
                                        $statutColor = 'bg-danger';
                                        $statutText = 'Critique';
                                    } elseif ($tauxClasse < 60) {
                                        $statutColor = 'bg-warning';
                                        $statutText = 'Moyen';
                                    } elseif ($tauxClasse < 80) {
                                        $statutColor = 'bg-info';
                                        $statutText = 'Acceptable';
                                    }
                                @endphp
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td><strong>{{ $classe->nom }}</strong></td>
                                    <td class="text-center">{{ $classe->eleves }}</td>
                                    <td class="text-end">{{ number_format($classe->total_attendu, 0, ',', ' ') }} FCFA</td>
                                    <td class="text-end text-success">{{ number_format($classe->total_paye, 0, ',', ' ') }} FCFA</td>
                                    <td class="text-end text-danger">{{ number_format($classe->reste, 0, ',', ' ') }} FCFA</td>
                                    <td class="text-center">
                                        <span class="fw-bold">{{ $tauxClasse }}%</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $statutColor }}">{{ $statutText }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold bg-light">
                                <td colspan="2">TOTAL GÉNÉRAL</td>
                                <td class="text-center">{{ $paiementsParClasse->sum('eleves') }}</td>
                                <td class="text-end">{{ number_format($totalAttenduGeneral, 0, ',', ' ') }} FCFA</td>
                                <td class="text-end text-success">{{ number_format($totalPayeGeneral, 0, ',', ' ') }} FCFA</td>
                                <td class="text-end text-danger">{{ number_format($totalAttenduGeneral - $totalPayeGeneral, 0, ',', ' ') }} FCFA</td>
                                <td class="text-center">
                                    @php $tauxGeneral = $totalAttenduGeneral > 0 ? round(($totalPayeGeneral / $totalAttenduGeneral) * 100, 2) : 0; @endphp
                                    <span class="fw-bold">{{ $tauxGeneral }}%</span>
                                </td>
                                <td class="text-center">
                                    @php
                                        $statutColor = 'bg-success';
                                        $statutText = 'Bon';
                                        if ($tauxGeneral < 30) {
                                            $statutColor = 'bg-danger';
                                            $statutText = 'Critique';
                                        } elseif ($tauxGeneral < 60) {
                                            $statutColor = 'bg-warning';
                                            $statutText = 'Moyen';
                                        } elseif ($tauxGeneral < 80) {
                                            $statutColor = 'bg-info';
                                            $statutText = 'Acceptable';
                                        }
                                    @endphp
                                    <span class="badge {{ $statutColor }}">{{ $statutText }}</span>
                                </td>
                            </tr>
                        </tfoot>
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
.progress {
    border-radius: 10px;
}
.progress-bar {
    font-weight: 600;
    font-size: 14px;
    line-height: 25px;
}
.table tfoot {
    border-top: 2px solid #dee2e6;
}
.badge {
    padding: 5px 12px;
}
</style>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    // Gestion du filtre mois (activer/désactiver selon période)
    $('#periode').change(function() {
        if ($(this).val() === 'annuelle') {
            $('#mois_id').prop('disabled', true);
        } else {
            $('#mois_id').prop('disabled', false);
        }
    });
    
    // Initialisation
    if ($('#periode').val() === 'annuelle') {
        $('#mois_id').prop('disabled', true);
    }

    // Données pour les graphiques
    const chartData = {
        typeFrais: {
            labels: {!! $repartitionParType->pluck('type')->toJson() !!},
            data: {!! $repartitionParType->pluck('total')->toJson() !!}
        },
        evolution: {
            labels: {!! $evolutionPaiements->map(function($item) { 
                return date('F Y', mktime(0, 0, 0, $item->mois, 1, $item->annee)); 
            })->toJson() !!},
            data: {!! $evolutionPaiements->pluck('total')->toJson() !!}
        },
        modePaiement: {
            labels: ['Espèces', 'Chèque', 'Virement', 'Mobile Money'],
            data: [
                {{ $repartitionMode->where('mode_paiement', 'especes')->sum('total') ?? 0 }},
                {{ $repartitionMode->where('mode_paiement', 'cheque')->sum('total') ?? 0 }},
                {{ $repartitionMode->where('mode_paiement', 'virement')->sum('total') ?? 0 }},
                {{ $repartitionMode->where('mode_paiement', 'mobile_money')->sum('total') ?? 0 }}
            ]
        },
        recouvrementClasse: {
            labels: {!! $paiementsParClasse->pluck('nom')->toJson() !!},
            attendu: {!! $paiementsParClasse->pluck('total_attendu')->toJson() !!},
            paye: {!! $paiementsParClasse->pluck('total_paye')->toJson() !!},
            taux: {!! $paiementsParClasse->pluck('taux_recouvrement')->toJson() !!}
        }
    };

    // Graphique Type de Frais
    new Chart($('#chartTypeFrais'), {
        type: 'doughnut',
        data: {
            labels: chartData.typeFrais.labels,
            datasets: [{
                data: chartData.typeFrais.data,
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
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Graphique Évolution des Paiements
    new Chart($('#chartEvolutionPaiements'), {
        type: 'line',
        data: {
            labels: chartData.evolution.labels,
            datasets: [{
                label: 'Montant payé (FCFA)',
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
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString() + ' FCFA';
                        }
                    }
                }
            }
        }
    });

    // Graphique Mode de Paiement
    new Chart($('#chartModePaiement'), {
        type: 'pie',
        data: {
            labels: chartData.modePaiement.labels,
            datasets: [{
                data: chartData.modePaiement.data,
                backgroundColor: [
                    'rgba(13, 110, 253, 0.7)',
                    'rgba(255, 193, 7, 0.7)',
                    'rgba(25, 135, 84, 0.7)',
                    'rgba(220, 53, 69, 0.7)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Graphique Taux de Recouvrement par Classe
    new Chart($('#chartRecouvrementClasse'), {
        type: 'bar',
        data: {
            labels: chartData.recouvrementClasse.labels,
            datasets: [
                {
                    label: 'Attendu',
                    data: chartData.recouvrementClasse.attendu,
                    backgroundColor: 'rgba(13, 110, 253, 0.5)',
                    borderColor: 'rgba(13, 110, 253, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Payé',
                    data: chartData.recouvrementClasse.paye,
                    backgroundColor: 'rgba(25, 135, 84, 0.5)',
                    borderColor: 'rgba(25, 135, 84, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString() + ' FCFA';
                        }
                    }
                }
            }
        }
    });
});
</script>
@endsection