<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'Relance des Paiements' }}</title>
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
        .text-right { text-align: right; }
        .text-success { color: #28a745; }
        .text-danger { color: #dc3545; }
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
        <h1>{{ $title ?? 'Relance des Paiements' }}</h1>
        <div class="date">Généré le: {{ $date }}</div>
    </div>

    <div class="filters">
        <h3>Filtres appliqués:</h3>
            <div class="filter-item"><span class="filter-label">Classe:</span> {{ $filters['classe'] ?? 'Toutes' }}</div>
            <div class="filter-item"><span class="filter-label">Type:</span> {{ $filters['type_frais'] ?? 'Toutes' }}</div>
            <div class="filter-item"><span class="filter-label">Mois:</span> {{ $filters['mois'] ?? 'Tous' }}</div>

    </div>

    <table>
        <thead>
            <tr>
                <th>Élève</th>
                <th>Classe</th>
                <th>Niveau</th>
                <th>Total Attendu</th>
                <th>Total Payé</th>
                <th>Reste à Payer</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalAttendu = 0;
                $totalPaye = 0;
            @endphp

            @foreach($data as $eleve)
                @php
                    $totalAttendu += $eleve['total_attendu'];
                    $totalPaye += $eleve['total_paye'];
                @endphp
                <tr>
                    <td>{{ $eleve['eleve'] }}</td>
                    <td>{{ $eleve['classe'] }}</td>
                    <td>{{ $eleve['niveau'] }}</td>
                    <td class="text-right">{{ number_format($eleve['total_attendu'], 0, ',', ' ') }} FCFA</td>
                    <td class="text-right text-success">{{ number_format($eleve['total_paye'], 0, ',', ' ') }} FCFA</td>
                    <td class="text-right text-danger">{{ number_format($eleve['reste_total'], 0, ',', ' ') }} FCFA</td>
                </tr>
            @endforeach

            <tr class="total-row">
                <td colspan="3">TOTAL</td>
                <td class="text-right">{{ number_format($totalAttendu, 0, ',', ' ') }} FCFA</td>
                <td class="text-right text-success">{{ number_format($totalPaye, 0, ',', ' ') }} FCFA</td>
                <td class="text-right text-danger">{{ number_format($totalAttendu - $totalPaye, 0, ',', ' ') }} FCFA</td>
                <td colspan="2"></td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        École - {{ config('app.name') }} | Total: {{ count($data) }} élève(s) | Page 1 sur 1
    </div>
</body>
</html>
