<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reçu de Paiement</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 0px;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 0;
        }

        .table-container {
            display: table;
            width: 100%;
            margin: 0;
            padding: 0;
            border-collapse: collapse;
        }

        .table-row {
            display: table-row;
        }

        .receipt.bureau {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 15px;
            box-sizing: border-box;
            border: 2px solid #f6b60a;
            border-right: 1px dashed #ccc;
            background-color: #fffde7;
        }

        .receipt.client {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 15px;
            box-sizing: border-box;
            border: 2px solid #0f2740;
            background-color: #ffffff;
        }

        .header, .footer, .inline-fields, .field, .payment-methods {
            margin-bottom: 8px;
        }

        .field label {
            font-weight: bold;
            display: block;
            margin-bottom: 3px;
            font-size: 11px;
        }

        .field span, .inline-fields .item span {
            display: block;
            border-bottom: 1px solid #000;
            padding: 2px 0;
            min-height: 16px;
            font-size: 11px;
        }

        .inline-fields {
            display: table;
            width: 100%;
        }

        .inline-fields .item {
            display: table-cell;
            width: 33%;
            padding-right: 10px;
            box-sizing: border-box;
        }

        .inline-fields .item label {
            font-size: 11px;
            font-weight: bold;
            display: block;
            margin-bottom: 2px;
        }

        .inline-fields .item span {
            border-bottom: 1px solid #000;
            padding: 2px 0;
            min-height: 16px;
            display: block;
            font-size: 11px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
            font-size: 11px;
        }

        th, td {
            border: 1px solid #000;
            padding: 4px;
            text-align: left;
        }

        th {
            background-color: #f5f5f5;
            font-weight: bold;
        }

        .footer {
            font-size: 10px;
            display: table;
            width: 100%;
            margin-top: 10px;
        }

        .footer div {
            display: table-cell;
        }

        .signature {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }

        .text-right {
            text-align: right;
        }

        .border-none {
            border: none !important;
        }

        .mt-0 {
            margin-top: 0 !important;
        }

        .mt-5 {
            margin-top: 5px;
        }

        .total-row {
            background-color: #f9f9f9;
            font-weight: bold;
        }

        .total-row td {
            font-weight: bold;
        }

        .montant-paye {
            color: #28a745;
            font-weight: bold;
        }

        .reste-a-payer {
            color: #dc3545;
            font-weight: bold;
        }

        .header-logo {
            height: 45px;
            width: auto;
        }

        .badge-duplicata {
            background: #ff0000;
            color: #fff;
            padding: 2px 8px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 10px;
        }

        @media print {
            .receipt.bureau {
                border: 2px solid #f6b60a;
                border-right: 1px dashed #ccc;
            }
            .receipt.client {
                border: 2px solid #0f2740;
            }
        }
    </style>
