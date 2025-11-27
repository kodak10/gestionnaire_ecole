<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Tableau d'Honneur Mensuel</title>
    <style>
        /* Polices compatibles avec DomPDF */
        @font-face {
            font-family: 'DejaVu Sans';
            src: url('{{ storage_path('fonts/dejavu-sans.ttf') }}') format('truetype');
        }
        
        @font-face {
            font-family: 'DejaVu Serif';
            src: url('{{ storage_path('fonts/dejavu-serif.ttf') }}') format('truetype');
        }

        /* Reset et base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'DejaVu Sans', Arial, sans-serif;
            background: #f0f0f0;
        }

        /* Conteneur principal compact */
        .certificate-container {
            width: 280mm;
            height: 180mm;
            margin: 5mm auto;
            background: linear-gradient(135deg, #618597 0%, #4a6b7a 100%);
            position: relative;
            border: 2mm solid #2c3e50;
            box-shadow: 0 4mm 8mm rgba(0,0,0,0.3);
        }

        /* Bordures décoratives */
        .border-outer {
            position: absolute;
            top: 5mm;
            left: 5mm;
            right: 5mm;
            bottom: 5mm;
            border: 1mm solid #fff;
        }

        .border-inner {
            position: absolute;
            top: 10mm;
            left: 10mm;
            right: 10mm;
            bottom: 10mm;
            border: 0.5mm solid #fff;
        }

        /* Contenu principal */
        .certificate-content {
            position: absolute;
            top: 15mm;
            left: 15mm;
            right: 15mm;
            bottom: 15mm;
            background: white;
            padding: 8mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
        }

        /* En-tête */
        .header {
            text-align: center;
            margin-bottom: 4mm;
        }

        .title {
            font-family: 'DejaVu Serif', 'Times New Roman', serif;
            font-size: 9mm;
            color: #2c3e50;
            margin-bottom: 2mm;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .subtitle {
            font-size: 4mm;
            color: #7f8c8d;
            margin-bottom: 4mm;
        }

        /* Section photo et nom */
        .student-section {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8mm;
            margin: 3mm 0;
            width: 100%;
        }

        .photo-container {
            flex-shrink: 0;
            position: relative;
        }

        .student-photo {
            width: 35mm;
            height: 35mm;
            border-radius: 50%;
            border: 1mm solid #618597;
            object-fit: cover;
            background: #f8f9fa;
        }

        .rank-badge {
            position: absolute;
            top: -3mm;
            right: -3mm;
            background: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 12mm;
            height: 12mm;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4mm;
            font-weight: bold;
            border: 1mm solid white;
            box-shadow: 0 1mm 2mm rgba(0,0,0,0.3);
        }

        .student-info {
            flex: 1;
            text-align: center;
        }

        .student-name {
            font-size: 7mm;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 2mm;
            text-transform: uppercase;
        }

        .student-class {
            font-size: 4mm;
            color: #7f8c8d;
            margin-bottom: 1mm;
        }

        .student-average {
            font-size: 5mm;
            color: #e74c3c;
            font-weight: bold;
        }

        /* Message de félicitations */
        .message-section {
            text-align: center;
            margin: 3mm 0;
            padding: 0 5mm;
        }

        .message {
            font-size: 3.5mm;
            line-height: 1.4;
            color: #2c3e50;
            text-align: justify;
        }

        .school-name {
            font-weight: bold;
            color: #618597;
        }

        .period {
            font-weight: bold;
            color: #e74c3c;
        }

        /* Signatures */
        .signatures-section {
            display: flex;
            justify-content: space-between;
            width: 100%;
            margin-top: 4mm;
            padding: 0 10mm;
        }

        .signature-block {
            text-align: center;
            flex: 1;
        }

        .signature-title {
            font-size: 3.5mm;
            color: #7f8c8d;
            margin-bottom: 1mm;
        }

        .signature-line {
            width: 40mm;
            height: 0.5mm;
            background: #2c3e50;
            margin: 2mm auto;
        }

        .signature-name {
            font-size: 3.5mm;
            font-weight: bold;
            color: #2c3e50;
        }

        /* Date */
        .date-section {
            text-align: center;
            margin-top: 3mm;
            font-size: 3.2mm;
            color: #7f8c8d;
            font-style: italic;
        }

        /* Styles d'impression */
        @media print {
            body {
                background: white;
                margin: 0;
                padding: 0;
            }
            
            .certificate-container {
                box-shadow: none;
                border: none;
                margin: 0;
                width: 100%;
                height: 100%;
                page-break-after: always;
            }
        }

        /* Fallback pour photo manquante */
        .photo-placeholder {
            width: 35mm;
            height: 35mm;
            border-radius: 50%;
            background: #ecf0f1;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1mm solid #bdc3c7;
            color: #7f8c8d;
            font-size: 3mm;
            text-align: center;
        }

        /* Informations de période */
        .period-info {
            text-align: center;
            margin-bottom: 3mm;
            font-size: 4mm;
            color: #7f8c8d;
        }
    </style>
</head>
<body>
    @foreach($meilleursEleves as $index => $eleve)
    <div class="certificate-container">
        <div class="border-outer"></div>
        <div class="border-inner"></div>
        
        <div class="certificate-content">
            <!-- En-tête -->
            <div class="header">
                <div class="title">Tableau d'Honneur</div>
                <div class="subtitle">Mensuel - Classement des meilleurs élèves</div>
            </div>

            <!-- Informations de période -->
            <div class="period-info">
                @if($classe)
                    Classe : <strong>{{ $classe->nom }}</strong> | 
                @endif
                Mois : <strong>{{ $mois->nom }}</strong> | 
                Année : <strong>{{ $anneeScolaire->annee_debut }}-{{ $anneeScolaire->annee_fin }}</strong>
            </div>

            <!-- Section élève -->
            <div class="student-section">
                <div class="photo-container">
                    @if($eleve['inscription']->eleve->photo_path && file_exists(storage_path('app/public/' . $eleve['inscription']->eleve->photo_path)))
                        <img src="{{ storage_path('app/public/' . $eleve['inscription']->eleve->photo_path) }}" class="student-photo">
                    @else
                        <div class="photo-placeholder">
                            PHOTO<br>NON<br>DISPONIBLE
                        </div>
                    @endif
                    <div class="rank-badge">
                        {{ $index + 1 }}ère
                    </div>
                </div>
                
                <div class="student-info">
                    <div class="student-name">
                        {{ strtoupper($eleve['inscription']->eleve->nom) }} {{ $eleve['inscription']->eleve->prenom }}
                    </div>
                    <div class="student-class">
                        Élève en classe de {{ $eleve['inscription']->classe->nom }}
                    </div>
                    <div class="student-average">
                        Moyenne : {{ $eleve['moyenne'] }}/{{ $eleve['moy_base'] }}
                    </div>
                </div>
            </div>

            <!-- Message -->
            <div class="message-section">
                <div class="message">
                    L'Établissement <span class="school-name">{{ $ecole->nom_ecole ?? 'GS EXCELLE' }}</span> est heureux de décerner ce diplôme d'excellence et félicite 
                    <strong>{{ $eleve['inscription']->eleve->prenom }}</strong> pour ses efforts exceptionnels durant 
                    <span class="period">le mois de {{ $mois->nom }}</span>. 
                    Sa persévérance et ses résultats remarquables lui valent la 
                    <strong>{{ $index + 1 }}ère place</strong> dans le Tableau d'Honneur mensuel de l'établissement.
                </div>
            </div>

            <!-- Signatures -->
            <div class="signatures-section">
                <div class="signature-block">
                    <div class="signature-title">Le Directeur</div>
                    <div class="signature-line"></div>
                    <div class="signature-name">{{ $ecole->directeur ?? 'Le Directeur' }}</div>
                </div>
                
                <div class="signature-block">
                    <div class="signature-title">Le Professeur Principal</div>
                    <div class="signature-line"></div>
                    <div class="signature-name">{{ $eleve['inscription']->classe->professeur_principal ?? 'Professeur Principal' }}</div>
                </div>
            </div>

            <!-- Date -->
            <div class="date-section">
                Fait à Abidjan, le {{ date('d/m/Y') }}
            </div>
        </div>
    </div>
    @endforeach
</body>
</html>