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
            max-width: 180mm;
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
            padding: 8mm 10mm;
            background: #fffef7;
        }

        /* En-tête */
        .header {
            width: 100%;
            text-align: center;
            margin-bottom: 5mm;
        }

        .ministere {
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 2mm;
        }

        .direction, .inspection, .secteur {
            font-size: 9pt;
            margin-bottom: 1mm;
        }

        .ecole {
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 4mm;
            margin-bottom: 4mm;
            text-decoration: underline;
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

        /* Signature */
        .signature {
            margin-top: 5mm;
            text-align: right;
        }

        .signature-line {
            margin-top: 8mm;
            font-weight: bold;
        }

        .date {
            text-align: center;
            margin-top: 4mm;
            margin-bottom: 2mm;
            font-size: 10pt;
        }

        /* Empêcher les sauts de page à l'intérieur */
        .parchemin, .border-cadre, .header, .content {
            page-break-inside: avoid;
            break-inside: avoid;
        }
    </style>
</head>
<body>

@foreach($eleves as $eleve)
@php
    $inscription = $eleve['inscription'];
    $mention = $eleve['mention'];
    $classeSuivante = $eleve['classe_suivante'];
@endphp

<div class="container">
    <div class="parchemin">
        <div class="border-cadre">
            <!-- En-tête -->
            <div class="header">
                <div class="ministere">MINISTÈRE DE L'ÉDUCATION NATIONALE</div>
                <div class="direction">DIRECTION RÉGIONALE : {{ strtoupper($ecole->region ?? 'KORHOGO') }}</div>
                <div class="inspection">INSPECTION DE L'ENSEIGNEMENT PRÉSCOLAIRE ET PRIMAIRE : {{ strtoupper($ecole->inspection ?? 'KORHOGO-EST') }}</div>
                <div class="secteur">SECTEUR PÉDAGOGIQUE : {{ strtoupper($ecole->secteur ?? 'TIEKELEZO') }}</div>
                <div class="ecole">{{ strtoupper($ecole->nom ?? 'GROUPE SCOLAIRE EXCELLE') }}</div>
            </div>

            <!-- Titre -->
            <div class="title">
                BILAN DE FIN D'ANNÉE SCOLAIRE {{ $anneeScolaire->annee }}
            </div>

            <!-- Contenu -->
            <div class="content">
                <div class="eleve">
                    L'Élève : <span class="eleve-name">{{ strtoupper($inscription->eleve->nom) }} {{ ucfirst($inscription->eleve->prenom) }}</span>
                </div>

                <div class="section">
                    En {{ $classe->nom }} de la Maternelle « {{ $ecole->nom ?? 'GROUPE SCOLAIRE EXCELLE' }} » de {{ $ecole->ville ?? 'KORHOGO' }}, a produit un travail dans l'ensemble :
                </div>

                <!-- Tableau des mentions -->
                <table class="mention-table">
                    <tr><td>Passable</td><td class="{{ $mention == 'Passable' ? 'mention-checked' : '' }}">{{ $mention == 'Passable' ? 'X' : '' }}</td></tr>
                    <tr><td>Assez-bien</td><td class="{{ $mention == 'Assez-bien' ? 'mention-checked' : '' }}">{{ $mention == 'Assez-bien' ? 'X' : '' }}</td></tr>
                    <tr><td>Bien</td><td class="{{ $mention == 'Bien' ? 'mention-checked' : '' }}">{{ $mention == 'Bien' ? 'X' : '' }}</td></tr>
                    <tr><td>Très-bien</td><td class="{{ $mention == 'Très-bien' ? 'mention-checked' : '' }}">{{ $mention == 'Très-bien' ? 'X' : '' }}</td></tr>
                    <tr><td>Excellent</td><td class="{{ $mention == 'Excellent' ? 'mention-checked' : '' }}">{{ $mention == 'Excellent' ? 'X' : '' }}</td></tr>
                </table>

                <div class="travail">
                    Il/elle est par conséquent, déclaré(e) capable de suivre, au titre de l'année scolaire {{ $anneeScolaire->annee }}
                </div>

                <!-- Tableau classe suivante -->
                <table class="classe-suivante-table">
                    <tr><td>La Petite Section</td><td class="{{ $classeSuivante == 'Petite Section' ? 'classe-checked' : '' }}">{{ $classeSuivante == 'Petite Section' ? 'X' : '' }}</td></tr>
                    <tr><td>La Moyenne Section</td><td class="{{ $classeSuivante == 'Moyenne Section' ? 'classe-checked' : '' }}">{{ $classeSuivante == 'Moyenne Section' ? 'X' : '' }}</td></tr>
                    <tr><td>La Grande Section</td><td class="{{ $classeSuivante == 'Grande Section' ? 'classe-checked' : '' }}">{{ $classeSuivante == 'Grande Section' ? 'X' : '' }}</td></tr>
                    <tr><td>Le CP1</td><td class="{{ $classeSuivante == 'CP1' ? 'classe-checked' : '' }}">{{ $classeSuivante == 'CP1' ? 'X' : '' }}</td></tr>
                </table>

                <!-- Voeux -->
                <div class="voeux">
                    La direction, le personnel et ses petits amis lui souhaitent de BONNES VACANCES.
                </div>
            </div>

            <!-- Date et signature -->
            <div class="date">
                Korhogo, le {{ now()->format('d/m/Y') }}
            </div>

            <div class="signature">
                <div class="signature-line">
                    LA DIRECTION
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