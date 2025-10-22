<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Tableau d'Honneur</title>
    <style>
        @page { margin: 0; }
        body { 
            font-family: 'DejaVu Sans', serif; 
            margin: 0;
            padding: 0;
        }
        .page {
            width: 21cm;
            height: 29.7cm;
            page-break-after: always;
            position: relative;
        }
        .certificate-container {
            width: 100%;
            height: 100%;
            background: white;
            border: 15px solid #8B4513;
            padding: 40px;
            text-align: center;
        }
        .border-design {
            border: 10px double #D4AF37;
            height: calc(100% - 20px);
            padding: 30px;
        }
        .title {
            font-size: 42px;
            font-weight: bold;
            color: #8B4513;
            margin: 20px 0 10px 0;
            text-transform: uppercase;
        }
        .subtitle {
            font-size: 20px;
            color: #D4AF37;
            margin-bottom: 30px;
        }
        .student-photo {
            width: 150px;
            height: 150px;
            border: 4px solid #D4AF37;
            border-radius: 50%;
            margin: 0 auto 20px;
            overflow: hidden;
            background: #f8f9fa;
        }
        .student-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .student-name {
            font-size: 28px;
            font-weight: bold;
            color: #2C3E50;
            margin: 15px 0 5px;
        }
        .student-info {
            font-size: 16px;
            color: #34495E;
            margin: 5px 0;
        }
        .achievement {
            font-size: 18px;
            color: #27AE60;
            margin: 20px 0;
            font-weight: bold;
        }
        .message {
            font-size: 14px;
            color: #7F8C8D;
            margin: 20px 0;
            line-height: 1.6;
        }
        .signature-area {
            margin-top: 30px;
            display: flex;
            justify-content: space-around;
        }
        .signature {
            text-align: center;
        }
        .signature-line {
            border-top: 1px solid #34495E;
            width: 150px;
            margin: 5px auto;
            padding-top: 3px;
        }
        .rank {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 24px;
            color: #D4AF37;
            font-weight: bold;
        }
    </style>
</head>
<body>
    @foreach($meilleursEleves as $index => $eleve)
    <div class="page">
        <div class="certificate-container">
            <div class="border-design">
                <!-- Rang -->
                <div class="rank">{{ $index + 1 }}ère Place</div>

                <!-- Titre -->
                <div class="title">TABLEAU D'HONNEUR</div>
                <div class="subtitle">
                    @if(isset($mois))
                        Mois de {{ $mois->nom }} - Année {{ $anneeScolaire->annee }}
                    @else
                        Année Scolaire {{ $anneeScolaire->annee }}
                    @endif
                    @if($classe)
                        - {{ $classe->nom }}
                    @else
                        - Général
                    @endif
                </div>

                <!-- Photo de l'élève -->
                <div class="student-photo">
                    @if($eleve['inscription']->eleve->photo_path)
                        <img src="{{ storage_path('app/public/' . $eleve['inscription']->eleve->photo_path) }}" alt="Photo">
                    @else
                        <div style="display: flex; align-items: center; justify-content: center; height: 100%; font-size: 12px; color: #666;">
                            Photo
                        </div>
                    @endif
                </div>

                <!-- Informations de l'élève -->
                <div class="student-name">
                    {{ $eleve['inscription']->eleve->nom }} {{ $eleve['inscription']->eleve->prenom }}
                </div>

                <div class="student-info">
                    Élève en classe de <strong>{{ $eleve['inscription']->classe->nom }}</strong>
                </div>

                <div class="student-info">
                    avec une moyenne générale de <strong>{{ $eleve['moyenne'] }}/20</strong>
                </div>

                <!-- Message de félicitations -->
                <div class="message">
                    L'Établissement <strong>{{ auth()->user()->ecole->nom ?? 'GS EXCELLE' }}</strong> est heureux de vous décerner ce diplôme d'excellence et vous félicite pour tous les efforts fournis durant 
                    @if(isset($mois))
                        la période de composition du mois de {{ $mois->nom }}
                    @else
                        l'année scolaire {{ $anneeScolaire->annee }}
                    @endif
                    . Votre persévérance et vos résultats remarquables vous valent une place dans le Tableau d'Honneur.
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
                <div style="margin-top: 20px; font-size: 12px; color: #7F8C8D;">
                    Fait à {{ auth()->user()->ecole->ville ?? 'Abidjan' }}, le {{ date('d/m/Y') }}
                </div>
            </div>
        </div>
    </div>
    @endforeach
</body>
</html>