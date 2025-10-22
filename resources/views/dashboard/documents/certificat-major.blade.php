<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Certificat de Major - {{ $major['inscription']->eleve->nom }}</title>
    <style>
        @page { margin: 0; }
        body { 
            font-family: 'DejaVu Sans', serif; 
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .certificate-container {
            width: 21cm;
            height: 29.7cm;
            background: white;
            border: 20px solid #8B4513;
            box-shadow: 0 0 30px rgba(0,0,0,0.3);
            position: relative;
            text-align: center;
            padding: 50px;
        }
        .border-design {
            border: 15px double #D4AF37;
            height: calc(100% - 30px);
            padding: 40px;
            position: relative;
        }
        .gold-line {
            border-top: 3px solid #D4AF37;
            margin: 20px 0;
        }
        .title {
            font-size: 48px;
            font-weight: bold;
            color: #8B4513;
            margin: 30px 0 10px 0;
            text-transform: uppercase;
            letter-spacing: 3px;
        }
        .subtitle {
            font-size: 24px;
            color: #D4AF37;
            margin-bottom: 40px;
            font-style: italic;
        }
        .student-photo {
            width: 180px;
            height: 180px;
            border: 5px solid #D4AF37;
            border-radius: 50%;
            margin: 0 auto 30px;
            overflow: hidden;
            background: #f8f9fa;
        }
        .student-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .student-name {
            font-size: 36px;
            font-weight: bold;
            color: #2C3E50;
            margin: 20px 0 10px;
        }
        .student-info {
            font-size: 20px;
            color: #34495E;
            margin: 10px 0;
            line-height: 1.6;
        }
        .achievement {
            font-size: 22px;
            color: #27AE60;
            margin: 30px 0;
            font-weight: bold;
        }
        .message {
            font-size: 18px;
            color: #7F8C8D;
            margin: 30px 0;
            line-height: 1.8;
            text-align: justify;
        }
        .signature-area {
            margin-top: 50px;
            display: flex;
            justify-content: space-around;
        }
        .signature {
            text-align: center;
        }
        .signature-line {
            border-top: 2px solid #34495E;
            width: 200px;
            margin: 10px auto;
            padding-top: 5px;
        }
        .decoration {
            position: absolute;
            font-size: 100px;
            color: rgba(212, 175, 55, 0.1);
        }
        .top-left { top: 20px; left: 20px; }
        .top-right { top: 20px; right: 20px; }
        .bottom-left { bottom: 20px; left: 20px; }
        .bottom-right { bottom: 20px; right: 20px; }
        .date {
            position: absolute;
            bottom: 30px;
            right: 50px;
            font-size: 16px;
            color: #7F8C8D;
        }
    </style>
</head>
<body>
    <div class="certificate-container">
        <div class="border-design">
            <!-- Décorations -->
            <div class="decoration top-left">✧</div>
            <div class="decoration top-right">✧</div>
            <div class="decoration bottom-left">✧</div>
            <div class="decoration bottom-right">✧</div>

            <!-- Titre -->
            <div class="title">TABLEAU D'HONNEUR</div>
            <div class="subtitle">Certificat d'Excellence Académique</div>

            <div class="gold-line"></div>

            <!-- Photo de l'élève -->
            <div class="student-photo">
                @if($major['inscription']->eleve->photo_path)
                    <img src="{{ storage_path('app/public/' . $major['inscription']->eleve->photo_path) }}" alt="Photo">
                @else
                    <div style="display: flex; align-items: center; justify-content: center; height: 100%; font-size: 14px; color: #666;">
                        Photo de l'élève
                    </div>
                @endif
            </div>

            <!-- Informations de l'élève -->
            <div class="student-name">
                {{ $major['inscription']->eleve->nom }} {{ $major['inscription']->eleve->prenom }}
            </div>

            <div class="student-info">
                Élève en classe de <strong>{{ $major['inscription']->classe->nom }}</strong>
            </div>

            <div class="student-info">
                avec une moyenne générale de <strong>{{ $major['moyenne'] }}/20</strong>
            </div>

            <!-- Réussite -->
            <div class="achievement">
                @if($type == 'classe')
                    MAJOR DE LA CLASSE
                @else
                    MAJOR GÉNÉRAL
                @endif
                
                @if($periode == 'mois')
                    - {{ strtoupper($mois->nom) }}
                @else
                    - ANNÉE SCOLAIRE {{ $anneeScolaire->annee }}
                @endif
            </div>

            <!-- Message de félicitations -->
            <div class="message">
                L'Établissement <strong>{{ auth()->user()->ecole->nom ?? 'GS EXCELLE' }}</strong> est heureux de vous décerner ce diplôme d'excellence et vous félicite pour tous les efforts fournis durant 
                @if($periode == 'mois')
                    la période de composition du mois de {{ $mois->nom }}
                @else
                    l'année scolaire {{ $anneeScolaire->annee }}
                @endif
                . Votre persévérance, votre assiduité et vos résultats exceptionnels font de vous un exemple pour tous les élèves de l'établissement.
            </div>

            <!-- Signatures -->
            <div class="signature-area">
                <div class="signature">
                    <div class="signature-line"></div>
                    <div>Le Directeur</div>
                </div>
                <div class="signature">
                    <div class="signature-line"></div>
                    <div>Le Professeur Principal</div>
                </div>
            </div>

            <!-- Date -->
            <div class="date">
                Fait à {{ auth()->user()->ecole->ville ?? 'Abidjan' }}, le {{ date('d/m/Y') }}
            </div>
        </div>
    </div>
</body>
</html>