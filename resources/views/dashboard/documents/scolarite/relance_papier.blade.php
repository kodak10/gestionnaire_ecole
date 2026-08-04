<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Relance des Paiements - {{ $classe_nom }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #000000;
            line-height: 1.5;
            margin: 0;
            padding: 10px;
        }

        .relance-container {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }

        .relance-container:last-child {
            margin-bottom: 0;
        }

        /* En-tête simple centré */
        .relance-header {
            text-align: center;
            font-weight: bold;
            font-size: 13px;
            color: #333;
            padding: 6px 0;
            margin-bottom: 2px;
            border-bottom: 1px solid #ddd;
            background: #f9f9f9;
            border-radius: 4px 4px 0 0;
        }

        .relance-header span {
            margin: 0 10px;
        }

        .message-relance {
            background: #fff;
            padding: 15px 15px 15px 20px;
            border-left: 4px solid #dc3545;
            font-family: monospace;
            font-size: 13px;
            line-height: 1.8;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .message-relance strong {
            font-weight: bold;
            color: #000;
        }

        /* Séparateur en pointillés sous chaque message */
        .separator {
            border-bottom: 2px dashed #cccccc;
            margin-top: 15px;
            padding-top: 5px;
            width: 100%;
        }

        /* Supprimer le séparateur après le dernier message */
        .relance-container:last-child .separator {
            display: none;
        }

        @media print {
            .relance-container {
                margin-bottom: 20px;
                page-break-inside: avoid;
            }
            
            .separator {
                border-bottom: 2px dashed #aaaaaa;
            }
        }
    </style>
</head>
<body>
    @php
        $ecoleNom = $ecoleData['nom_ecole'];
        $ecoleTelephone = $ecoleData['telephone'];
        $ecoleAdresse = $ecoleData['adresse'];
        $ecoleEmail = $ecoleData['email'];
        $ecoleLogo = $ecoleData['logo'];
        
        $logoPath = 'assets/img/logo_excelle.jpg';
        if (!empty($ecoleLogo) && file_exists(public_path($ecoleLogo))) {
            $logoPath = $ecoleLogo;
        } elseif (!empty($ecoleLogo) && file_exists(public_path('storage/' . $ecoleLogo))) {
            $logoPath = 'storage/' . $ecoleLogo;
        }

        $templateContent = trim($template_content ?? '');
    @endphp

    @if(!empty($elevesData))
        @foreach($elevesData as $index => $eleve)
            @php
                $typeFrais = $eleve['type_tarif'] ?? 'Frais';
                $moisRelance = $mois_reference ?? 'Mois';

                $replaceData = [
                    'NOM' => '<strong>' . trim($eleve['nom'] ?? '') . '</strong>',
                    'PRENOM' => '<strong>' . trim($eleve['prenom'] ?? '') . '</strong>',
                    'NOM_RESPONSABLE' => '<strong>' . trim($eleve['parent_nom'] ?? '') . '</strong>',
                    'PRENOM_RESPONSABLE' => '<strong>' . trim($eleve['parent_prenom'] ?? '') . '</strong>',
                    'MATRICULE' => '<strong>' . trim($eleve['matricule'] ?? '') . '</strong>',
                    'CLASSE' => '<strong>' . trim($eleve['classe'] ?? '') . '</strong>',
                    'TYPE_FRAIS' => '<strong>' . trim($eleve['type_tarif'] ?? '') . '</strong>',
                    'MOIS' => '<strong>' . trim($mois_reference ?? '') . '</strong>',
                    'MOIS_CONCERNE' => '<strong>' . trim($mois_reference ?? '') . '</strong>',
                    'MONTANT_ATTENDU' => '<strong>' . number_format($eleve['montant_mois'] ?? 0, 0, ',', ' ') . ' FCFA</strong>',
                    'MONTANT_PAYE' => '<strong>' . number_format($eleve['total_paye'] ?? 0, 0, ',', ' ') . ' FCFA</strong>',
                    'RESTE_MOIS' => '<strong>' . number_format($eleve['reste_mois'] ?? 0, 0, ',', ' ') . ' FCFA</strong>',
                    'RESTE_TOTAL' => '<strong>' . number_format($eleve['reste_cumul'] ?? 0, 0, ',', ' ') . ' FCFA</strong>',
                    'MONTANT_DU' => '<strong>' . number_format($eleve['reste_cumul'] ?? 0, 0, ',', ' ') . ' FCFA</strong>',
                    'DATE_ECHEANCE' => '<strong>' . now()->addDays(5)->format('d/m/Y') . '</strong>',
                    'RETARD' => '<strong>' . (($eleve['reste_cumul'] ?? 0) > 0 ? '5' : '0') . ' jours</strong>',
                    'ECOLE' => '<strong>' . trim($ecoleNom) . '</strong>',
                    'ECOLE_ADRESSE' => '<strong>' . trim($ecoleAdresse) . '</strong>',
                    'ECOLE_TELEPHONE' => '<strong>' . trim($ecoleTelephone) . '</strong>',
                    'ECOLE_EMAIL' => '<strong>' . trim($ecoleEmail) . '</strong>',
                    'DATE' => '<strong>' . now()->format('d/m/Y') . '</strong>',
                    'DATE_FR' => '<strong>' . now()->locale('fr')->translatedFormat('d F Y') . '</strong>',
                    'ANNEE' => '<strong>' . trim($annee_scolaire ?? '') . '</strong>',
                    'NOMBRE_RELANCE' => '<strong>' . $loop->iteration . '</strong>',
                    'DELAI' => '<strong>5 jours</strong>',
                    'SANCTION' => '<strong>Suspension des cours</strong>',
                ];

                $message = trim($templateContent);
                foreach ($replaceData as $key => $value) {
                    $message = str_replace("%{$key}%", $value, $message);
                }
                $message = preg_replace('/%[^%]+%/', '', $message);
                $message = trim($message);
            @endphp

            <div class="relance-container">
                <!-- En-tête simple centré -->
                <div class="relance-header">
                    <span>RELANCE</span>
                    <span>|</span>
                    <span>{{ $typeFrais }}</span>
                    <span>|</span>
                    <span>{{ $moisRelance }}</span>
                </div>

                <div class="message-relance">{!! $message !!}</div>
                <div class="separator"></div>
            </div>
        @endforeach
    @endif
</body>
</html>