<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reçu de Paiement - Scolarité</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        .receipt { max-width: 400px; margin: 0 auto; border: 1px solid #ddd; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; }
        .school-name { font-size: 18px; font-weight: bold; }
        .receipt-title { font-size: 16px; margin-top: 10px; }
        .details { margin: 15px 0; }
        .detail-row { display: flex; margin: 5px 0; }
        .detail-label { font-weight: bold; width: 120px; }
        .footer { margin-top: 30px; text-align: center; font-size: 12px; }
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="header">
            <div class="school-name">Nom de l'Établissement</div>
            <div class="receipt-title">REÇU DE PAIEMENT DE SCOLARITÉ</div>
        </div>
        
        <div class="details">
            <div class="detail-row">
                <div class="detail-label">Référence:</div>
                <div>#{{ str_pad($paiement->id, 6, '0', STR_PAD_LEFT) }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Date:</div>
                <div></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Élève:</div>
                <div>{{ $paiement->eleve->nom }} {{ $paiement->eleve->prenom }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Matricule:</div>
                <div>{{ $paiement->eleve->matricule }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Classe:</div>
                <div>{{ $paiement->eleve->classe->nom }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Année Scolaire:</div>
                <div>{{ $paiement->anneeScolaire->annee }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Mode de Paiement:</div>
                <div>
                    @if($paiement->mode_paiement == 'especes') Espèces
                    @elseif($paiement->mode_paiement == 'cheque') Chèque
                    @elseif($paiement->mode_paiement == 'virement') Virement
                    @else Mobile Money
                    @endif
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Montant:</div>
                <div><strong>{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</strong></div>
            </div>
            <div class="detail-row">
    <div class="detail-label">Reste à payer:</div>
    <div><strong>{{ number_format($paiement->reste_a_payer, 0, ',', ' ') }} FCFA</strong></div>
</div>

<div class="detail-row">
    <div class="detail-label">Total payé:</div>
    <div><strong>{{ number_format($paiement->total_paye, 0, ',', ' ') }} FCFA</strong></div>
</div>


            
        </div>
        
        <div class="footer">
            <div>Merci pour votre confiance</div>
            <div>Reçu émis le: {{ date('d/m/Y à H:i') }}</div>
        </div>
        
        <div class="no-print" style="margin-top: 20px; text-align: center;">
            <button onclick="window.print()" style="padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer;">
                Imprimer le reçu
            </button>
            <button onclick="window.close()" style="padding: 10px 20px; background: #6c757d; color: white; border: none; cursor: pointer; margin-left: 10px;">
                Fermer
            </button>
        </div>
    </div>
</body>
</html>