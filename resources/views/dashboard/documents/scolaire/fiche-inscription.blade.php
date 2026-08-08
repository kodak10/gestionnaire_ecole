<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Fiche d'Inscription - {{ $eleve->nom ?? '' }}</title>
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
        .personal-info {
            width: 65%;
            float: left;
        }
        .photo-eleve {
            width: 30%;
            float: right;
            text-align: center;
        }
        .photo-eleve img {
            width: 120px;
            height: 150px;
            object-fit: cover;
            border: 1px solid #000;
        }
        .clear { clear: both; }
    </style>
</head>
<body>
    <!-- En-tête avec logo à gauche et infos au centre -->
    <div class="header">
        <div class="logo-section">
            <img class="logo" src="{{ public_path('assets/img/logo_excelle.jpg') }}" alt="Logo école" style="height:80px; vertical-align:middle; margin-right:10px;">
        </div>

        <div class="title-section">
            <h2 style="margin: 0; font-size: 18px;">FICHE D'INSCRIPTION</h2>
            <h3 style="margin: 5px 0; font-size: 16px;">{{ $ecole->nom_ecole ?? 'GS EXCELLE' }}</h3>
            <p style="margin: 0; font-size: 12px;">Année Scolaire: {{ $anneeScolaire->annee ?? $annee ?? '' }}</p>
        </div>
        <div style="clear: both;"></div>
    </div>

    <div class="content">
        <div class="section">
            <h4 style="background: #f0f0f0; padding: 5px; margin-bottom: 10px;">INFORMATIONS PERSONNELLES</h4>

            <div class="personal-info">
                <div class="field"><span class="label">Matricule:</span> {{ $eleve->code_national ?? $eleve->matricule ?? 'Non renseigné' }}</div>
                <div class="field"><span class="label">Nom:</span> {{ $eleve->nom ?? 'Non renseigné' }}</div>
                <div class="field"><span class="label">Prénom:</span> {{ $eleve->prenom ?? 'Non renseigné' }}</div>
                <div class="field"><span class="label">Sexe:</span> {{ $eleve->sexe ?? 'Non renseigné' }}</div>
                <div class="field">
                    <span class="label">Date Naissance:</span> 
                    @if(isset($eleve->naissance) && $eleve->naissance)
                        @if($eleve->naissance instanceof \DateTime || $eleve->naissance instanceof \Carbon\Carbon)
                            {{ $eleve->naissance->format('d/m/Y') }}
                        @else
                            {{ \Carbon\Carbon::parse($eleve->naissance)->format('d/m/Y') }}
                        @endif
                    @else
                        Non renseigné
                    @endif
                </div>
                <div class="field"><span class="label">Lieu Naissance:</span> {{ $eleve->lieu_naissance ?? 'Non renseigné' }}</div>
                <div class="field"><span class="label">Nationalité:</span> {{ $eleve->nationalite ?? 'Non renseigné' }}</div>
            </div>

            <div class="photo-eleve">
                @if(isset($eleve->photo_path) && $eleve->photo_path && file_exists(public_path('storage/' . $eleve->photo_path)))
                    <img src="{{ public_path('storage/' . $eleve->photo_path) }}" alt="Photo {{ $eleve->nom ?? '' }}">
                @else
                    <div style="width:120px; height:150px; border:1px solid #000; display:flex; align-items:center; justify-content:center;">
                        Pas de photo
                    </div>
                @endif
            </div>

            <div class="clear"></div>
        </div>

        <div class="section">
            <h4 style="background: #f0f0f0; padding: 5px; margin-bottom: 10px;">INFORMATIONS SCOLAIRES</h4>
            <div class="field"><span class="label">Classe:</span> {{ $classe->nom ?? $classe->libelle ?? 'Non renseigné' }}</div>
            <div class="field"><span class="label">Date Inscription:</span> 
                @if(isset($eleve->created_at) && $eleve->created_at)
                    @if($eleve->created_at instanceof \DateTime || $eleve->created_at instanceof \Carbon\Carbon)
                        {{ $eleve->created_at->format('d/m/Y') }}
                    @else
                        {{ \Carbon\Carbon::parse($eleve->created_at)->format('d/m/Y') }}
                    @endif
                @else
                    {{ date('d/m/Y') }}
                @endif
            </div>
        </div>

        <div class="section">
            <h4 style="background: #f0f0f0; padding: 5px; margin-bottom: 10px;">INFORMATIONS DU PARENT</h4>
            <div class="field"><span class="label">Nom Parent:</span> {{ $eleve->parent_nom ?? $eleve->pere_nom ?? 'Non renseigné' }}</div>
            <div class="field"><span class="label">Téléphone:</span> {{ $eleve->parent_telephone ?? $eleve->pere_contact ?? 'Non renseigné' }}</div>
            <div class="field"><span class="label">Email:</span> {{ $eleve->parent_email ?? 'Non renseigné' }}</div>
            <div class="field"><span class="label">Adresse:</span> {{ $eleve->parent_adresse ?? 'Non renseigné' }}</div>
        </div>

        @if(isset($eleve->transport_active) && $eleve->transport_active)
        <div class="section">
            <h4 style="background: #f0f0f0; padding: 5px; margin-bottom: 10px;">SERVICES</h4>
            <div class="field"><span class="label">Transport:</span> Actif</div>
            @if(isset($eleve->cantine_active) && $eleve->cantine_active)
                <div class="field"><span class="label">Cantine:</span> Actif</div>
            @endif
        </div>
        @endif
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
        <p>Fait à {{ $ecole->ville ?? '____________________' }}, le {{ date('d/m/Y') }}</p>
    </div>
</body>
</html>