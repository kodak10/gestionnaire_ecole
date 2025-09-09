<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>État de Scolarité - {{ $eleve->nom }} {{ $eleve->prenom }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #000;
            margin: 0;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        
        .header h1 {
            margin: 0;
            font-size: 18px;
            color: #333;
        }
        
        .header .subtitle {
            font-size: 14px;
            color: #666;
        }
        
        .info-section {
            margin-bottom: 20px;
        }
        
        .info-grid {
            width: 100%;
            border-collapse: collapse;
        }
        
        .info-grid td {
            padding: 5px;
            vertical-align: top;
        }
        
        .info-grid .label {
            font-weight: bold;
            width: 30%;
            color: #333;
        }
        
        .summary-section {
            margin: 20px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }
        
        .summary-grid {
            width: 100%;
            border-collapse: collapse;
        }
        
        .summary-grid td {
            padding: 8px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .summary-grid .total-row {
            font-weight: bold;
            background-color: #e9ecef;
        }
        
        .summary-grid .amount {
            text-align: right;
            font-family: 'Courier New', monospace;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .table th {
            background-color: #343a40;
            color: white;
            padding: 10px;
            text-align: left;
            border: 1px solid #454d55;
        }
        
        .table td {
            padding: 8px;
            border: 1px solid #dee2e6;
        }
        
        .table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-bold {
            font-weight: bold;
        }
        
        .text-danger {
            color: #dc3545;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #333;
            text-align: center;
            font-size: 11px;
            color: #666;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 15px;
            }
            
            .header {
                margin-bottom: 15px;
            }
            
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>ÉTAT DE SCOLARITÉ</h1>
        <div class="subtitle">Année Scolaire: {{ $anneeScolaire->annee }}</div>
    </div>
    
    <div class="info-section">
        <table class="info-grid">
            <tr>
                <td class="label">Élève:</td>
                <td>{{ $eleve->nom }} {{ $eleve->prenom }}</td>
                <td class="label">Matricule:</td>
                <td>{{ $eleve->matricule }}</td>
            </tr>
            <tr>
                <td class="label">Classe:</td>
                <td>{{ $eleve->classe->nom }}</td>
                <td class="label">Niveau:</td>
                <td>{{ $eleve->classe->niveau->nom }}</td>
            </tr>
            <tr>
                <td class="label">Date d'édition:</td>
                <td>{{ now()->format('d/m/Y H:i') }}</td>
                <td class="label">Référence:</td>
                <td>SCL-{{ $eleve->id }}-{{ $anneeScolaire->id }}</td>
            </tr>
        </table>
    </div>
    
    <div class="summary-section">
        <h3 style="margin-top: 0; color: #333;">Récapitulatif Scolarité</h3>
        <table class="summary-grid">
            <tr>
                <td>Total Scolarité:</td>
                <td class="amount">{{ number_format($summary['total_scolarite'], 0, ',', ' ') }} FCFA</td>
            </tr>
            <tr>
                <td>Total Payé:</td>
                <td class="amount">{{ number_format($summary['total_paye_scolarite'], 0, ',', ' ') }} FCFA</td>
            </tr>
            <tr>
                <td>Réduction:</td>
                <td class="amount">- {{ number_format($summary['reduction_scolarite'], 0, ',', ' ') }} FCFA</td>
            </tr>
            <tr class="total-row">
                <td>Reste à Payer:</td>
                <td class="amount text-danger">{{ number_format($summary['reste_payer_scolarite'], 0, ',', ' ') }} FCFA</td>
            </tr>
        </table>
    </div>
    
    <h3>Détail des Paiements</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Type de Frais</th>
                <th>Période</th>
                <th class="text-right">Montant Total</th>
                <th class="text-right">Montant Payé</th>
                <th class="text-right">Reste</th>
            </tr>
        </thead>
        <tbody>
            @foreach($paiements as $paiement)
            <tr>
                <td>{{ $paiement['type_frais'] }}</td>
                <td>{{ $paiement['mois_id'] ? 'Mensuel' : 'Annuel' }}</td>
                <td class="text-right">{{ number_format($paiement['montant_total'], 0, ',', ' ') }} FCFA</td>
                <td class="text-right">{{ number_format($paiement['montant_paye'], 0, ',', ' ') }} FCFA</td>
                <td class="text-right {{ $paiement['reste'] > 0 ? 'text-danger' : '' }}">
                    {{ number_format($paiement['reste'], 0, ',', ' ') }} FCFA
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <div class="footer">
        <p>Document généré le {{ now()->format('d/m/Y à H:i') }} | École Votre Établissement</p>
        <p>Ce document fait foi de situation de paiement pour l'année scolaire {{ $anneeScolaire->annee }}</p>
    </div>
</body>
</html>