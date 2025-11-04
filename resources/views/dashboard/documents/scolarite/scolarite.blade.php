<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reçu de Paiement</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 800px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
        }

        .header p {
            margin: 2px 0;
            font-size: 14px;
        }

        .info-table, .paiement-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .info-table td {
            padding: 5px 0;
        }

        .paiement-table th, .paiement-table td {
            border: 1px solid #333;
            padding: 8px;
            text-align: left;
        }

        .paiement-table th {
            background-color: #f2f2f2;
        }

        .total {
            margin-top: 10px;
            text-align: right;
            font-weight: bold;
        }

        .footer {
            text-align: center;
            border-top: 2px solid #333;
            padding-top: 10px;
            font-size: 12px;
            margin-top: 20px;
        }

        .highlight {
            font-weight: bold;
            color: #d9534f;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- En-tête école -->
        <div class="header">
            <h1>{{ $paiement->eleve->classe->ecole->nom ?? 'Nom de l\'École' }}</h1>
            <p>{{ $paiement->eleve->classe->ecole->adresse ?? 'Adresse de l\'École' }}</p>
            <p>Téléphone: {{ $paiement->eleve->classe->ecole->telephone ?? 'N/A' }} | Email: {{ $paiement->eleve->classe->ecole->email ?? 'N/A' }}</p>
            <h2>Reçu de Paiement</h2>
        </div>

        <!-- Infos élève et paiement -->
        <table class="info-table">
            <tr>
                <td><strong>Élève :</strong> {{ $paiement->eleve->nom_complet }}</td>
                <td><strong>Classe :</strong> {{ $paiement->eleve->classe->nom }}</td>
            </tr>
            <tr>
                <td><strong>Matricule :</strong> {{ $paiement->eleve->code_national ?? $paiement->eleve->matricule }}</td>
                <td><strong>Année Scolaire :</strong> {{ $paiement->anneeScolaire->annee }}</td>
            </tr>
            <tr>
                <td><strong>Date Paiement :</strong> {{ $paiement->created_at->format('d/m/Y') }}</td>
                <td><strong>Référence :</strong> {{ $paiement->reference ?? 'N/A' }}</td>
            </tr>
        </table>

        <!-- Tableau des détails -->
        <table class="paiement-table">
            <thead>
                <tr>
                    <th>Type de Frais</th>
                    <th>Mois</th>
                    <th>Montant</th>
                    <th>Mode de Paiement</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $paiement->typeFrais->nom }}</td>
                    <td>{{ $paiement->mois ? $paiement->mois->nom : 'N/A' }}</td>
                    <td>{{ number_format($paiement->montant, 0, ',', ' ') }} XOF</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $paiement->mode_paiement)) }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Total -->
        <div class="total">
            Total Payé: <span class="highlight">{{ number_format($paiement->montant, 0, ',', ' ') }} XOF</span>
        </div>

        <!-- Footer -->
        <div class="footer">
            Merci pour votre paiement.<br>
            Ce reçu fait foi de votre paiement auprès de l'école.
        </div>
    </div>
</body>
</html>
