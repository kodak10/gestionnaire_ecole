<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Bulletin Scolaire</title>
<style>
body {
    font-family: "Times New Roman", serif;
    font-size: 12px;
    margin: 0;
    padding: 0;
    color: #000;
}
.container {
    width: 180mm;
    margin: 0 auto;
    border: 1px solid #000;
    padding: 5mm;
    page-break-after: always;
    box-sizing: border-box;
}
.header {
    width: 100%;
    border-top: 1px solid #000;
    margin-bottom: 5mm;
    min-height: 30mm;
    overflow: hidden;
}
.header-left { float: left; width: 32%; text-align: center; }
.header-center { float: left; width: 36%; text-align: center; font-weight: bold; font-size: 13px; }
.header-right { float: right; width: 32%; text-align: center; font-size: 11px; }
.header img { height: 25mm; }
.clearfix { clear: both; }

.bulletin-row {
    width: 100%;
    margin-top: 2mm;
    margin-bottom: 4mm;
    border: 2px solid #000;
    border-collapse: collapse;
}
.bulletin-row td { padding: 2mm 1mm; vertical-align: middle; font-weight: bold; }
.bulletin-left { text-align: left; padding-left: 2mm; }
.bulletin-right { text-align: right; padding-right: 2mm; }

.student-info-wrapper {
    width: 100%;
    margin-bottom: 4mm;
    border-left: 2px solid #000;
    border-right: 2px solid #000;
    padding: 2mm 0;
    overflow: hidden;
}
.student-left { float: left; width: 68%; padding-right: 2%; box-sizing: border-box; }
.student-left .info-card table { width: 100%; border-collapse: collapse; }
.student-left .info-card td { border: none; padding: 2px 4px; text-align: left; vertical-align: top; }
.photo-box { float: right; width: 30%; height: 120px; box-sizing: border-box; text-align: center; border: 1px solid #000; padding: 0; }
.photo-box img { width: 100%; height: 100%; object-fit: cover; display: block; }

table.general { width: 100%; border-collapse: collapse; margin-bottom: 3mm; }
table.general, table.general th, table.general td { border: 1px solid black; }
table.general th, table.general td { padding: 2mm; text-align: center; }
table.general th { background: #ccc; }
.left { text-align: left; padding-left: 2mm; }

.signature { margin-top: 5mm; width: 100%; overflow: hidden; }
.signature div { float: left; width: 40%; border-top: 1px solid #000; text-align: center; padding-top: 2mm; font-weight: bold; }
.signature div:last-child { float: right; }
</style>
</head>
<body>

@php
    use Carbon\Carbon;
    $ecole = \App\Models\Ecole::find(session('current_ecole_id'));
@endphp

@foreach($elevesAvecMoyennes as $eleveData)

    <!-- En-tête supérieur : nom école à gauche, date d'édition à droite -->
    <div style="width:100%; margin-bottom:3mm; overflow:hidden;">
        <div style="float:left; width:50%; font-weight:bold;">
            {{ $ecole->nom ?? 'Nom École' }}
        </div>
        <div style="float:right; width:50%; text-align:right;">
            Édition : {{ Carbon::now()->format('d/m/Y') }}
        </div>
        <div class="clearfix"></div>
    </div>

    <!-- En-tête principal -->
    {{-- <div class="header">
        <div class="header-left">
            <img src="{{ $ecole->logo ?? 'https://upload.wikimedia.org/wikipedia/commons/6/6d/Logo_ministere_education_civ.png' }}" alt="Logo école">
        </div>
        <div class="header-center">
            MINISTÈRE DE L’ÉDUCATION NATIONALE<br>
            DE L’ENSEIGNEMENT TECHNIQUE ET DE LA FORMATION PROFESSIONNELLE<br>
            <b>{{ $classe->nom }}</b>
        </div>
        <div class="header-right">
            Adresse : {{ $classe->adresse ?? '...' }}<br>
            Téléphone : {{ $classe->telephone ?? '...' }}<br>
            Code : {{ $classe->code ?? '...' }}<br>
            Statut : {{ $classe->statut ?? '...' }}
        </div>
        <div class="clearfix"></div>
    </div> --}}
    <!-- En-tête principal -->
    <<!-- En-tête principal -->
<div class="header" style="width:100%; overflow:hidden; margin-bottom:5mm;">
    <!-- Logo -->
    <div style="float:left; width:20%; text-align:center;">
        <img src="{{ $ecole->logo ?? 'https://upload.wikimedia.org/wikipedia/commons/6/6d/Logo_ministere_education_civ.png' }}" alt="Logo école" style=" width:100%;">
    </div>

    <!-- Partie centrale (plus large) -->
    <div style="float:left; width:50%; text-align:center; border:1px solid #000; padding:2mm; box-sizing:border-box;border-radius:10px;">
        MINISTÈRE DE L’ÉDUCATION NATIONALE<br>
        DE L’ENSEIGNEMENT TECHNIQUE ET DE LA FORMATION PROFESSIONNELLE<br>
        <span>...........................</span><br>
        <b>{{ $ecole->nom }}</b>
    </div>

    <!-- Partie droite -->
    <div style="float:right; width:25%; text-align:center; border:1px solid #000; padding:2mm; box-sizing:border-box; border-radius:10px;">
        Adresse : {{ $classe->adresse ?? '...' }}<br>
        Téléphone : {{ $classe->telephone ?? '...' }}<br>
        Code : {{ $classe->code ?? '...' }}<br>
        Statut : {{ $classe->statut ?? '...' }}
        <br><br>
    </div>

    <div style="clear:both;"></div>
</div>



<div class="container">


    <!-- Bulletin / Année -->
    <table class="bulletin-row">
    <tr>
        <td class="bulletin-left"><strong>BULLETIN DE NOTES : {{ $mois->nom }}</strong></td>
        <td class="bulletin-right">
            <div style="width:100%; overflow:hidden;">
                <div style="float:left; font-weight:bold;">
                    {{ $ecole->nom ?? 'Nom École' }}
                </div>
                <div style="float:right; text-align:right;">
                    Édition : {{ \Carbon\Carbon::now()->format('d/m/Y') }}
                </div>
                <div class="clearfix"></div>
            </div>
        </td>
    </tr>
</table>


    <!-- Informations élève -->
    <div class="student-info-wrapper">
        <div class="student-left">
            <div class="info-card">
                <table>
                    <tr>
                        <td><b>Nom et Prénoms :</b> {{ $eleveData['inscription']->eleve->nom_complet }}</td>
                        <td><b>Matricule :</b> {{ $eleveData['inscription']->eleve->matricule }}</td>
                    </tr>
                    <tr>
                        <td><b>Classe :</b> {{ $classe->nom }}</td>
                        <td><b>Sexe :</b> {{ $eleveData['inscription']->eleve->sexe }}</td>
                    </tr>
                    <tr>
                        <td><b>Effectif :</b> {{ $effectif }}</td>
                        <td><b>Nationalité :</b> {{ $eleveData['inscription']->eleve->nationalite ?? '...' }}</td>
                    </tr>
                    <tr>
                        <td><b>Née le :</b> {{ $eleveData['inscription']->eleve->date_naissance }}</td>
                        <td><b>Lieu :</b> {{ $eleveData['inscription']->eleve->lieu_naissance ?? '...' }}</td>
                    </tr>
                    <tr>
                        <td><b>Redoublant :</b> {{ $eleveData['inscription']->eleve->redoublant ? 'OUI' : 'NON' }}</td>
                        <td><b>Situation :</b> {{ $eleveData['inscription']->eleve->situation ?? '...' }}</td>
                    </tr>
                </table>
            </div>
        </div>
        <div class="photo-box">
            <img src="{{ $eleveData['inscription']->eleve->photo ?? 'https://randomuser.me/api/portraits/lego/1.jpg' }}" alt="Photo élève">
        </div>
        <div class="clearfix"></div>
    </div>

    <!-- Matières -->
    <table class="general">
        <thead>
            <tr>
                <th>MATIÈRES</th>
                <th>Moy.</th>
                <th>Coeff.</th>
                <th>M. x C</th>
                <th>Rang</th>
                <th>Appréciation</th>
            </tr>
        </thead>
        <tbody>
        @foreach($eleveData['notes'] as $note)
            <tr>
                <td class="left">{{ $note->matiere->nom }}</td>
                <td>{{ number_format($note->valeur,2,',','') }}</td>
                <td>{{ $note->coefficient ?? 1 }}</td>
                <td>{{ number_format(($note->valeur * ($note->coefficient ?? 1)),2,',','') }}</td>
                <td>{{ $note->rang_matiere ?? '-' }}</td>
                <td>{{ $note->appreciation ?? '-' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <!-- Totaux -->
    <table class="general">
        <tr>
            <td><b>TOTAUX</b></td>
            <td>{{ number_format($eleveData['total_notes'],2,',','') }}</td>
            <td><b>MOYENNE :</b></td>
            <td>{{ number_format($eleveData['moyenne'],2,',','') }}</td>
            <td><b>RANG :</b></td>
            <td>{{ $eleveData['rang_general'] }} / {{ $effectif }}</td>
        </tr>
    </table>

    

    <!-- Résultats et distinctions -->
    <table class="general">
        <thead>
            <tr>
                <th>RÉSULTAT DE CLASSE</th>
                <th>DISTINCTIONS</th>
                <th>SANCTIONS</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    Moy Premier: {{ $moyPremier }}<br>
                    Moy Dernier: {{ $moyDernier }}<br>
                    Moy Classe: {{ $moyClasse }}<br><br>
                    0 - 8,49 | 8,50 - 9,99 | 10 - 20
                </td>
                <td>
                    ☐ Tableau d’Honneur<br>
                    ☐ Tableau d’Honneur + Encouragement<br>
                    ☐ Tableau d’Honneur + Félicitation
                </td>
                <td>
                    ☐ Avertissement pour travail insuffisant<br>
                    ☐ Blâme pour travail insuffisant<br>
                    ☐ Avertissement pour mauvaise conduite<br>
                    ☐ Blâme pour mauvaise conduite
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Appreciation du conseil de classe et visa du chef d'etabilssement -->
    <table class="general">
        <thead>
            <tr>
                <th>Appreciation du conseil de classe</th>
                <th>Visa du chef d'etabilssement</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    L'enseignant<br> {{ $enseignantPrincipal->nom_complet ?? '...' }}
                    <br><br><br>
                </td>
                <td>
                    {{ $ecole->lieu ?? '...' }} le {{ Carbon::now()->format('d/m/Y') }}<br>
                    <br>
                    
                </td>
                
            </tr>
        </tbody>
    </table>

    <!-- Recapitulatif des compo et decision de fin d'année -->
    <table class="general">
        <thead>
            <tr>
                <th>Recapitulatif des compo</th>
                <th>decision de fin d'année</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    
                </td>
                <td>
                    
                </td>
                
            </tr>
        </tbody>
    </table>


</div>
@endforeach

</body>
</html>
