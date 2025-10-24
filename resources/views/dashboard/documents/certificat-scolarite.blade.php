<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Certificat de Scolarité - {{ $inscription->eleve->nom }}</title>
    <style>
        @page { margin: 2cm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; line-height: 1.5; }
        
        .header { 
            margin-bottom: 20px; 
            border-bottom: 2px solid #000; 
            padding-bottom: 10px; 
            overflow: hidden;
        }
        .logo-section {
            width: 20%;
            float: left;
            text-align: center;
        }
        .title-section {
            width: 60%;
            float: left;
            text-align: center;
        }
        .logo {
            width: 100%;
            height: 100%;
        }
        .content { margin: 20px 0; }
        .section { margin-bottom: 15px; }
        .personal-info { width: 65%; float: left; }
        .photo-eleve { width: 30%; float: right; text-align: center; }
        .photo-eleve img { width: 120px; height: 150px; object-fit: cover; border: 1px solid #000; }
        .clear { clear: both; }

        .signature-row { margin-top: 50px; overflow: hidden; }
        .signature-box { width: 45%; text-align: center; border-top: 1px solid #000; padding-top: 5px; }
        .signature-left { float: left; }
        .signature-right { float: right; }

        .cachet-section { text-align: right; margin-top: 40px; font-size: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo-section">
            <img class="logo" src="{{ public_path('assets/img/logo_excelle.jpg') }}" alt="Logo école" style="height:80px;">
        </div>
        <div class="title-section">
            <h2 style="margin: 0; font-size: 18px;">CERTIFICAT DE SCOLARITÉ</h2>
            <h3 style="margin: 5px 0; font-size: 16px;">{{ auth()->user()->ecole->nom ?? 'GS EXCELLE' }}</h3>
            <p style="margin: 0; font-size: 12px;">Année Scolaire: {{ $inscription->anneeScolaire->annee }}</p>
        </div>
        <div class="clear"></div>
    </div>

    <div class="content section">
        <div class="personal-info">
            <p>Le Directeur de l'École <strong>{{ auth()->user()->ecole->nom ?? 'GS EXCELLE' }}</strong> certifie que :</p>
            <p><strong>Nom :</strong> {{ $inscription->eleve->nom }}</p>
            <p><strong>Prénom :</strong> {{ $inscription->eleve->prenom }}</p>
            <p><strong>Date de naissance :</strong> {{ $inscription->eleve->naissance->format('d/m/Y') }}</p>
            <p><strong>Lieu de naissance :</strong> {{ $inscription->eleve->lieu_naissance ?? 'Non renseigné' }}</p>
            <p><strong>Matricule :</strong> {{ $inscription->eleve->matricule }}</p>

            <p>Est régulièrement inscrit(e) en classe de <strong>{{ $inscription->classe->nom }}</strong> pour l'année scolaire <strong>{{ $inscription->anneeScolaire->annee }}</strong>.</p>
        </div>

        <div class="photo-eleve" style="margin-top: 50px; float:right">
            @if($inscription->eleve->photo_path)
                    
                    <img src="{{ public_path('storage/' . $inscription->eleve->photo_path) }}" alt="Photo {{ $inscription->eleve->nom }}">
                @else
                    <div style="width:120px; height:150px; border:1px solid #000; display:flex; align-items:center; justify-content:center;">
                        Pas de photo
                    </div>
                @endif
        </div>

        <div class="clear"></div>
    </div>

    <div>
        <p>Le présent certificat est délivré à l'intéressé(e) pour servir et
valoir ce que de droit</p>
    </div>

    <div class="signature-row">
        <div class="signature-box signature-left">
            Fait à ______________<br>
            Le Directeur<br><br>
            _________________________
        </div>
        <div class="signature-box signature-right">
            Cachet de l'École
        </div>
        <div class="clear"></div>
    </div>

</body>
</html>
