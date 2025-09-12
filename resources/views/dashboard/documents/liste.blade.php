<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
        }
        .header .date {
            font-style: italic;
        }
        .filters {
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .filters h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #333;
        }
        .filter-item {
            margin-right: 15px;
            display: inline-block;
        }
        .filter-label {
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        table th {
            background-color: #3498DB;
            color: white;
            font-weight: bold;
        }
        table tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $title }}</h1>
        <div class="date">Généré le: {{ $date }}</div>
    </div>

    <!-- Section des filtres appliqués -->
    <div class="filters">
        <h3>Filtres appliqués:</h3>
        <div class="filter-item"><span class="filter-label">Classe:</span> {{ $filters['classe'] }}</div>
        <div class="filter-item"><span class="filter-label">Nom:</span> {{ $filters['nom'] }}</div>
        <div class="filter-item"><span class="filter-label">Sexe:</span> {{ $filters['sexe'] }}</div>
        <div class="filter-item"><span class="filter-label">Cantine:</span> {{ $filters['cantine'] }}</div>
        <div class="filter-item"><span class="filter-label">Transport:</span> {{ $filters['transport'] }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Matricule</th>
                <th>Nom Complet</th>
                <th>Classe</th>
                <th>Date Naissance</th>
                <th>Sexe</th>
                <th>Parent</th>
                <th>Téléphone</th>
                <th>Cantine</th>
                <th>Transport</th>
            </tr>
        </thead>
        <tbody>
            @foreach($eleves as $inscription)
            <tr>
                <td>{{ $inscription->eleve->code_national ?? $inscription->eleve->matricule }}</td>
                <td>{{ $inscription->eleve->nom_complet }}</td>
                <td>{{ $inscription->classe->nom }}</td>
                <td>{{ $inscription->eleve->naissance_formattee }}</td>
                <td>{{ $inscription->eleve->sexe }}</td>
                <td>{{ $inscription->eleve->parent_nom }}</td>
                <td>{{ $inscription->eleve->parent_telephone }}</td>
                <td>{{ $inscription->eleve->cantine_active ? 'Oui' : 'Non' }}</td>
                <td>{{ $inscription->eleve->transport_active ? 'Oui' : 'Non' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        École - {{ config('app.name') }} | Total: {{ $eleves->count() }} élève(s) | Page 1 sur 1
    </div>
</body>
</html>