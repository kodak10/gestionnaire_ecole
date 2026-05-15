<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Parchemin - Fin d'Année Scolaire</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Georgia', Times, serif;
            font-size: 12px;
            margin: 0;
            padding: 0;
            background: #fff;
        }

        .container {
            width: 100%;
            max-width: 200mm;
            margin: 0 auto;
            padding: 5mm 0;
        }

        .page-break {
            page-break-after: always;
        }

        /* Style du parchemin */
        .parchemin {
            width: 100%;
            margin: 0 auto;
            border: 1px solid #8B4513;
            background: #fffef7;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .border-cadre {
            border: 2px solid #8B4513;
            padding: 8mm 2mm;
            background: #fffef7;
        }

        /* En-tête identique à celui du bulletin */
        .entete {
            width: 100%;
            overflow: hidden;
            margin-bottom: 5mm;
        }
        .entete-logo {
            float: left;
            width: 20%;
            text-align: center;
        }
        .logo-img {
            width: 100%;
            height: 100px;
        }
        .entete-centre {
            float: left;
            width: 58%;
            text-align: center;
            border: 1px solid #000;
            padding: 2mm;
            box-sizing: border-box;
            border-radius: 10px;
                        

        }
        .entete-droite {
            
            float: right;
            width: 14%;
            text-align: left;
            box-sizing: border-box;
            border-radius: 10px;
        }
        .clearfix {
            clear: both;
        }
        
        .republique-text {
            font-size: 10pt;
            font-weight: bold;
        }
        .ministere-text {
            font-size: 9pt;
            font-weight: bold;
        }
        .ecole-nom-header {
            font-size: 10pt;
            font-weight: bold;
            margin-top: 2mm;
        }
        .info-droite {
            font-size: 9pt;
            line-height: 1.4;
        }

        /* Titre */
        .title {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 4mm 0;
            text-decoration: underline;
        }

        /* Contenu */
        .content {
            width: 100%;
        }

        .eleve {
            font-size: 11pt;
            margin-bottom: 4mm;
            line-height: 1.4;
            text-align: center;
        }

        .eleve-name {
            font-weight: bold;
            text-decoration: underline;
        }

        .section {
            font-size: 11pt;
            margin: 4mm 0;
            text-align: center;
        }

        .travail {
            font-size: 11pt;
            margin: 4mm 0;
            text-align: center;
        }

        /* Tableau des mentions */
        .mention-table {
            width: 70%;
            margin: 4mm auto;
            border-collapse: collapse;
        }

        .mention-table td {
            padding: 2.5mm 3mm;
            border: 1px solid #000;
            font-size: 11pt;
        }

        .mention-table td:first-child {
            width: 65%;
        }

        .mention-table td:last-child {
            width: 35%;
            text-align: center;
        }

        .mention-checked {
            font-weight: bold;
        }

        .mention-checked::after {
            content: " ✓";
            font-weight: bold;
            color: green;
        }

        /* Classe suivante */
        .classe-suivante-table {
            width: 70%;
            margin: 4mm auto;
            border-collapse: collapse;
        }

        .classe-suivante-table td {
            padding: 2.5mm 3mm;
            border: 1px solid #000;
            font-size: 11pt;
        }

        .classe-suivante-table td:first-child {
            width: 65%;
        }

        .classe-suivante-table td:last-child {
            width: 35%;
            text-align: center;
        }

        .classe-checked {
            font-weight: bold;
        }

        .classe-checked::after {
            content: " ✓";
            font-weight: bold;
            color: green;
        }

        /* Voeux */
        .voeux {
            font-size: 10pt;
            font-style: italic;
            text-align: center;
            margin: 5mm 0 3mm 0;
        }

        /* Signature avec photo */
        /* Signature avec photo */
.signature-container {
    margin-top: 5mm;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
}

.photo-eleve {
    width: 100px;
    height: 100px;
    border: 1px solid #8B4513;
    border-radius: 50%;
    overflow: hidden;
    background: #f0f0f0;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.photo-eleve img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.photo-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 30px;
    color: #999;
}

.signature {
    text-align: right;
    flex-grow: 1;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    height: 90px; /* Même hauteur que la photo */
}

.signature-line {
    font-weight: bold;
    font-size: 11pt;
    margin: 0;
    padding: 0;
}

        .date {
            text-align: center;
            margin-top: 4mm;
            margin-bottom: 2mm;
            font-size: 10pt;
        }
    </style>
</head>
<body>

@php
    use Carbon\Carbon;
    $ecole = \App\Models\Ecole::find(session('current_ecole_id'));
@endphp

@foreach($eleves as $eleve)
@php
    $inscription = $eleve['inscription'];
    $mention = $eleve['mention'];
    $classeSuivante = $eleve['classe_suivante'];
    $photoPath = $eleve['photo_path'] ?? null;
@endphp

