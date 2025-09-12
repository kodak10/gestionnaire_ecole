<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Reçu de Paiement - {{ $ecole->nom }}</title>
    <style>
        @page {
            margin: 0;
            size: A4;
        }
        
        body {
            margin: 0;
            padding: 0;
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            background-color: #fff;
        }
        
        .receipt-container {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 15mm;
            box-sizing: border-box;
            position: relative;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 3px double #2c5282;
            padding-bottom: 15px;
        }
        
        .school-name {
            font-size: 22px;
            font-weight: bold;
            color: #2c5282;
            margin: 0;
            text-transform: uppercase;
        }
        
        .school-address {
            font-size: 12px;
            color: #666;
            margin: 5px 0;
        }
        
        .school-contact {
            font-size: 11px;
            color: #666;
            margin: 3px 0;
        }
        
        .receipt-title {
            font-size: 18px;
            font-weight: bold;
            color: #2c5282;
            text-align: center;
            margin: 15px 0;
            text-transform: uppercase;
        }
        
        .receipt-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
        }
        
        .info-group {
            flex: 1;
        }
        
        .info-label {
            font-weight: bold;
            color: #4a5568;
            font-size: 11px;
            text-transform: uppercase;
        }
        
        .info-value {
            font-size: 12px;
            color: #2d3748;
        }
        
        .student-info {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
        }
        
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #2c5282;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .payment-details {
            margin-bottom: 20px;
        }
        
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        .details-table th {
            background-color: #2c5282;
            color: white;
            padding: 8px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
        }
        
        .details-table td {
            padding: 8px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .details-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        
        .amounts {
            margin-top: 20px;
            padding: 15px;
            background-color: #f0fff4;
            border: 1px solid #c6f6d5;
            border-radius: 8px;
        }
        
        .amount-row {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
            padding: 5px 0;
        }
        
        .amount-label {
            font-weight: bold;
            color: #2d3748;
        }
        
        .amount-value {
            font-weight: bold;
            color: #2d3748;
        }
        
        .total-row {
            border-top: 2px solid #2c5282;
            padding-top: 10px;
            margin-top: 10px;
            font-size: 14px;
        }
        
        .signature-area {
            margin-top: 40px;
            text-align: right;
        }
        
        .signature-line {
            border-top: 1px solid #2c5282;
            width: 200px;
            margin-left: auto;
            margin-top: 40px;
            padding-top: 5px;
            text-align: center;
            font-size: 11px;
            color: #666;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
        }
        
        .watermark {
            position: absolute;
            opacity: 0.1;
            font-size: 80px;
            color: #2c5282;
            transform: rotate(-45deg);
            top: 40%;
            left: 10%;
            z-index: -1;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            
            .receipt-container {
                width: 100%;
                height: 100%;
                padding: 15mm;
                box-shadow: none;
            }
            
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="watermark">{{ $ecole->nom }}</div>
        
        <!-- En-tête -->
        <div class="header">
            <h1 class="school-name">{{ $ecole->nom }}</h1>
            <div class="school-address">{{ $ecole->adresse }}</div>
            <div class="school-contact">
                Tél: {{ $ecole->telephone }} | Email: {{ $ecole->email }}
            </div>
        </div>
        
        <!-- Titre du reçu -->
        <div class="receipt-title">REÇU DE PAIEMENT</div>
        
        <!-- Informations du reçu -->
        <div class="receipt-info">
            <div class="info-group">
                <div class="info-label">N° Reçu</div>
                <div class="info-value">#{{ str_pad($paiement->id, 6, '0', STR_PAD_LEFT) }}</div>
            </div>
            <div class="info-group">
                <div class="info-label">Date</div>
                <div class="info-value">{{ $paiement->created_at->format('d/m/Y à H:i') }}</div>
            </div>
            <div class="info-group">
                <div class="info-label">Mode de Paiement</div>
                <div class="info-value">
                    @switch($paiement->mode_paiement)
                        @case('especes') Espèces @break
                        @case('cheque') Chèque @break
                        @case('virement') Virement @break
                        @case('mobile_money') Mobile Money @break
                        @default {{ $paiement->mode_paiement }}
                    @endswitch
                </div>
            </div>
        </div>
        
        <!-- Informations de l'élève -->
        <div class="student-info">
            <div class="section-title">INFORMATIONS DE L'ÉLÈVE</div>
            <div class="amount-row">
                <span class="amount-label">Nom complet:</span>
                <span class="amount-value">{{ $eleve->prenom }} {{ $eleve->nom }}</span>
            </div>
            <div class="amount-row">
                <span class="amount-label">Matricule:</span>
                <span class="amount-value">{{ $eleve->matricule }}</span>
            </div>
            <div class="amount-row">
                <span class="amount-label">Classe:</span>
                <span class="amount-value">{{ $classe->nom }}</span>
            </div>
        </div>
        
        <!-- Détails du paiement -->
        <div class="payment-details">
            <div class="section-title">DÉTAILS DU PAIEMENT</div>
            <table class="details-table">
                <thead>
                    <tr>
                        <th>Type de Frais</th>
                        <th>Montant Payé</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($paiement->details as $detail)
                    <tr>
                        <td>{{ $detail->typeFrais->nom }}</td>
                        <td>{{ number_format($detail->montant, 0, ',', ' ') }} FCFA</td>
                        <td>{{ $detail->created_at->format('d/m/Y') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <!-- Montants -->
        <div class="amounts">
            <div class="section-title">RÉCAPITULATIF FINANCIER</div>
            
            <div class="amount-row">
                <span class="amount-label">Total payé:</span>
                <span class="amount-value">{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</span>
            </div>
            
            <div class="amount-row">
                <span class="amount-label">Montant total attendu:</span>
                <span class="amount-value">{{ number_format($montant_total, 0, ',', ' ') }} FCFA</span>
            </div>
            
            <div class="amount-row">
                <span class="amount-label">Total déjà payé:</span>
                <span class="amount-value">{{ number_format($montant_total - $reste_a_payer, 0, ',', ' ') }} FCFA</span>
            </div>
            
            <div class="amount-row total-row">
                <span class="amount-label">Reste à payer:</span>
                <span class="amount-value" style="color: #e53e3e;">{{ number_format($reste_a_payer, 0, ',', ' ') }} FCFA</span>
            </div>
            
            @if($paiement->reference)
            <div class="amount-row">
                <span class="amount-label">Référence:</span>
                <span class="amount-value">{{ $paiement->reference }}</span>
            </div>
            @endif
        </div>
        
        <!-- Signature -->
        <div class="signature-area">
            <div class="signature-line">
                Signature et cachet
            </div>
            <div style="font-size: 11px; color: #666; margin-top: 5px;">
                Encaissé par: {{ $paiement->user->name }}
            </div>
        </div>
        
        <!-- Pied de page -->
        <div class="footer">
            <p>Ce reçu est généré automatiquement le {{ now()->format('d/m/Y à H:i') }}</p>
            <p>{{ $ecole->nom }} - {{ $ecole->adresse }} - {{ $ecole->telephone }}</p>
            <p>Merci pour votre confiance</p>
        </div>
    </div>
</body>
</html>