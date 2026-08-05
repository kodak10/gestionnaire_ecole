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
            table-layout: fixed;
        }
        th, td {
            border: 1px solid #000;
            padding: 4px 3px;
            text-align: center;
            vertical-align: middle;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
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
            font-size: 8px;
            color: #666;
            display: inline-block;
        }
        .page-break {
            page-break-after: always;
        }
        .col-nom { width: 18%; }
        .col-matiere { width: 8%; }
        .col-moyenne { width: 8%; }
        .col-rang { width: 6%; }
        .col-appreciation { width: 12%; }
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
    <h2>{{ strtoupper($data['classe']->nom ?? '') }} — {{ strtoupper($data['mois']->nom ?? '') }}</h2>
    
    <div class="stats">
        <strong>Statistiques de la classe :</strong> 
        Moyenne: {{ number_format($data['moyenne_classe'] ?? 0, 2, ',', '') }} / {{ $data['moy_base'] ?? 20 }} | 
        Max: {{ number_format($data['moyenne_max'] ?? 0, 2, ',', '') }} | 
        Min: {{ number_format($data['moyenne_min'] ?? 0, 2, ',', '') }} | 
        Effectif: {{ $data['effectif'] ?? 0 }}
    </div>

    <!-- Tableau des matières -->
    <table>
        <thead>
            <tr>
                <th class="col-nom">Nom & Prénoms</th>
                @foreach($data['matieres'] ?? [] as $matiere)
                    @if(($matiere->pivot->coefficient ?? 0) > 0)
                        <th class="col-matiere">
                            {{ strtoupper($matiere->nom) }}
                            <br><small>(Coef {{ $matiere->pivot->coefficient ?? 1 }})</small>
                        </th>
                    @endif
                @endforeach
                <th class="col-moyenne">Moy.<br><small>/{{ $data['moy_base'] ?? 20 }}</small></th>
                <th class="col-rang">Rang<br><small>/{{ $data['effectif'] ?? 0 }}</small></th>
                <th class="col-appreciation">Appréciation</th>
            </tr>
        </thead>
        <tbody>
            @php
                $elevesTries = $data['eleves'] ?? [];
                usort($elevesTries, function($a, $b) {
                    $cmpNom = strcmp($a['nom'] ?? '', $b['nom'] ?? '');
                    if ($cmpNom == 0) {
                        return strcmp($a['prenom'] ?? '', $b['prenom'] ?? '');
                    }
                    return $cmpNom;
                });
            @endphp
            
            @foreach($elevesTries as $eleve)
                @php
                    $notesParMatiere = isset($eleve['details']) ? $eleve['details'] : [];
                @endphp
                <tr>
                    <td class="left">{{ strtoupper($eleve['nom'] ?? '') }} {{ ucfirst($eleve['prenom'] ?? '') }}</td>
                    
                    @foreach($data['matieres'] ?? [] as $matiere)
                        @if(($matiere->pivot->coefficient ?? 0) > 0)
                            <td>
                                @if(isset($notesParMatiere[$matiere->id]))
                                    <strong>{{ number_format($notesParMatiere[$matiere->id]['valeur'] ?? 0, 2, ',', '') }}</strong>
                                    <span>/{{ $notesParMatiere[$matiere->id]['base'] ?? 20 }}</span>
                                @else
                                    -
                                @endif
                            </td>
                        @endif
                    @endforeach
                    
                    <td class="bold">{{ $eleve['moyenne'] ?? '0,00' }}</td>
                    <td class="bold">
                        {{ $eleve['rang_general'] ?? '-' }}{{ isset($eleve['exaequo']) && $eleve['exaequo'] ? 'e ex æquo' : 'e' }}
                    </td>
                    <td class="left">{{ $eleve['appreciation'] ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</main>
</body>
</html>