<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Certificat de Scolarité - {{ $inscription->eleve->nom }}</title>
    <style>
        @page { margin: 3cm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 14px; line-height: 1.6; }
        .header { text-align: center; margin-bottom: 30px; }
        .content { text-align: justify; margin: 40px 0; }
        .signature { margin-top: 60px; text-align: right; }
        .certificate { border: 2px solid #000; padding: 40px; margin: 20px; }
    </style>
</head>
<body>
    <div class="certificate">
        <div class="header">
            <h1 style="margin-bottom: 5px;">CERTIFICAT DE SCOLARITÉ</h1>
            <h2 style="margin-top: 5px; margin-bottom: 10px;">{{ auth()->user()->ecole->nom ?? 'GS EXCELLE' }}</h2>
            <p>Établissement d'Enseignement Primaire</p>
        </div>

        <div class="content">
            <p>Le Directeur de l'École <strong>{{ auth()->user()->ecole->nom ?? 'GS EXCELLE' }}</strong> certifie que</p>
            
            <p style="text-align: center; font-size: 16px; font-weight: bold; margin: 20px 0;">
                {{ $inscription->eleve->nom }} {{ $inscription->eleve->prenom }}
            </p>
            
            <p>Né(e) le <strong>{{ $inscription->eleve->naissance->format('d/m/Y') }}</strong> 
               à <strong>{{ $inscription->eleve->lieu_naissance ?? 'Non renseigné' }}</strong>,</p>
            
            <p>Matricule: <strong>{{ $inscription->eleve->matricule }}</strong>,</p>
            
            <p>Est régulièrement inscrit(e) en classe de <strong>{{ $inscription->classe->nom }}</strong> 
               pour l'année scolaire <strong>{{ $inscription->anneeScolaire->annee }}</strong>.</p>
            
            <p>Le présent certificat est délivré à l'intéressé(e) pour servir et valoir ce que de droit.</p>
        </div>

        <div class="signature">
            <p>Fait à ______________, le {{ date('d/m/Y') }}</p>
            <br><br>
            <p>Le Directeur</p>
            <p>_________________________</p>
            <p>Cachet de l'École</p>
        </div>
    </div>
</body>
</html>