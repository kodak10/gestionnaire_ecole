<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Relance des Paiements</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .header { text-align: center; margin-bottom: 20px; }
        .filters { margin-bottom: 20px; }
        .filters p { margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .total-row { font-weight: bold; background-color: #e9ecef; }
        .text-right { text-align: right; }
        .text-success { color: #28a745; }
        .text-danger { color: #dc3545; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Relance des Paiements</h1>
        <p>Généré le: {{ $date }}</p>
    </div>

    <div class="filters">
        <h3>Filtres appliqués:</h3>
        @foreach($filters as $key => $value)
            <p><strong>{{ ucfirst($key) }}:</strong> {{ $value }}</p>
        @endforeach
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
                <th>Statut</th>
                <th>En Retard Depuis</th>
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
                    <td class="text-right text-danger">{{ number_format($eleve['reste_a_payer'], 0, ',', ' ') }} FCFA</td>
                    <td>{{ $eleve['statut'] }}</td>
                    <td>{{ $eleve['en_retard_depuis'] ?? 'N/A' }}</td>
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
</body>
</html>