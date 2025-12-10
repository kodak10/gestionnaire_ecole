<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Diplôme Tableau d'Honneur</title>

    <style>
        @page {
            size: A4 landscape;
            margin: 0;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: DejaVu Sans, Arial, sans-serif;
        }

        /* Page diplôme */
        .diplome {
            width: 297mm;
            height: 210mm;
            position: relative;

            /* ✅ IMAGE PHOTOSHOP EN FOND */
            background-image: url('{{ public_path('storage/diplomes/modele-diplome-mensuel.png') }}');
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
        }

        /* ========= ZONES TEXTE ========= */
        .rang {
            position: absolute;
            top: 68mm;
            left: 40mm;
            width: 215mm;

            text-align: center;
            font-size: 4mm;
            color: #555;
            font-weight: bold;
        }

        .nom {
            position: absolute;
            top: 95mm;      /* ⬅ ajuste */
            left: 40mm;
            width: 250mm;

            text-align: center;
            margin-left: 15px;
            font-size: 10mm;
            font-weight: bold;
            color: #000;
            text-transform: uppercase;
            line-height: 0.8;
        }
        .photo {
            position: absolute;
            top: 80mm;          /* Ajuste si besoin */
            left: 35mm;
            width: 45mm;
            height: 45mm;
            border-radius: 50%;
            border: 0.5mm solid #333;   /* ✅ Bordure circulaire */
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .classe {
            position: absolute;
            top: 109mm;
            left: 40mm;
            width: 215mm;

            text-align: center;
            font-size: 6mm;
            color: #333;
        }

        .moyenne {
            position: absolute;
            top: 116mm;
            left: 40mm;
            width: 215mm;

            text-align: center;
            font-size: 6mm;
            font-weight: bold;
            color: #b71c1c;
        }
        .date {
            position: absolute;
            bottom: 10mm;
            left: 40mm;
            width: 215mm;

            text-align: center;
            font-size: 3mm;
            color: #333;
        }

        .signature_directeur {
            position: absolute;
            bottom: 25mm;
            right: 76mm;
            font-size: 4mm;
            color: #333;
            font-weight: bold
        }

        .signature_enseignant {
            position: absolute;
            text-align: left;
            bottom: 25mm;
            left: 85mm;
            font-size: 4mm;
            color: #333;
            font-weight: bold
        }

        
    </style>
</head>
<body>

<div class="diplome">

@php
    $nom = strtoupper(trim($eleve['inscription']->eleve->nom));
    $prenoms = array_values(array_filter(
        explode(' ', trim($eleve['inscription']->eleve->prenom))
    ));

    $maxLength = 25;

    // Construction initiale
    $nomFinal = $nom . ' ' . strtoupper(implode(' ', $prenoms));

    // Tant que ça dépasse, on réduit les prénoms un à un (en partant de la fin)
    for ($i = count($prenoms) - 1; $i >= 0; $i--) {

        if (mb_strlen($nomFinal) <= $maxLength) {
            break;
        }

        $prenoms[$i] = mb_substr($prenoms[$i], 0, 1) . '.';
        $nomFinal = $nom . ' ' . strtoupper(implode(' ', $prenoms));
    }

    // Si ça dépasse encore → réduire le nom en initiale
    if (mb_strlen($nomFinal) > $maxLength) {
        $nomInitial = mb_substr($nom, 0, 1) . '.';
        $nomFinal = $nomInitial . ' ' . strtoupper(implode(' ', $prenoms));
    }

    // Nettoyage
    $nomFinal = preg_replace('/\s+/', ' ', trim($nomFinal));
@endphp

 @php
        $photoPath = str_replace(
            url('/storage'),
            public_path('storage'),
            $eleve['inscription']->eleve->photo_url
        );
    @endphp



    <div class="photo">
        <img src="{{ $photoPath }}">

    </div>

<div class="nom">
    {{ $nomFinal }}
</div>

<div class="photo">
    <img src="{{ $eleve['inscription']->eleve->photo_url }} ">
</div>

<div class="classe">
    Elève en Classe de : <strong>{{ $eleve['inscription']->classe->nom }}</strong>
</div>

<div class="moyenne">
    Moyenne : {{ number_format((float)$eleve['moyenne_reelle'], 2, '.', '') }} / {{ intval(preg_replace('/[.,].*/', '', $eleve['moy_base'])) }}
</div>

<div class="rang">
    Année Scolaire : {{ $anneeScolaire->annee }}
</div>

<div class="date">
    Fait à {{ $inscription->ecole->ville ?? 'Korhogo' }}, le {{ now()->format('d/m/Y') }}
</div>

<div class="signature_enseignant">
    {{ $eleve['inscription']->classe->enseignant->nom_prenoms }}
</div>

<div class="signature_directeur">
    {{ $anneeScolaire->ecole->directeur }}
</div>

</div>



</body>
</html>
