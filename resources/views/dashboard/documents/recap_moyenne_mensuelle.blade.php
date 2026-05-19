<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Récapitulatif mensuel - {{ $data['classe']->nom }} - {{ $data['mois']->nom }}</title>
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
        .rang {
            font-size: 9px;
            color: #666;
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
    <span>Imprimé le {{ now()->format('d/m/Y') }}</span> — <span>Page {PAGE_NUM} / {PAGE_COUNT}</span>
</footer>

<main>
    <h2>{{ strtoupper($data['classe']->nom) }} — {{ strtoupper($data['mois']->nom) }}</h2>
    
    <div class="stats">
        <strong>Statistiques de la classe :</strong> 
        Moyenne: {{ number_format($data['moyenne_classe'], 2, ',', '') }} / {{ $data['moy_base'] }} | 
        Max: {{ number_format($data['moyenne_max'], 2, ',', '') }} | 
        Min: {{ number_format($data['moyenne_min'], 2, ',', '') }} | 
        Effectif: {{ $data['effectif'] }}
    </div>

    <!-- Tableau des matières -->
    <table>
        <thead>
            <tr>
                <th style="width:18%">Nom & Prénoms</th>
                @foreach($data['matieres'] as $matiere)
                    @if(($matiere->pivot->coefficient ?? 0) > 0)
                        <th>
                            {{ strtoupper($matiere->nom) }}<br>
                            <small>(Coeff {{ $matiere->pivot->coefficient ?? 1 }})</small>
                        </th>
                    @endif
                @endforeach
                <th style="width:10%">Moyenne<br><small>/{{ $data['moy_base'] }}</small></th>
                <th style="width:8%">Rang<br><small>/{{ $data['effectif'] }}</small></th>
                <th style="width:15%">Appréciation</th>
            </tr>
        </thead>
        <tbody>
            @php
                // Trier les élèves par nom puis prénom alphabétique
                $elevesTries = $data['eleves'];
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
                    $notesParMatiere = isset($eleve['details']) ? $eleve['details'] : [];
                @endphp
                <tr>
                    <td class="left">{{ strtoupper($eleve['nom']) }} {{ ucfirst($eleve['prenom']) }}</td>
                    
                    @foreach($data['matieres'] as $matiere)
                        @if(($matiere->pivot->coefficient ?? 0) > 0)
                            <td>
                                @if(isset($notesParMatiere[$matiere->id]))
                                    <strong>{{ number_format($notesParMatiere[$matiere->id]['valeur'], 2, ',', '') }}</strong>
                                    <br><span class="rang">/{{ $notesParMatiere[$matiere->id]['base'] }}</span>
                                    @if(isset($notesParMatiere[$matiere->id]['rang']))
                                        <br><span class="rang">Rg: {{ $notesParMatiere[$matiere->id]['rang'] }}</span>
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                        @endif
                    @endforeach
                    
                    <td class="bold">{{ $eleve['moyenne'] }}</td>
                    <td class="bold">
                        {{ $eleve['rang'] }}{{ $eleve['exaequo'] ? 'e ex æquo' : 'e' }}
                    </td>
                    <td class="left">{{ $eleve['appreciation'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</main>
</body>
</html>