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
.checkbox {
    font-family: DejaVu Sans, sans-serif;
    font-size: 14px;
}

.container {
    border-top: 1px solid #000;
    border-left: 1px solid #000;
    border-right: 1px solid #000;
    border-bottom: none; /* ✅ Enlève la bordure du bas */
    box-sizing: border-box;
}

.page-break {
    page-break-after: always;
}
.header {
    width: 100%;
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
    <hr>
    <div class="header" style="width:100%; overflow:hidden">
        <!-- Logo -->
        <div style="float:left; width:15%; text-align:center;">
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
        <div style="float:right; width:30%; text-align:left; border:1px solid #000; padding:2mm; box-sizing:border-box; border-radius:10px;">
            Code : <b>{{ $ecole->code ?? '' }}</b><br>
            Adresse : <b>{{ $ecole->adresse ?? '' }}</b><br>
            Tél. / Fax : <b>{{ $ecole->telephone ?? '' }}</b> / <b>{{ $ecole->fax ?? '0274839310' }}</b><br>
            <br><br>
        </div>

        <div style="clear:both;"></div>
    </div>

    <!-- Bulletin / Année -->
    <table class="bulletin-row" style="width:100%; text-align:center; font-size:16px; text-transform:uppercase; border-collapse:collapse;">
        <tr>
            <!-- Colonne pour le titre -->
            <td style="text-align:center; width:70%;">
                <strong>BULLETIN DE NOTES : {{ $mois->nom }}</strong>
            </td>

            <!-- Colonne pour l'année à droite -->
            <td style="text-align:right; width:30%;">
                {{ $anneeScolaire->annee ?? 'Année' }}
            </td>
        </tr>
    </table>


    <div class="container">

        <!-- Informations élève -->
        <table style="width:100%; border-collapse:collapse;">
            <!-- Première ligne : Matricule + Nom et Prénoms -->
            <tr style="text-transform:uppercase;background:#ccc">
                <td style="text-align:left; width:70%; padding:5px;text-transform:uppercase;">
                    <b>{{ strtoupper($eleveData['inscription']->eleve->nom_complet) }}</b> 
                </td>
                <td style="text-align:right; width:30%; padding:5px;">
                    <b>Matricule :</b> {{ $eleveData['inscription']->eleve->matricule }}
                </td>
                
            </tr>

            <!-- Deuxième ligne : infos + photo -->
            <tr>
                <!-- Infos élève -->
                <td style="vertical-align:top; padding:5px;">
                    <table style="width:100%; border-collapse:collapse; table-layout:fixed;">
                        <tr>
                            <!-- Colonne gauche large -->
                            <td style="padding:6px; width:60%;">
                                <b>Classe :</b> {{ $classe->nom }}
                            </td>

                            <!-- Colonne droite pour effectif -->
                            <td style="padding:6px; width:30%; text-align:left;">
                                <b>Effectif :</b> {{ $effectif }}
                            </td>
                        </tr>

                        <tr>
                            <td style="padding:6px;">
                                <b>Sexe :</b> {{ $eleveData['inscription']->eleve->sexe ?? '' }}
                            </td>
                            <td style="padding:6px; text-align:left;">
                                <b>Né(e) le :</b> {{ $eleveData['inscription']->eleve->naissance_formattee }} à
                                <br>
                                {{ strtoupper($eleveData['inscription']->eleve->lieu_naissance ?? '') }}
                            </td>
                            
                        </tr>

                        <tr>
                            <td style="padding:6px; text-transform:uppercase;">
                                <b>Nom du parent :</b> {{ $eleveData['inscription']->eleve->parent_nom ?? '' }}
                            </td>
                            <td style="padding:6px; text-align:left;">
                                <b>Téléphone :</b> {{ $eleveData['inscription']->eleve->parent_telephone ?? '' }}
                            </td>
                            
                        </tr>
                    </table>
                </td>

                <!-- Photo -->
                <td style="text-align:center; vertical-align:middle; width:30%; padding:5px;">
                    <div style="width:100%; height:80px; border:1px solid #000; padding:4px; display:inline-block;">
                    <img src="{{ $eleveData['inscription']->eleve->photo_path && file_exists(storage_path('app/public/' . $eleveData['inscription']->eleve->photo_path))
                            ? storage_path('app/public/' . $eleveData['inscription']->eleve->photo_path)
                            : public_path('images/default.png') }}"
                    alt="Photo"
                    style="width:100px; height:80px; object-fit:cover; border-radius:5px;">

                    </div>
                </td>
            </tr>

        </table>

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
        <table class="general" style="width:100%; border-collapse: collapse;">
            <tr style="background:#ccc; text-align:center;">
                <td style="padding:5px;">
                    <b>MOYENNE :</b> {{ number_format($eleveData['moyenne'],2,',','') }} /20 &nbsp; | &nbsp;
                    <b>RANG :</b> {{ $eleveData['rang_general'] }} / {{ $effectif }} &nbsp; | &nbsp;
                    <b>APPRÉCIATION :</b> {{ $eleveData['mention'] }}
                </td>
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
                        Plus forte moyenne: {{ $moyPremier }}<br>
                        Plus faible moyenne: {{ $moyDernier }}<br>
                        Moyenne de la Classe: {{ $moyClasse }}<br><br>
                        
                    </td>
                    <td>
                        <span class="checkbox">{{ $eleveData['distinctions']['tableau_honneur'] ? '☑' : '□' }}</span> Tableau d'Honneur<br>
                        <span class="checkbox">{{ $eleveData['distinctions']['encouragement'] ? '☑' : '□' }}</span> Tableau d'Honneur + Encouragement<br>
                        <span class="checkbox">{{ $eleveData['distinctions']['felicitation'] ? '☑' : '□' }}</span> Tableau d'Honneur + Félicitation
                    </td>
                    <td>
                        <span class="checkbox">{{ $eleveData['sanctions']['avertissement_travail'] ? '☑' : '□' }}</span> Avertissement pour travail insuffisant<br>
                        <span class="checkbox">{{ $eleveData['sanctions']['blame_travail'] ? '☑' : '□' }}</span> Blâme pour travail insuffisant<br>
                        <span class="checkbox">{{ $eleveData['sanctions']['avertissement_conduite'] ? '☑' : '□' }}</span> Avertissement pour mauvaise conduite<br>
                        <span class="checkbox">{{ $eleveData['sanctions']['blame_conduite'] ? '☑' : '□' }}</span> Blâme pour mauvaise conduite
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Appreciation du conseil de classe et visa du chef d'etabilssement -->
        <table class="general">
            <thead>
                <tr>
                    <th>Appreciation du conseil de classe</th>
                    <th>Visa du directeur</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        L'enseignant <br>
                        <br> <br> <br> <br>
                        <br>
{{ strtoupper($eleveData['inscription']->classe->enseignant->nom_prenoms ?? '...') }}
                    </td>
                    <td>
                        {{ $ecole->adresse ?? '...' }} le {{ Carbon::now()->format('d/m/Y') }}<br>
                        <span style="text-decoration: underline;">Le Directeur des Etudes</span> <br>

                        <br> <br> <br> <br>
                        {{ strtoupper($ecole->directeur ?? '...') }}
                    </td>
                </tr>
            </tbody>
        </table>


    </div>
        <span style="text-decoration: underline; text-align:center">Bulletin informatisé : Ne doit contenir ni rature, ni grattage. Aucun duplicata ne sera délivré.</span>

    @if(!$loop->last)
        <div class="page-break"></div>
    @endif
@endforeach

</body>
</html>