</head>
<body>
    @php
        // Récupérer les données depuis les tableaux
        $ecoleNom = $ecoleData['nom_ecole'];
        $ecoleTelephone = $ecoleData['telephone'];
        $ecoleAdresse = $ecoleData['adresse'];
        $ecoleLogo = $ecoleData['logo'];
        
        // Logo path
        $logoPath = 'assets/img/logo_excelle.jpg';
        if (!empty($ecoleLogo) && file_exists(public_path($ecoleLogo))) {
            $logoPath = $ecoleLogo;
        } elseif (!empty($ecoleLogo) && file_exists(public_path('storage/' . $ecoleLogo))) {
            $logoPath = 'storage/' . $ecoleLogo;
        }
        
        // Paiement
        $paiementId = str_pad($paiementData['id'], 6, '0', STR_PAD_LEFT);
        $paiementDate = date('d/m/Y H:i', strtotime($paiementData['created_at']));
        $paiementMode = $paiementData['mode_paiement'];
        $userName = $paiementData['user_name'];
        
        // Mode paiement formaté
        $modes = [
            'especes' => 'Espèces',
            'cheque' => 'Chèque',
            'virement' => 'Virement',
            'mobile_money' => 'Mobile Money',
            'carte' => 'Carte'
        ];
        $paiementModeLabel = $modes[$paiementMode];
        
        // Élève
        $eleveMatricule = $eleveData['code_national'];
        $eleveNom = $eleveData['nom'] . ' ' . $eleveData['prenom'];
        
        // Classe
        $classeNom = $classeData['nom'];
        
        // Types de frais
        $typesFrais = [];
        foreach($detailsData as $detail) {
            $typesFrais[] = $detail['tarif_libelle'];
        }
        $typesFraisString = implode(' + ', array_unique($typesFrais));
    @endphp

    <div class="table-container">
        <div class="table-row">
            <!-- Copie Bureau -->
            <div class="receipt bureau">
                <div class="header">
                    <table width="100%" style="margin-top: 0px !important">
                        <tr>
                            <td style="width: 60%; vertical-align: top; border:none !important">
                                <div style="text-align: left;">
                                    <img src="{{ $logoPath }}" alt="Logo" style="height: 45px;"><br>
                                    <span style="font-size: 10px;" class="bold">Téléphone: {{ $ecoleTelephone }}</span>
                                </div>
                            </td>
                            <td style="width: 40%; text-align: right; vertical-align: top; font-size: 10px; border:none !important">
                                <div><strong>DATE:</strong> {{ $paiementDate }}</div>
                                <div><strong>RECU N°:</strong> {{ $paiementId }}</div>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="field">
                    <label>NOM & PRENOMS</label>
                    <span>{{ $eleveNom }}</span>
                </div>

                <div class="inline-fields">
                    <div class="item">
                        <label class="bold">MATRICULE</label>
                        <span>{{ $eleveMatricule }}</span>
                    </div>
                    <div class="item">
                        <label class="bold">CLASSE</label>
                        <span>{{ $classeNom }}</span>
                    </div>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Libellé</th>
                            <th>Montant</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($detailsData as $detail)
                        <tr>
                            <td>{{ $detail['tarif_libelle'] }}</td>
                            <td>{{ number_format($detail['montant'], 0, ',', ' ') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="inline-fields" style="margin-top: 5px;">
                    <div class="item">
                        <label class="bold">Total versé</label>
                        <span>{{ number_format($montant_total, 0, ',', ' ') }} FCFA</span>
                    </div>
                    <div class="item">
                        <label class="bold">Reste à payer</label>
                        <span class="reste-a-payer">{{ number_format($reste_total, 0, ',', ' ') }} FCFA</span>
                    </div>
                </div>

                <div class="payment-methods">
                    <label class="bold">Mode de paiement:</label>
                    <span>{{ $paiementModeLabel }}</span>
                </div>

                <div class="footer">
                    <div class="bold">Encaissé par:</div>
                    <div class="signature">{{ $userName }}</div>
                </div>
            </div>

            <!-- Copie Client (DUPLICATA) -->
            <div class="receipt client">
                <div class="header">
                    <table width="100%" style="margin-top: 0px !important">
                        <tr>
                            <td style="width: 60%; vertical-align: top; border:none !important">
                                <div style="text-align: left;">
                                    <img src="{{ $logoPath }}" alt="Logo" style="height: 45px;"><br>
                                    <span style="font-size: 10px;" class="bold">Téléphone: {{ $ecoleTelephone }}</span>
                                </div>
                            </td>
                            <td style="width: 40%; text-align: right; vertical-align: top; font-size: 10px; border:none !important">
                                <div><span class="badge-duplicata">DUPLICATA</span></div>
                                <div><strong>DATE:</strong> {{ $paiementDate }}</div>
                                <div><strong>RECU N°:</strong> {{ $paiementId }}</div>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="field">
                    <label>NOM & PRENOMS</label>
                    <span>{{ $eleveNom }}</span>
                </div>

                <div class="inline-fields">
                    <div class="item">
                        <label class="bold">MATRICULE</label>
                        <span>{{ $eleveMatricule }}</span>
                    </div>
                    <div class="item">
                        <label class="bold">CLASSE</label>
                        <span>{{ $classeNom }}</span>
                    </div>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Libellé</th>
                            <th>Montant</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($detailsData as $detail)
                        <tr>
                            <td>{{ $detail['tarif_libelle'] }}</td>
                            <td>{{ number_format($detail['montant'], 0, ',', ' ') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="inline-fields" style="margin-top: 5px;">
                    <div class="item">
                        <label class="bold">Total versé</label>
                        <span>{{ number_format($montant_total, 0, ',', ' ') }} FCFA</span>
                    </div>
                    <div class="item">
                        <label class="bold">Reste à payer</label>
                        <span class="reste-a-payer">{{ number_format($reste_total, 0, ',', ' ') }} FCFA</span>
                    </div>
                </div>

                <div class="payment-methods">
                    <label class="bold">Mode de paiement:</label>
                    <span>{{ $paiementModeLabel }}</span>
                </div>

                <div class="footer">
                    <div class="bold">Encaissé par:</div>
                    <div class="signature">{{ $userName }}</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>