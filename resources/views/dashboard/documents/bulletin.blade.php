<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Bulletins - {{ $classe->nom }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; margin: 20px; }
        .bulletin { margin-bottom: 40px; page-break-after: always; }
        .header { text-align: center; margin-bottom: 10px; }
        .header h2, .header h3 { margin: 0; }
        .eleve-info { margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table, th, td { border: 1px solid #000; }
        th, td { padding: 6px; text-align: left; }
        th { background: #ddd; }
        .footer { margin-top: 20px; display: flex; justify-content: space-between; }
        .footer div { border-top: 1px solid #000; width: 45%; text-align: center; padding-top: 5px; }
    </style>
</head>
<body>
@foreach($elevesAvecMoyennes as $eleveData)
<div class="bulletin">
    <div class="header">
        <h2>École: {{ auth()->user()->ecole->nom ?? 'Nom de l\'école' }}</h2>
        <h3>Classe: {{ $classe->nom }} - {{ $classe->niveau->nom }}</h3>
        <p>Période: {{ $mois->nom }} | Année scolaire: {{ auth()->user()->anneeScolaire->annee ?? 'N/A' }}</p>
    </div>

    <div class="eleve-info">
        <strong>Élève:</strong> {{ $eleveData['inscription']->eleve->prenom }} {{ $eleveData['inscription']->eleve->nom }}<br>
        <strong>Rang:</strong> {{ $eleveData['rang'] }}<br>
        <strong>Moyenne:</strong> {{ number_format($eleveData['moyenne'], 2) }}/20<br>
        <strong>Mention:</strong> {{ $eleveData['mention'] }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Matière</th>
                <th>Note</th>
                <th>Coeff</th>
                <th>Appréciation</th>
            </tr>
        </thead>
        <tbody>
            @foreach($eleveData['notes'] as $note)
            <tr>
                <td>{{ $note->matiere->nom }}</td>
                <td>{{ number_format($note->valeur, 2) }}</td>
                <td>{{ $note->coefficient }}</td>
                <td>{{ $note->appreciation ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <div>Observations du professeur principal</div>
        <div>Signature des parents</div>
    </div>
</div>
@endforeach
</body>
</html>
