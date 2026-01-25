<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Diplôme Tableau d'Honneur</title>

    <style>
        @page {
            size: A4 portrait;
            margin: 0;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: DejaVu Sans, Arial, sans-serif;
        }

        /* Page diplôme */
        .diplome {
            width: 210mm;
            height: 297mm;
            position: relative;

            /* ✅ IMAGE PHOTOSHOP EN FOND */
            background-image: url('{{ public_path('storage/documents/certificat-frequentation.png') }}');
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
        }

        /* ========= ZONES TEXTE ========= */
        .annee {
            position: absolute;
            top: 44mm;
            right: 15mm;
            font-size: 4mm;
            color: #333;
            font-weight: bold
        }

        .nomPrenoms {
            position: absolute;
            top: 132mm;
            left: 55mm;
            width: 100%;

            font-size: 5mm;
            color: #333;
            font-weight: bold;
        }

        .matricule {
            position: absolute;
            top: 144mm;
            left: 36mm;
            width: 100%;

            font-size: 4mm;
            color: #333;
            font-weight: bold;
        }

        .acteNaissance {
            position: absolute;
            top: 144mm;
            left: 136mm;
            width: 100%;

            font-size: 4mm;
            color: #333;
            font-weight: bold;
        }

        .inscritLe {
            position: absolute;
            bottom: 84mm;
            left: 35mm;
            font-size: 4mm;
            color: #333;
            font-weight: bold
        }

        .aCeJour {
            position: absolute;
            bottom: 84mm;
            right: 75mm;
            font-size: 4mm;
            color: #333;
            font-weight: bold
        }

        .parents {
            position: absolute;
            bottom: 115mm;
            left: 40mm;
            font-size: 4mm;
            color: #333;
            font-weight: bold
        }

        .lieuNaissance {
            position: absolute;
            bottom: 137mm;
            left: 95mm;
            font-size: 4mm;
            color: #333;
            font-weight: bold
        }

        .dateNaissance {
            position: absolute;
            bottom: 137mm;
            left: 31mm;
            font-size: 4mm;
            color: #333;
            font-weight: bold
        }

        .classe {
            position: absolute;
            bottom: 126mm;
            left: 40mm;
            font-size: 4mm;
            color: #333;
            font-weight: bold
        }

        .faitLe {
            position: absolute;
            bottom: 56mm;
            right: 22mm;
            font-size: 4mm;
            color: #333;
            font-weight: bold
        }

        .signature_enseignant {
            position: absolute;
            bottom: 14mm;
            left: 75mm;
            font-size: 4mm;
            color: #333;
            font-weight: bold
        }

        
    </style>
</head>
<body>

<div class="diplome">

    <div class="annee">
        {{ $anneeScolaire->annee }}
    </div>

    <div class="classe">
        {{ $inscription->classe->niveau->nom }}
    </div>

    <div class="nomPrenoms">
        {{ $inscription->eleve->nom }} {{ $inscription->eleve->prenom }}
    </div>

    <div class="matricule">
        {{ $inscription->eleve->matricule }}
    </div>

    <div class="dateNaissance">
        {{ $inscription->eleve->naissance_formattee ? $inscription->eleve->naissance->format('d/m/Y') : 'N/A' }}
    </div>

    <div class="lieuNaissance">
        {{ $inscription->eleve->lieu_naissance ?? 'N/A' }}
    </div>

    <div class="acteNaissance">
        {{ $inscription->eleve->num_extrait ?: 'N/A' }}
    </div>


    <div class="parents">
        {{ $inscription->eleve->parent_nom }}
    </div>

    <div class="inscritLe">
        {{ $inscription->created_at->format('d/m/Y') }}
    </div>

    <div class="aCeJour">
        {{ now()->format('d/m/Y') }}
    </div>

    <div class="faitLe">
        {{ now()->format('d/m/Y') }}
    </div>

</div>

</body>

</html>
