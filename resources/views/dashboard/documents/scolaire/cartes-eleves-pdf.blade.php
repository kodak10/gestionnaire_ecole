<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 8mm;
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        /* ===================================================
           GRILLE : 3 cartes par ligne
        =================================================== */
        .ligne-cartes {
            width: 100%;
            display: table;
            table-layout: fixed;
            margin-bottom: 6mm;
        }
        .cellule-carte {
            display: table-cell;
            width: 33.33%;
            vertical-align: top;
            padding: 0 3mm;
        }

        /* ===================================================
           CARTE - conteneur (ratio proche CR80 : 85.6 x 54mm)
        =================================================== */
        .carte {
            position: relative;
            width: 85.6mm;
            height: 54mm;
            background-color: #eeeeee;
            border: 0.2mm solid #d5d5d5;
            overflow: hidden;
        }

        /* ===================================================
           MOTIF DE TRIANGLES (bande diagonale, reproduit en CSS)
        =================================================== */
        .triangle {
            position: absolute;
            width: 0;
            height: 0;
        }
        .triangle-up-orange {
            border-left: 4.5mm solid transparent;
            border-right: 4.5mm solid transparent;
            border-bottom: 7.5mm solid #EE8B22;
        }
        .triangle-up-cyan {
            border-left: 4.5mm solid transparent;
            border-right: 4.5mm solid transparent;
            border-bottom: 7.5mm solid #29C1D4;
        }
        .triangle-down-orange {
            border-left: 4.5mm solid transparent;
            border-right: 4.5mm solid transparent;
            border-top: 7.5mm solid #EE8B22;
        }
        .triangle-down-cyan {
            border-left: 4.5mm solid transparent;
            border-right: 4.5mm solid transparent;
            border-top: 7.5mm solid #29C1D4;
        }

        /* ===================================================
           BLOC LOGO ÉCOLE
        =================================================== */
        .logo-bloc {
            position: absolute;
            top: 20%;
            left: 3%;
            width: 33%;
            text-align: left;
        }
        .logo-badge {
            width: 9mm;
            height: 9mm;
            border-radius: 50%;
            background-color: #ffffff;
            border: 0.4mm solid #F0B429;
            text-align: center;
            line-height: 9mm;
            font-size: 5.5mm;
            color: #F0B429;
            margin-bottom: 1mm;
        }
        .logo-texte-1 {
            font-size: 6.5px;
            font-weight: bold;
            color: #1B6E5B;
            letter-spacing: 0.3px;
            line-height: 1.3;
        }
        .logo-texte-2 {
            font-size: 6px;
            font-weight: bold;
            color: #F0B429;
            letter-spacing: 0.5px;
        }
        .logo-slogan {
            font-size: 5px;
            color: #1B6E5B;
            font-style: italic;
            line-height: 1.3;
            margin-top: 0.5mm;
        }

        /* ===================================================
           CONTACT (bas gauche)
        =================================================== */
        .contact-bloc {
            position: absolute;
            bottom: 5%;
            left: 3%;
            width: 33%;
        }
        .contact-ligne {
            font-size: 6px;
            font-weight: bold;
            color: #EE8B22;
            margin-bottom: 1.3mm;
        }
        .contact-puce {
            display: inline-block;
            width: 3mm;
            height: 3mm;
            border-radius: 50%;
            background-color: #29C1D4;
            color: #ffffff;
            text-align: center;
            line-height: 3mm;
            font-size: 4px;
            margin-right: 1mm;
        }
        .contact-adresse {
            font-size: 6px;
            font-weight: bold;
            color: #EE8B22;
        }

        /* ===================================================
           TITRE "CARTE ÉLÈVE"
        =================================================== */
        .titre-carte {
            position: absolute;
            top: 6%;
            right: 5%;
            font-size: 13px;
            font-weight: bold;
            color: #EE8B22;
            letter-spacing: 0.5px;
        }

        /* ===================================================
           PHOTO ÉLÈVE (cercle central)
        =================================================== */
        .photo-cercle {
            position: absolute;
            top: 24%;
            left: 34%;
            width: 24mm;
            height: 24mm;
            border-radius: 50%;
            border: 1mm solid #29C1D4;
            overflow: hidden;
            background-color: #EE8B22;
            text-align: center;
        }
        .photo-cercle img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .photo-cercle .initiales {
            color: #ffffff;
            font-size: 16px;
            font-weight: bold;
            line-height: 24mm;
        }

        /* ===================================================
           BLOC INFOS (droite)
        =================================================== */
        .infos-bloc {
            position: absolute;
            top: 26%;
            right: 4%;
            width: 46%;
        }
        .info-ligne {
            font-size: 7px;
            font-weight: bold;
            color: #1a1a1a;
            margin-bottom: 2.3mm;
            white-space: nowrap;
        }
        .info-ligne .valeur {
            color: #1CB5C4;
            font-weight: bold;
        }
    </style>
</head>
<body>

@foreach($lignes as $ligne)
    <div class="ligne-cartes">
        @foreach($ligne as $eleve)
            <div class="cellule-carte">
                @include('dashboard.documents.scolaire.partials.carte-eleve-item', ['eleve' => $eleve, 'ecole' => $ecole])
            </div>
        @endforeach
    </div>
@endforeach

</body>
</html>