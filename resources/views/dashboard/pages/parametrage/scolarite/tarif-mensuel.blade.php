@extends('dashboard.layouts.master')

@section('content')
<!-- Page Header -->
<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
    <div class="my-auto mb-2">
        <h3 class="page-title mb-1">Tarifs Mensuels</h3>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Tableau de Bord</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Tarifs Mensuels</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex my-xl-auto right-content align-items-center flex-wrap">
        <div class="pe-1 mb-2">
            <a href="{{ route('tarifs-mensuels.index') }}" class="btn btn-outline-light bg-white btn-icon me-1" data-bs-toggle="tooltip" data-bs-placement="top" title="Actualiser">
                <i class="ti ti-refresh"></i>
            </a>
        </div>
    </div>
</div>
<!-- /Page Header -->

<!-- Messages d'alerte -->
<div class="mb-4">
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif
</div>

<!-- Formulaire -->
<div class="card">
    <div class="card-body">
        <!-- Information sur l'année scolaire -->
        {{-- <div class="alert alert-info mb-3">
            <i class="ti ti-calendar me-2"></i>
            <strong>Année Scolaire :</strong> {{ $anneeScolaire->annee ?? 'Non définie' }}
            (du {{ \Carbon\Carbon::parse($anneeScolaire->date_debut)->format('d/m/Y') ?? '--' }} 
            au {{ \Carbon\Carbon::parse($anneeScolaire->date_fin)->format('d/m/Y') ?? '--' }})
            <span class="badge bg-success ms-2">
                {{ $moisScolaires->count() }} mois
            </span>
        </div> --}}

        <form action="{{ route('tarifs-mensuels.store') }}" method="POST" id="tarifMensuelForm">
            @csrf
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Tarif <span class="text-danger">*</span></label>
                        <select class="form-select" name="tarif_id" id="tarif_id" required>
                            <option value="">Sélectionner un tarif</option>
                            @foreach($tarifs as $tarif)
                                <option value="{{ $tarif->id }}" 
                                    {{ (old('tarif_id') == $tarif->id || (isset($selectedTarifId) && $selectedTarifId == $tarif->id)) ? 'selected' : '' }}>
                                    {{ $tarif->typeFrais->nom ?? 'Non défini' }} 
                                    @if($tarif->libelle) - {{ $tarif->libelle }} @endif
                                    @if($tarif->niveau_id) 
                                        ({{ $tarif->niveau->nom ?? 'Niveau' }})
                                    @else
                                        (Tous les niveaux)
                                    @endif
                                    
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Montant Annuel (FCFA)</label>
                        <input type="text" class="form-control" id="tarif_annuel_display" value="-" disabled>
                        <input type="hidden" name="tarif_annuel" id="tarif_annuel" value="">
                        <input type="hidden" name="type_frais_id" id="type_frais_id" value="">
                        <input type="hidden" name="niveau_id" id="niveau_id" value="">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Total Mensuel</label>
                        <input type="text" class="form-control" id="total_mensuel_display" value="0 FCFA" disabled>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="alert alert-warning">
                        <i class="ti ti-alert-triangle me-1"></i>
                        <strong>Attention :</strong> La somme des montants mensuels ne doit pas dépasser le montant annuel.
                    </div>
                </div>
            </div>

            <!-- Tableau des mois -->
            <div class="table-responsive mt-3">
                <table class="table table-bordered table-hover" id="table-mois">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Mois</th>
                            <th>Montant (FCFA)</th>
                            <th style="width: 120px;">Statut</th>
                        </tr>
                    </thead>
                    <tbody id="mois-container">
                        @foreach($moisScolaires as $index => $mois)
                            @php
                                $oldMontant = old('montants.' . $mois->id, 0);
                                $montant = $oldMontant;
                                $statut = $montant > 0 ? 'Enregistré' : 'Non défini';
                                $badgeClass = $montant > 0 ? 'bg-success' : 'bg-secondary';
                            @endphp
                            <tr>
                                <td class="text-center">{{ $loop->iteration }}</td>
                                <td>
                                    <strong>{{ $mois->nom }}</strong>
                                    <input type="hidden" name="mois_ids[]" value="{{ $mois->id }}">
                                </td>
                                <td>
                                    <input type="number" 
                                           class="form-control montant-input" 
                                           name="montants[{{ $mois->id }}]" 
                                           id="montant_{{ $mois->id }}"
                                           value="{{ old('montants.' . $mois->id, $montant) }}"
                                           min="0" 
                                           step="100"
                                           placeholder="0"
                                           data-mois="{{ $mois->id }}">
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $badgeClass }} statut-badge" id="statut_{{ $mois->id }}">
                                        {{ $statut }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="2" class="text-end"><strong>Total Mensuel</strong></th>
                            <th id="total_mensuel_footer">0 FCFA</th>
                            <th></th>
                        </tr>
                        <tr>
                            <th colspan="2" class="text-end text-danger"><strong>Montant Annuel</strong></th>
                            <th id="tarif_annuel_footer">-</th>
                            <th></th>
                        </tr>
                        <tr>
                            <th colspan="2" class="text-end"><strong>Différence</strong></th>
                            <th id="difference_footer">-</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="row mt-4">
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary" id="btnSubmit">
                        <i class="ti ti-device-floppy me-2"></i>Enregistrer les tarifs mensuels
                    </button>
                    <button type="reset" class="btn btn-secondary" id="btnReset">
                        <i class="ti ti-refresh me-2"></i>Réinitialiser
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function () {
    var tarifAnnuel = 0;
    var btnSubmit = $('#btnSubmit');

    function verifierBouton() {
        var total = 0;
        $('.montant-input').each(function() {
            var val = parseFloat($(this).val()) || 0;
            total += val;
        });
        
        var tarifAnnuelVal = parseFloat($('#tarif_annuel').val()) || 0;
        
        if (tarifAnnuelVal > 0 && total > tarifAnnuelVal) {
            btnSubmit.prop('disabled', true);
            btnSubmit.html('<i class="ti ti-alert-triangle me-2"></i> Dépassement du montant annuel');
            btnSubmit.removeClass('btn-primary').addClass('btn-danger');
        } else if (tarifAnnuelVal > 0 && total === 0) {
            btnSubmit.prop('disabled', true);
            btnSubmit.html('<i class="ti ti-info-circle me-2"></i> Veuillez saisir un montant');
            btnSubmit.removeClass('btn-primary').addClass('btn-warning');
        } else {
            btnSubmit.prop('disabled', false);
            btnSubmit.html('<i class="ti ti-device-floppy me-2"></i> Enregistrer les tarifs mensuels');
            btnSubmit.removeClass('btn-danger btn-warning').addClass('btn-primary');
        }
    }

    function calculerTotal() {
        var total = 0;
        $('.montant-input').each(function() {
            var val = parseFloat($(this).val()) || 0;
            total += val;
            var moisId = $(this).data('mois');
            if (val > 0) {
                $('#statut_' + moisId).removeClass('bg-secondary').addClass('bg-success').text('Enregistré');
            } else {
                $('#statut_' + moisId).removeClass('bg-success').addClass('bg-secondary').text('Non défini');
            }
        });
        
        var tarifAnnuelVal = parseFloat($('#tarif_annuel').val()) || 0;
        var difference = tarifAnnuelVal - total;

        var totalFormate = total.toLocaleString('fr-FR') + ' FCFA';
        var annuelFormate = tarifAnnuelVal > 0 ? tarifAnnuelVal.toLocaleString('fr-FR') + ' FCFA' : '-';
        
        $('#total_mensuel_display').val(totalFormate);
        $('#total_mensuel_footer').text(totalFormate);
        $('#tarif_annuel_footer').text(annuelFormate);
        
        if (tarifAnnuelVal > 0) {
            if (difference < 0) {
                $('#difference_footer').html('<span class="text-danger">' + difference.toLocaleString('fr-FR') + ' FCFA (Dépassement)</span>');
                $('#difference_info').html('<span class="text-danger">Différence : ' + difference.toLocaleString('fr-FR') + ' FCFA (Dépassement)</span>');
            } else {
                $('#difference_footer').html('<span class="text-success">' + difference.toLocaleString('fr-FR') + ' FCFA</span>');
                $('#difference_info').html('<span class="text-success">Différence : ' + difference.toLocaleString('fr-FR') + ' FCFA</span>');
            }
        } else {
            $('#difference_footer').text('-');
            $('#difference_info').text('Différence : -');
        }

        if (tarifAnnuelVal > 0 && total > tarifAnnuelVal) {
            $('#total_mensuel_display').addClass('text-danger');
            $('#total_mensuel_display').removeClass('text-success');
        } else if (tarifAnnuelVal > 0 && total > 0) {
            $('#total_mensuel_display').removeClass('text-danger');
            $('#total_mensuel_display').addClass('text-success');
        } else {
            $('#total_mensuel_display').removeClass('text-danger text-success');
        }

        verifierBouton();
    }

    function chargerTarifsMensuels() {
        var tarifId = $('#tarif_id').val();

        if (!tarifId) {
            $('.montant-input').val(0);
            $('#tarif_annuel').val(0);
            $('#tarif_annuel_display').val('-');
            $('#tarif_annuel_footer').text('-');
            $('#type_frais_id').val('');
            $('#niveau_id').val('');
            $('#tarif_info').html(
                '<i class="ti ti-info-circle"></i> Sélectionnez un tarif'
            );
            tarifAnnuel = 0;
            $('.statut-badge').removeClass('bg-success').addClass('bg-secondary').text('Non défini');
            calculerTotal();
            return;
        }

        $.ajax({
            url: "{{ route('tarifs-mensuels.get-tarifs') }}",
            type: "GET",
            data: {
                tarif_id: tarifId
            },
            success: function(response) {
                if (response.tarif_annuel) {
                    tarifAnnuel = response.tarif_annuel;
                    $('#tarif_annuel').val(response.tarif_annuel);
                    $('#tarif_annuel_display').val(
                        parseFloat(response.tarif_annuel).toLocaleString('fr-FR') + ' FCFA'
                    );
                    $('#tarif_annuel_footer').text(
                        parseFloat(response.tarif_annuel).toLocaleString('fr-FR') + ' FCFA'
                    );
                    var infoText = '<i class="ti ti-check-circle text-success"></i> ';
                    if (response.type_frais_nom) {
                        infoText += response.type_frais_nom;
                    }
                    if (response.libelle) {
                        infoText += ' - ' + response.libelle;
                    }
                    if (response.niveau_nom) {
                        infoText += ' (' + response.niveau_nom + ')';
                    } else {
                        // infoText += ' (Tous les niveaux)';
                    }
                    infoText += ' - ' + parseFloat(response.tarif_annuel).toLocaleString('fr-FR') + ' FCFA';
                    $('#tarif_info').html(infoText);
                } else {
                    tarifAnnuel = 0;
                    $('#tarif_annuel').val(0);
                    $('#tarif_annuel_display').val('Aucun tarif');
                    $('#tarif_annuel_footer').text('-');
                    $('#tarif_info').html(
                        '<i class="ti ti-alert-circle text-danger"></i> Tarif non trouvé'
                    );
                }

                if (response.tarifs) {
                    $('.montant-input').each(function() {
                        var moisId = $(this).data('mois');
                        if (response.tarifs[moisId]) {
                            $(this).val(response.tarifs[moisId].montant);
                            $('#statut_' + moisId).removeClass('bg-secondary').addClass('bg-success').text('Enregistré');
                        } else {
                            $(this).val(0);
                            $('#statut_' + moisId).removeClass('bg-success').addClass('bg-secondary').text('Non défini');
                        }
                    });
                }

                calculerTotal();
            },
            error: function(xhr) {
                toastr.error('Erreur lors du chargement des tarifs mensuels');
            }
        });
    }

    $('#tarif_id').on('change', function() {
        chargerTarifsMensuels();
    });

    $(document).on('input', '.montant-input', function() {
        var val = parseFloat($(this).val()) || 0;
        var moisId = $(this).data('mois');
        if (val > 0) {
            $('#statut_' + moisId).removeClass('bg-secondary').addClass('bg-success').text('Enregistré');
        } else {
            $('#statut_' + moisId).removeClass('bg-success').addClass('bg-secondary').text('Non défini');
        }
        calculerTotal();
    });

    $('#btnReset').on('click', function(e) {
        e.preventDefault();
        if (confirm('Voulez-vous vraiment effacer tous les montants ?')) {
            $('.montant-input').val(0);
            $('.statut-badge').removeClass('bg-success').addClass('bg-secondary').text('Non défini');
            calculerTotal();
        }
    });

    $('#tarifMensuelForm').on('submit', function(e) {
        var total = 0;
        var totalAvecMontant = 0;
        $('.montant-input').each(function() {
            var val = parseFloat($(this).val()) || 0;
            total += val;
            if (val > 0) {
                totalAvecMontant++;
            }
        });

        var tarifAnnuelVal = parseFloat($('#tarif_annuel').val()) || 0;

        if (tarifAnnuelVal > 0 && total > tarifAnnuelVal) {
            e.preventDefault();
            toastr.error('Le total mensuel (' + total.toLocaleString('fr-FR') + 
                        ' FCFA) dépasse le montant annuel (' + tarifAnnuelVal.toLocaleString('fr-FR') + 
                        ' FCFA). Veuillez ajuster les montants.');
            return false;
        }

        if (totalAvecMontant === 0) {
            e.preventDefault();
            toastr.warning('Veuillez saisir au moins un montant mensuel.');
            return false;
        }

        var submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true);
        submitBtn.html('<i class="ti ti-loader ti-spin me-2"></i> Enregistrement...');
    });

    @if($errors->any())
        @foreach($errors->all() as $error)
            toastr.error("{{ $error }}");
        @endforeach
    @endif

    @if(session('success'))
        toastr.success("{{ session('success') }}");
    @endif

    @if(session('error'))
        toastr.error("{{ session('error') }}");
    @endif

    var tarifInitial = $('#tarif_id').val();
    
    if (tarifInitial) {
        chargerTarifsMensuels();
    } else {
        var urlParams = new URLSearchParams(window.location.search);
        var tarifIdFromUrl = urlParams.get('tarif_id');
        if (tarifIdFromUrl) {
            $('#tarif_id').val(tarifIdFromUrl);
            chargerTarifsMensuels();
        }
    }

    if (window.location.search.includes('tarif_id=')) {
        setTimeout(function() {
            var tarifId = new URLSearchParams(window.location.search).get('tarif_id');
            if (tarifId && !$('#tarif_id').val()) {
                $('#tarif_id').val(tarifId);
                chargerTarifsMensuels();
            }
        }, 1000);
    }
});
</script>
@endsection