<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Bulletin Annuel - {{ $classe->nom }}</title>
<style>
body {
    font-family: 'Georgia', Times, serif;
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
    border-bottom: none;
    box-sizing: border-box;
}
.page-break {
    page-break-after: always;
}
.header {
    width: 100%;
    min-height: 15mm;
    overflow: hidden;
}
.clearfix { clear: both; }
.bulletin-row {
    width: 100%;
    border-collapse: collapse;
}
.bulletin-row td { padding: 2mm 1mm; vertical-align: middle; font-weight: bold; }
table.general { 
    width: 100%; 
    border-collapse: collapse; 
    margin-bottom: 3mm; 
}
table.general, table.general th, table.general td { 
    border: 1px solid black; 
}
table.general th, table.general td { 
    padding: 2mm; 
    text-align: center; 
}
table.general th { 
    background: #ccc; 
}
.left { 
    text-align: left; 
    padding-left: 2mm; 
}
.period-badge {
    background-color: #f0f0f0;
    padding: 2mm;
    margin-bottom: 3mm;
    text-align: center;
    font-size: 11px;
}
.recap-composition {
    font-size: 11px;
    line-height: 1.3;
}
.recap-composition table {
    width: 100%;
    border-collapse: collapse;
}
.recap-composition td {
    padding: 2px 4px;
    border: none;
}
.mois-item {
    margin-bottom: 3px;
}
</style>
</head>
<body>

@php
    use Carbon\Carbon;
    $ecole = \App\Models\Ecole::find(session('current_ecole_id'));
@endphp

