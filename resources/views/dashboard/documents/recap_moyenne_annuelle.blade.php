<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Récapitulatif annuel</title>
    <style>
        @page {
            margin: 90px 25px 50px 25px;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
        }
        header {
            position: fixed;
            top: -70px;
            left: 0;
            right: 0;
            height: 60px;
            text-align: center;
            border-bottom: 1px solid #000;
        }
        header img.logo {
            height: 50px;
            vertical-align: middle;
        }
        header .ecole-info {
            display: inline-block;
            vertical-align: middle;
            margin-left: 10px;
        }
        footer {
            position: fixed;
            bottom: -30px;
            left: 0;
            right: 0;
            height: 20px;
            font-size: 10px;
            text-align: center;
            border-top: 1px solid #000;
        }
        h2 {
            text-align: center;
            margin: 0;
            padding: 0;
            font-size: 14px;
        }
        .stats {
            text-align: center;
            margin: 10px 0;
            font-size: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
            font-size: 10px;
        }
        th, td {
            border: 1px solid #000;
            padding: 6px;
            text-align: center;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .left {
            text-align: left;
        }
        .bold {
            font-weight: bold;
        }
        .text-success {
            color: green;
        }
        .text-danger {
            color: red;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>

<header>
    <img class="logo" src="{{ public_path('assets/img/logo_excelle.jpg') }}" alt="Logo école">
    <div class="ecole-info">
        <strong>{{ strtoupper(session('current_ecole_nom', 'ECOLE EXCELLE')) }}</strong><br>
        <small>Année scolaire : {{ session('current_annee_scolaire_nom', '') }}</small>
    </div>
</header>

<footer>
    <span>Imprimé le {{ now()->format('d/m/Y') }}</span>
</footer>

<main>
@foreach($data as $index => $classeData)
    <h2>{{ strtoupper($classeData['classe']->nom) }} — BULLETIN ANNUEL</h2>
    
    <div class="stats">
        <strong>Statistiques de la classe :</strong> 
        Moyenne générale: {{ number_format($classeData['moyenne_classe'], 2, ',', '') }} / {{ $classeData['moy_base'] }} | 
        Max: {{ number_format($classeData['moyenne_max'], 2, ',', '') }} | 
        Min: {{ number_format($classeData['moyenne_min'], 2, ',', '') }} | 
        Effectif: {{ $classeData['effectif'] }}
    </div>

    <!-- Tableau des moyennes par mois -->
    <table>
        <thead>
            <tr>
                <th rowspan="2" style="width:20%">Nom & Prénoms</th>
                @foreach($classeData['mois_notes'] as $mois)
                    <th colspan="2">
                        {{ strtoupper($mois['nom']) }}<br>
                    </th>
                @endforeach
                <th rowspan="2" style="width:12%">Moyenne<br><small>/{{ $classeData['moy_base'] }}</small></th>
                <th rowspan="2" style="width:8%">Rang<br><small>/{{ $classeData['effectif'] }}</small></th>
                <th rowspan="2" style="width:10%">Décision</th>
            </tr>
            <tr>
                @foreach($classeData['mois_notes'] as $mois)
                    <th style="width:8%">Moyenne</th>
                    <th style="width:6%">Rang</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @php
                // Trier les élèves par nom puis prénom alphabétique
                $elevesTries = $classeData['eleves'];
                usort($elevesTries, function($a, $b) {
                    $cmpNom = strcmp($a['nom'], $b['nom']);
                    if ($cmpNom == 0) {
                        return strcmp($a['prenom'], $b['prenom']);
                    }
                    return $cmpNom;
                });
            @endphp
            
            @foreach($elevesTries as $eleve)
                @php
                    $moisNotes = isset($eleve['mois_notes']) ? $eleve['mois_notes'] : [];
                    $rangsMois = isset($eleve['rangs_mois']) ? $eleve['rangs_mois'] : [];
                @endphp
                <tr>
                    <td class="left">{{ strtoupper($eleve['nom']) }} {{ ucfirst($eleve['prenom']) }}</td>
                    
                    <!-- Affichage des moyennes par mois (déjà triés par ordre dans $classeData['mois_notes']) -->
                    @foreach($classeData['mois_notes'] as $mois)
                        <td>
                            @if(isset($moisNotes[$mois['id']]))
                                {{ number_format($moisNotes[$mois['id']], 2, ',', '') }}
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @if(isset($rangsMois[$mois['id']]))
                                {{ $rangsMois[$mois['id']]['rang'] }}e
                            @else
                                -
                            @endif
                        </td>
                    @endforeach
                    
                    <td class="bold">{{ $eleve['moyenne'] }}</td>
                    <td class="bold">
                        {{ $eleve['rang_general'] }}{{ $eleve['exaequo'] ? 'e ex æquo' : 'e' }}
                    </td>
                    <td class="bold">
                        <span class="@if($eleve['decision'] == 'ADMIS' || $eleve['decision'] == 'ADMISE') text-success
                            @elseif($eleve['decision'] == 'NON ADMIS' || $eleve['decision'] == 'NON ADMISE') text-danger
                            @endif">
                            {{ $eleve['decision'] }}
                        </span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if(!$loop->last)
        <div class="page-break"></div>
    @endif
@endforeach
</main>
</body>
</html>