@php
    $photoPath = public_path('images/default.png'); // Chemin par défaut
    
    if($inscription->eleve->photo_path) {
        // Vérifier si le fichier existe dans storage
        $storagePath = storage_path('app/public/' . $inscription->eleve->photo_path);
        if(file_exists($storagePath)) {
            $photoPath = $storagePath;
        } else {
            // Vérifier si le fichier existe dans public/storage
            $publicPath = public_path('storage/' . $inscription->eleve->photo_path);
            if(file_exists($publicPath)) {
                $photoPath = $publicPath;
            }
        }
    }
@endphp

<div class="container">
    <div class="parchemin">
        <div class="border-cadre">
            
            <!-- EN-TÊTE AVEC INFOS ÉCOLE -->
            <div class="entete">
                <!-- Logo à gauche -->
                <div class="entete-logo" style="margin-right: 20px">
                    <img src="{{ $ecole->logo }}" alt="Logo" class="logo-img">
                    <br>
                    Tél. / Fax : <b>{{ $ecole->telephone ?? '' }}</b> / <b>{{ $ecole->fax ?? '0274839310' }}</b>
                </div>

                <!-- Partie centrale (avec cadre) -->
                <div class="entete-centre">
                    <div class="republique-text"><b>RÉPUBLIQUE DE CÔTE D'IVOIRE</b></div>
                    <div>MINISTÈRE DE L’ÉDUCATION NATIONALE DE L'ALPHABÉTISATION ET DE L'ENSEIGNEMENT TECHNIQUE<br></div>
                    <div>...........................</div>
                    <span>Direction Régionale: Korhogo</span> <br>
                    <span>IEPP: {{ $ecole->iept ?? 'KORHOGO EST' }}</span> <br>
                    <span>Secteur Pédagogique: {{ $ecole->secteur_pedagogique ?? 'Jean Delafosse' }}</span>
                    <div>...........................</div>

                    <div class="ecole-nom-header"><b>{{ strtoupper($ecole->nom ?? 'GROUPE SCOLAIRE EXCELLE') }}</b></div>
                </div>
 
                <!-- Partie droite (infos école) -->
                <div class="entete-droite">
                    <div class="info-droite">
                        <div class="photo-eleve">
        @php
            $photoPath = public_path('images/default.png'); // Chemin par défaut
            
            if($inscription->eleve->photo_path) {
                $storagePath = storage_path('app/public/' . $inscription->eleve->photo_path);
                if(file_exists($storagePath)) {
                    $photoPath = $storagePath;
                } else {
                    $publicPath = public_path('storage/' . $inscription->eleve->photo_path);
                    if(file_exists($publicPath)) {
                        $photoPath = $publicPath;
                    }
                }
            }
        @endphp
        <img src="{{ $photoPath }}" 
             alt="Photo de {{ $inscription->eleve->prenom }}">
    </div>
                    </div>
                </div>
                <div class="clearfix"></div>
            </div>

            <!-- Titre -->
            <div class="title">
                BILAN DE FIN D'ANNÉE SCOLAIRE {{ $anneeScolaire->annee ?? $anneeScolaire->debut.'-'.$anneeScolaire->fin }}
            </div>

            <!-- Contenu -->
            <div class="content">
                <div class="eleve">
                    L'Élève : <span class="eleve-name">{{ strtoupper($inscription->eleve->nom) }} {{ ucfirst($inscription->eleve->prenom) }}</span>
                </div>

                <div class="section">
                    En {{ $classe->nom }} de l'école « {{ $ecole->nom ?? 'GROUPE SCOLAIRE EXCELLE' }} » de {{ $ecole->ville ?? 'KORHOGO' }}, a produit un travail dans l'ensemble :
                </div>

                <!-- Tableau des mentions -->
                <table class="mention-table">
                    <tr><td>Passable</td><td></td></tr>
                    <tr><td>Assez-bien</td><td></td></tr>
                    <tr><td>Bien</td><td></td></tr>
                    <tr><td>Très-bien</td><td></td></tr>
                    <tr><td>Excellent</td><td></td></tr>
                </table>

                <div class="travail">
                    Il/elle est par conséquent, déclaré(e) capable de suivre, au titre de l'année scolaire 2026-2027
                </div>

                <!-- Tableau classe suivante -->
                <table class="classe-suivante-table">
                    <tr><td>La Petite Section</td><td></td></tr>
                    <tr><td>La Moyenne Section</td><td></td></tr>
                    <tr><td>La Grande Section</td><td></td></tr>
                    <tr><td>Le CP1</td><td></td></tr>
                </table>

                <!-- Voeux -->
                <div class="voeux">
                    La direction, le personnel et ses petits amis lui souhaitent de BONNES VACANCES.
                </div>
            </div>

            <!-- Date -->
            <div class="date">
                {{ $ecole->ville ?? 'Korhogo' }}, le {{ now()->format('d/m/Y') }}
            </div>
<div class="signature-container">
    
    <div class="signature">
        <div class="signature-line">
            LA DIRECTION
        </div>
    </div>
</div>
        </div>
    </div>
</div>

@if(!$loop->last)
    <div class="page-break"></div>
@endif

@endforeach

</body>
</html>