@foreach($elevesAvecMoyennes as $eleveData)

    <!-- En-tête supérieur -->
    <div style="width:100%; overflow:hidden;">
        <div style="float:left; width:50%; font-weight:bold;text-transform:uppercase;">
            {{ $ecole->nom }}
        </div>
        <div style="float:right; width:50%; text-align:right;">
            Édition : {{ Carbon::now()->format('d/m/Y') }}
        </div>
        <div class="clearfix"></div>
    </div>

    <!-- En-tête principal -->
    <hr>
    <div class="header" style="width:100%; overflow:hidden">
        <div style="float:left; width:15%; text-align:center;">
            <img src="{{ $ecole->logo ?? 'https://upload.wikimedia.org/wikipedia/commons/6/6d/Logo_ministere_education_civ.png' }}" alt="Logo école" style="width:100%;">
        </div>
        <div style="float:left; width:50%; text-align:center; border:1px solid #000; padding:2mm; box-sizing:border-box;border-radius:10px;">
            <b>RÉPUBLIQUE DE CÔTE D'IVOIRE</b><br>
            MINISTÈRE DE L'ÉDUCATION NATIONALE ET DE L'ALPHABÉTISATION<br>
            <span>...........................</span><br>
            <b>{{ $ecole->nom }}</b>
        </div>
        <div style="float:right; width:30%; text-align:left; border:1px solid #000; padding:2mm; box-sizing:border-box; border-radius:10px;">
            Code : <b>{{ $ecole->code ?? '' }}</b><br>
            Adresse : <b>{{ $ecole->adresse ?? '' }}</b><br>
            Tél. / Fax : <b>{{ $ecole->telephone ?? '' }}</b> / <b>{{ $ecole->fax ?? '0274839310' }}</b><br>
            Email : <b>{{ $ecole->email ?? '' }}</b><br>
        </div>
        <div style="clear:both;"></div>
    </div>

    <!-- Titre Bulletin Annuel -->
    <table class="bulletin-row" style="width:100%; text-align:center; font-size:16px; text-transform:uppercase; border-collapse:collapse;">
        <tr>
            <td style="text-align:center; width:70%;">
                <strong>BULLETIN ANNUEL</strong>
            </td>
            <td style="text-align:right; width:30%;">
                {{ $anneeScolaire->annee ?? $anneeScolaire->debut.'-'.$anneeScolaire->fin }}
            </td>
        </tr>
    </table>
    

    <div class="container">

        <!-- Informations élève -->
        <table style="width:100%; border-collapse:collapse;margin-bottom:8px;">
            <tr style="text-transform:uppercase;background:#ccc">
                <td style="text-align:left; width:70%; padding:5px;">
                    <b>{{ strtoupper($eleveData['inscription']->eleve->nom) }} {{ strtoupper($eleveData['inscription']->eleve->prenom) }}</b> 
                </td>
                <td style="text-align:right; width:30%; padding:5px;">
                    <b>Matricule :</b> {{ $eleveData['inscription']->eleve->code_national ?? $eleveData['inscription']->eleve->matricule }}
                </td>
            </tr>
            <tr>
                <td style="vertical-align:top; padding:5px; width:70%;">
                    <table style="width:100%; border-collapse:collapse; table-layout:fixed;">
                        <tr>
                            <td style="padding:6px; width:60%;">
                                <b>Classe :</b> {{ $classe->nom }}
                            </td>
                            <td style="padding:6px; width:40%; text-align:left;">
                                <b>Effectif :</b> {{ $effectif }}
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:6px;">
                                <b>Sexe :</b> {{ $eleveData['inscription']->eleve->sexe ?? '' }}
                            </td>
                            <td style="padding:6px; text-align:left;">
                                <b>Né(e) le :</b> {{ $eleveData['inscription']->eleve->naissance_formattee ?? '' }}
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:6px;">
                                <b>Nom du parent :</b> {{ strtoupper($eleveData['inscription']->eleve->parent_nom ?? '') }}
                            </td>
                            <td style="padding:6px;">
                                <b>Téléphone :</b> {{ $eleveData['inscription']->eleve->parent_telephone ?? '' }}
                            </td>
                        </tr>

                    </table>
                </td>
                <td style="width:30%; padding:5px;">
                    <div style="width:100px; height:80px; border:1px solid #000; padding:4px;float:right; box-sizing:border-box; text-align:center;">
                        <img src="{{ $eleveData['inscription']->eleve->photo_path && file_exists(storage_path('app/public/' . $eleveData['inscription']->eleve->photo_path))
                                ? storage_path('app/public/' . $eleveData['inscription']->eleve->photo_path)
                                : public_path('images/default.png') }}"
                        alt="Photo"
                        style="width:80px; height:80px; object-fit:cover; border-radius:5px;">
                    </div>
                </td>
            </tr>
        </table>

        <!-- Matières avec moyenne annuelle -->
        <table class="general">
            <thead>
                <tr>
                    <th>MATIÈRES</th>
                    <th>Moyenne Annuelle</th>
                    <th>Coeff.</th>
                    <th>Rang</th>
                    <th>Appréciation</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $matieresAvecNotes = $matieres->filter(function ($matiere) use ($eleveData) {
                        return $eleveData['notes']->firstWhere('matiere_id', $matiere->id);
                    });
                @endphp

                @foreach($matieresAvecNotes as $matiere)
                    @php
                        $note = $eleveData['notes']->firstWhere('matiere_id', $matiere->id);
                    @endphp
                    <tr>
                        <td class="left">{{ $matiere->nom }}</td>
                        <td>
                            @if($note && $note->valeur > 0)
                                {{ number_format($note->valeur, 2, ',', '') }} / {{ $note->base }}
                            @else
                                &nbsp;
                            @endif
                        </td>
                        <td>{{ $note->coefficient ?? '' }}</td>
                        <td>{{ $note->rang_matiere_text ?? '-' }}</td>
                        <td>{{ $note->appreciation ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totaux -->
        <table class="general" style="width:100%; border-collapse: collapse;">
            <tr style="background:#ccc; text-align:center;">
                <td style="padding:5px;">
                    <b>MOYENNE ANNUELLE :</b> {{ number_format($eleveData['moyenne'], 2, ',', '') }} / {{ number_format($classe->moy_base, 0, '', '') }} &nbsp; | &nbsp; 
                    <b>RANG :</b> {{ $eleveData['rang_text'] }} / {{ $effectif }} &nbsp; | &nbsp;
                    <b>APPRÉCIATION :</b> {{ $eleveData['mention'] }}
                </td>
            </tr>
        </table>

        <!-- Tableau avec 4 colonnes : RÉCAP DES COMPOSITIONS | RÉSULTAT DE CLASSE | DISTINCTIONS | SANCTIONS -->
        <table class="general">
            <thead>
                <tr>
                    <th style="width: 25%;">RÉCAPITULATIF DES COMPOSITIONS</th>
                    <th style="width: 25%;">RÉSULTAT DE CLASSE</th>
                    <th style="width: 25%;">DISTINCTIONS</th>
                    <th style="width: 25%;">SANCTIONS</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <!-- Colonne 1: Récapitulatif des compositions par mois -->
                    <td style="text-align: left; vertical-align: top; padding: 3mm;">
                        @if(isset($eleveData['moyennes_par_mois']) && count($eleveData['moyennes_par_mois']) > 0)
                            @foreach($eleveData['moyennes_par_mois'] as $item)
                                <div style="margin-bottom: 5px;">
                                    <strong>{{ $item['mois'] }} :</strong> 
                                    {{ number_format($item['moyenne'], 2, ',', '') }} 
                                    (Rang: {{ $item['rang'] }}e/{{ $item['effectif'] }})
                                </div>
                            @endforeach
                        @else
                            <div style="color: #999;">Aucune donnée disponible</div>
                        @endif
                    </td>
                    
                    <!-- Colonne 2: Résultat de classe -->
                    <td style="text-align: left; vertical-align: top;">
                        Plus forte moyenne: <strong>{{ number_format($moyPremier, 2, ',', '') }}</strong><br>
                        Plus faible moyenne: <strong>{{ number_format($moyDernier, 2, ',', '') }}</strong><br>
                        Moyenne de la Classe: <strong>{{ number_format($moyClasse, 2, ',', '') }}</strong><br>
                    </td>
                    
                    <!-- Colonne 3: Distinctions -->
                    <td style="text-align: left; vertical-align: top;">
                        <span class="checkbox">
                            {{ isset($eleveData['distinctions']['tableau_honneur']) && $eleveData['distinctions']['tableau_honneur'] ? '☑' : '□' }}
                        </span> Tableau d'Honneur<br>

                        <span class="checkbox">
                            {{ isset($eleveData['distinctions']['encouragement']) && $eleveData['distinctions']['encouragement'] ? '☑' : '□' }}
                        </span> Encouragement<br>

                        <span class="checkbox">
                            {{ isset($eleveData['distinctions']['felicitation']) && $eleveData['distinctions']['felicitation'] ? '☑' : '□' }}
                        </span> Félicitations
                    </td>
                    
                    <!-- Colonne 4: Sanctions -->
                    <td style="text-align: left; vertical-align: top;">
                        <span class="checkbox">
                            {{ isset($eleveData['sanctions']['avertissement_travail']) && $eleveData['sanctions']['avertissement_travail'] ? '☑' : '□' }}
                        </span> Avertissement travail<br>

                        <span class="checkbox">
                            {{ isset($eleveData['sanctions']['blame_travail']) && $eleveData['sanctions']['blame_travail'] ? '☑' : '□' }}
                        </span> Blâme travail<br>

                        <span class="checkbox">
                            {{ isset($eleveData['sanctions']['avertissement_conduite']) && $eleveData['sanctions']['avertissement_conduite'] ? '☑' : '□' }}
                        </span> Avertissement conduite<br>

                        <span class="checkbox">
                            {{ isset($eleveData['sanctions']['blame_conduite']) && $eleveData['sanctions']['blame_conduite'] ? '☑' : '□' }}
                        </span> Blâme conduite
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Appreciation du conseil de classe -->
        <table class="general">
            <thead>
                <tr>
                    <th>Appreciation du conseil de classe</th>
                    <th>Visa du directeur</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="vertical-align: top;">
                        
                        <span style="text-decoration: underline;">L'enseignant</span>
                        <br> <br> <br>
                        <br><br>
                        {{ strtoupper($eleveData['inscription']->classe->enseignant->nom_prenoms ?? '___________________') }}
                    </td>
                    <td style="text-align: center; vertical-align: bottom;">
                        {{ $ecole->ville ?? 'Korhogo' }} le {{ Carbon::now()->format('d/m/Y') }}<br>
                        <span style="text-decoration: underline;">Le Directeur des Etudes</span><br>
                        <br><br><br>
                        {{ $ecole->directeur ?? '___________________' }}
                    </td>
                </tr>
            </tbody>
        </table>

    </div>
    
    <p style="text-decoration: underline; text-align:center; font-size:10px;">
        Bulletin annuel informatisé : Ne doit contenir ni rature, ni grattage.
    </p>

    @if(!$loop->last)
        <div class="page-break"></div>
    @endif
@endforeach

</body>
</html>