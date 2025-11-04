<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">

<style>
/* --- POLICE COMIC SANS POUR DOMPDF --- */
/* @font-face {
    font-family: 'comic';
    src: url("{{ public_path('storage/fonts/COMIC.ttf') }}") format('truetype');
} */

body {
    font-family: 'comic', sans-serif;
    font-size: 12px;
    line-height: 1.4;
}

/* TABLE HEADER GAUCHE / DROITE */
.header-table {
    width: 100%;
    border-collapse: collapse;
}
.header-table td {
    width: 50%;
    vertical-align: top;
    font-size: 12px;
}

/* TITRE PRINCIPAL */
.title {
    text-align: center;
    font-weight: bold;
    font-size: 18px;
    margin: 10px 0;
    text-decoration: underline;
}

/* CONTENU TEXTE */
.content {
    margin-top: 10px;
    font-size: 13px;
    text-align: justify;
}

/* SIGNATURE GAUCHE */
.signature-block {
    margin-top: 60px;
    text-align: right;
    font-size: 13px;
}

.bold { font-weight: bold; }
.underline { text-decoration: underline; }

.logo-ecole {
    margin-top: 5px;
    width: 80px;
}
.line-wrapper {
    display: inline-block;
    width: 100%;
}

.label-text {
    display: inline-block;
}

.dotted-fill {
    display: inline-block;
    width: 80%;
    border-bottom: 1px dotted #000;
}


</style>
</head>

<body>

<table class="header-table">
    <tr>
        <!-- GAUCHE -->
        <td>
            <span  style="text-align:center" class="bold">MINISTERE DE L’EDUCATION NATIONALE<br>
            ET DE L’ALPHABETISATION</span><br>
            --------------------<br>
            DIRECTION REGIONALE DE {{ $inscription->ecole->ville ?? '' }}<br>
            I.E.P.P : {{ $inscription->ecole->nom ?? '' }}<br>
            Secteur pédagogique : {{ $inscription->ecole->secteur ?? '' }}<br>
            E.PV : {{ $inscription->ecole->nom ?? '' }}

            <br><br>
            <!-- LOGO ECOLE -->
            @if(isset($inscription->ecole->logo))
                <img src="{{ public_path('storage/'.$inscription->ecole->logo) }}" class="logo-ecole">
            @endif
        </td>

        <!-- DROITE -->
        <td style="text-align:right;">
            <span class="bold">REPUBLIQUE DE COTE D'IVOIRE</span><br>
            Union - Discipline - Travail<br><br>
            Année scolaire : {{ $inscription->anneeScolaire->debut }} / {{ $inscription->anneeScolaire->fin }}
        </td>
    </tr>
</table>

<div class="title">CERTIFICAT DE FREQUENTATION</div>

<div class="content">
    Je soussigné,<br><br>

    <span class="bold">{{ $inscription->ecole->directeur ?? '' }}</span>,
    Directeur des Etudes de l’E.PV <span class="bold">{{ $inscription->ecole->nom }}</span>,<br><br>

    Atteste que l’élève 
    <span class="bold underline">{{ strtoupper($inscription->eleve->nom) }} {{ ucfirst($inscription->eleve->prenom) }}</span><br><br>

    Matricule : {{ $inscription->eleve->code_national ?? $inscription->eleve->matricule }} /
    Acte de naissance N° {{ $inscription->eleve->num_extrait ?? '...........' }}<br><br>

    Né(e) le {{ $inscription->eleve->naissance->format('d/m/Y') }} 
    à {{ $inscription->eleve->lieu_naissance ?? '.........................' }}<br><br>

    Cours suivi : <span class="bold">{{ $inscription->classe->nom }}</span><br><br>

<div >
    <span >Fils/Fille de :</span>
    <span >{{ $inscription->eleve->parent_nom }}</span>
</div><br>

<div class="line-wrapper">
    <span class="label-text">Et de :</span>
    <span class="dotted-fill">{{ $inscription->eleve->parent_nom2 ?? '' }}</span>
</div><br><br>


    Est effectivement inscrit à l’E.PV <span class="bold">{{ $inscription->ecole->nom }}</span>.<br><br>

    Depuis le {{ $inscription->eleve->created_at->format('d/m/Y') }} 
    à ce jour {{ now()->format('d/m/Y') }}.<br><br>

    En foi de quoi, cette attestation lui est délivrée pour servir et valoir ce que de droit.
</div>

<div class="signature-block">
    Fait à {{ $inscription->ecole->ville ?? 'Korogho' }}, le {{ now()->format('d/m/Y') }}<br><br><br>

    <span class="bold underline">LE DIRECTEUR DES ETUDES</span><br><br><br>

    <span class="bold ">{{ $inscription->ecole->directeur ?? '' }}</span><br>
    
</div>

</body>
</html>
