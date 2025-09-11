<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Reçu de paiement</title>
    <style>
        body {
            margin: 0; 
            font-family: Arial, sans-serif;
            font-size: 14px;
        }
        .header {
            background-color: #00a5e4;
            color: white;
            padding: 15px 20px;
            position: relative;
            overflow: hidden;
        }
        .logo {
            background-color: #b14d36;
            width: 150px;
            height: 100px;
            float: left;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        .header h1 {
            margin: 0;
            font-size: 32px;
            font-weight: 700;
            text-align: center;
        }
        .phone {
            font-weight: 700;
            font-size: 14px;
            margin-top: 10px;
            float: left;
            clear: left;
        }
        .activity {
            font-weight: 700;
            font-size: 18px;
            text-align: center;
            margin-top: 5px;
        }
        .receipt-info {
            position: absolute;
            top: 20px;
            right: 20px;
            text-align: right;
            font-weight: 700;
        }
        .receipt-info .number {
            border-bottom: 1px solid white;
            padding-bottom: 2px;
        }
        .receipt-info .date {
            margin-top: 15px;
        }
        .blue-bar {
            background-color: #1b71bc;
            height: 8px;
            clear: both;
        }
        .content {
            padding: 25px 20px;
            background-color: white;
            font-weight: 700;
            font-size: 16px;
        }
        .content p {
            margin: 18px 0;
            border-bottom: 1px dotted #000;
            padding-bottom: 5px;
        }
        .footer {
            background-color: #00a5e4;
            height: 20px;
        }
        .signature {
            margin-top: 40px;
            text-align: right;
            padding-right: 50px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">LOGO ECOLE</div>
        <div class="phone">0102030405 / 0203040505</div>
        <h1>REÇU DE PAIEMENT</h1>
        <div class="activity">{{ strtoupper($paiement->typeFrais->nom) }}</div>
        <div class="receipt-info">
            <div class="number">Reçu N° : {{ $paiement->id }}</div>
            <div class="date">Date : {{ $paiement->created_at->format('d/m/Y') }}</div>
        </div>
    </div>
    <div class="blue-bar"></div>

    <div class="content">
        <p>MATRICULE : {{ $eleve->matricule }} — NOM : {{ $eleve->prenom }} {{ $eleve->nom }}</p>
        <p>CLASSE : {{ $classe->nom }}</p>
        <p>Libellé : {{ $paiement->typeFrais->nom }}</p>
        <p>
            MONTANT VERSÉ : 
            {{ number_format($paiement->details->sum('montant'), 0, ',', ' ') }} FCFA
        </p>
        <p>MODE DE PAIEMENT : {{ strtoupper($paiement->mode_paiement) }}</p>
        @if($paiement->reference)
            <p>RÉFÉRENCE : {{ $paiement->reference }}</p>
        @endif
        <p>MONTANT TOTAL ATTENDU : {{ number_format($montant_total, 0, ',', ' ') }} FCFA</p>
        <p>RESTE À PAYER : {{ number_format($reste_a_payer, 0, ',', ' ') }} FCFA</p>
        <p>Encaissé par : {{ $paiement->user->name }}</p>
        
        <div class="signature">
            <p>Signature</p>
        </div>
    </div>

    <div class="footer"></div>
</body>
</html>
