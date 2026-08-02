{{-- resources/views/dashboard/documents/liste.blade.php --}}

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

    <div class="filters">
        <h3>Filtres appliqués:</h3>
        <div class="filter-item"><span class="filter-label">Classe:</span> {{ $filters['classe'] ?? 'Toutes' }}</div>
        <div class="filter-item"><span class="filter-label">Sexe:</span> {{ $filters['sexe'] ?? 'Tous' }}</div>
        @if(isset($filters['cantine']) && $filters['cantine'] !== 'Tous' && $filters['cantine'] !== null)
            <div class="filter-item"><span class="filter-label">Cantine:</span> {{ $filters['cantine'] }}</div>
        @endif
        @if(isset($filters['transport']) && $filters['transport'] !== 'Tous' && $filters['transport'] !== null)
            <div class="filter-item"><span class="filter-label">Transport:</span> {{ $filters['transport'] }}</div>
        @endif
        @if(isset($filters['nom']) && $filters['nom'] !== 'Tous' && $filters['nom'] !== null)
            <div class="filter-item"><span class="filter-label">Recherche:</span> {{ $filters['nom'] }}</div>
        @endif
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
                @if(isset($filters['cantine']) && $filters['cantine'] !== 'Tous' && $filters['cantine'] !== null)
                    <th>Cantine</th>
                @endif
                @if(isset($filters['transport']) && $filters['transport'] !== 'Tous' && $filters['transport'] !== null)
                    <th>Transport</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($eleves as $eleve)
            <tr>
                <td>{{ $eleve->code_national ?? $eleve->matricule }}</td>
                <td>{{ $eleve->nom }} {{ $eleve->prenom }}</td>
                <td>{{ $eleve->classe_nom ?? 'Non assigné' }}</td>
                <td>{{ $eleve->naissance ? date('d/m/Y', strtotime($eleve->naissance)) : '' }}</td>
                <td>{{ $eleve->sexe ?? '' }}</td>
                <td>{{ $eleve->parent_nom ?? $eleve->pere_nom ?? '' }}</td>
                <td>
                    @if($eleve->parent_telephone)
                        {{ $eleve->parent_telephone }}
                    @endif
                    @if($eleve->parent_telephone02)
                        / {{ $eleve->parent_telephone02 }}
                    @endif
                </td>
                @if(isset($filters['cantine']) && $filters['cantine'] !== 'Tous' && $filters['cantine'] !== null)
                    <td>{{ $eleve->cantine_active ? 'Oui' : 'Non' }}</td>
                @endif
                @if(isset($filters['transport']) && $filters['transport'] !== 'Tous' && $filters['transport'] !== null)
                    <td>{{ $eleve->transport_active ? 'Oui' : 'Non' }}</td>
                @endif
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        École - {{ config('app.name') }} | Total: {{ $eleves->count() }} élève(s) | Page 1 sur 1
    </div>
</body>
</html>