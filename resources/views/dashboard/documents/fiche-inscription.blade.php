<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Fiche d'Inscription - {{ $inscription->eleve->nom }}</title>
    <style>
        @page { margin: 2cm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
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
        .field { margin-bottom: 8px; }
        .label { font-weight: bold; display: inline-block; width: 150px; }
        .signatures-row {
            margin-top: 50px;
            overflow: hidden;
        }
        .signature-box { 
            width: 45%; 
            text-align: center; 
            border-top: 1px solid #000; 
            padding-top: 5px;
        }
        .signature-left {
            float: left;
        }
        .signature-right {
            float: right;
        }
        .cachet-section {
            text-align: right;
            margin-top: 40px;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <!-- En-tête avec logo à gauche et infos au centre -->
    <div class="header">
        <div class="logo-section">
            <img class="logo" src="{{ public_path('assets/img/logo_excelle.jpg') }}" alt="Logo école" style="height:60px; vertical-align:middle; margin-right:10px;">
    </div>

        <div class="title-section">
            <h2 style="margin: 0; font-size: 18px;">FICHE D'INSCRIPTION</h2>
            <h3 style="margin: 5px 0; font-size: 16px;">{{ auth()->user()->ecole->nom ?? 'GS EXCELLE' }}</h3>
            <p style="margin: 0; font-size: 12px;">Année Scolaire: {{ $inscription->anneeScolaire->annee }}</p>
        </div>
        <div style="clear: both;"></div>
    </div>

    <div class="content">
        <div class="section">
            <h4 style="background: #f0f0f0; padding: 5px; margin-bottom: 10px;">INFORMATIONS PERSONNELLES</h4>
            <div class="field"><span class="label">Matricule:</span> {{ $inscription->eleve->matricule }}</div>
            <div class="field"><span class="label">Nom:</span> {{ $inscription->eleve->nom }}</div>
            <div class="field"><span class="label">Prénom:</span> {{ $inscription->eleve->prenom }}</div>
            <div class="field"><span class="label">Sexe:</span> {{ $inscription->eleve->sexe }}</div>
            <div class="field"><span class="label">Date Naissance:</span> {{ $inscription->eleve->naissance->format('d/m/Y') }}</div>
            <div class="field"><span class="label">Lieu Naissance:</span> {{ $inscription->eleve->lieu_naissance ?? 'Non renseigné' }}</div>
        </div>

        <div class="section">
            <h4 style="background: #f0f0f0; padding: 5px; margin-bottom: 10px;">INFORMATIONS SCOLAIRES</h4>
            <div class="field"><span class="label">Classe:</span> {{ $inscription->classe->nom }}</div>
            <div class="field"><span class="label">Niveau:</span> {{ $inscription->classe->niveau->nom }}</div>
            <div class="field"><span class="label">Date Inscription:</span> {{ $inscription->created_at->format('d/m/Y') }}</div>
        </div>

        <div class="section">
            <h4 style="background: #f0f0f0; padding: 5px; margin-bottom: 10px;">INFORMATIONS DU PARENT</h4>
            <div class="field"><span class="label">Nom Parent:</span> {{ $inscription->eleve->parent_nom ?? 'Non renseigné' }}</div>
            <div class="field"><span class="label">Téléphone:</span> {{ $inscription->eleve->parent_telephone ?? 'Non renseigné' }}</div>
        </div>
    </div>

    <!-- Signatures sur la même ligne avec float -->
    <div class="signatures-row">
        <div class="signature-box signature-left">
            Le Responsable de l'École<br><br>
            _________________________<br>
            <small>Nom, Prénom et Signature</small>
        </div>
        <div class="signature-box signature-right">
            Le Parent / Tuteur<br><br>
            _________________________<br>
            <small>Nom, Prénom et Signature</small>
        </div>
        <div style="clear: both;"></div>
    </div>

    <!-- Cachet et date en bas à droite -->
    <div class="cachet-section">
        <p>Cachet de l'établissement</p>
        <div style="width: 80px; height: 80px; border: 2px dashed #000; display: inline-block; margin-bottom: 5px;"></div>
        <p>Fait à ____________________, le {{ date('d/m/Y') }}</p>
    </div>
</body>
</html>