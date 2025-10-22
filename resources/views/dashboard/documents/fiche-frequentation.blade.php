<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Fiche de Fréquentation - {{ $inscription->eleve->nom }}</title>
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
            max-width: 80px;
            max-height: 80px;
        }
        .content { margin: 20px 0; }
        .section { margin-bottom: 15px; }
        .field { margin-bottom: 8px; }
        .label { font-weight: bold; display: inline-block; width: 180px; }
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
        .table-frequentation {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 10px;
        }
        .table-frequentation, .table-frequentation th, .table-frequentation td {
            border: 1px solid #000;
        }
        .table-frequentation th, .table-frequentation td {
            padding: 4px;
            text-align: center;
        }
        .table-frequentation th {
            background: #f0f0f0;
        }
        .mois-section {
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <!-- En-tête avec logo à gauche et infos au centre -->
    <div class="header">
        <div class="logo-section">
            @if(auth()->user()->ecole && auth()->user()->ecole->logo)
                <img src="{{ storage_path('app/public/' . auth()->user()->ecole->logo) }}" class="logo" alt="Logo École">
            @else
                <div style="width: 80px; height: 80px; border: 1px solid #000; margin: 0 auto; display: table-cell; vertical-align: middle; text-align: center;">
                    LOGO
                </div>
            @endif
        </div>
        <div class="title-section">
            <h2 style="margin: 0; font-size: 18px;">FICHE DE FRÉQUENTATION</h2>
            <h3 style="margin: 5px 0; font-size: 16px;">{{ auth()->user()->ecole->nom ?? 'GS EXCELLE' }}</h3>
            <p style="margin: 0; font-size: 12px;">Année Scolaire: {{ $inscription->anneeScolaire->annee }}</p>
        </div>
        <div style="clear: both;"></div>
    </div>

    <div class="content">
        <!-- Informations de l'élève -->
        <div class="section">
            <h4 style="background: #f0f0f0; padding: 5px; margin-bottom: 10px;">INFORMATIONS DE L'ÉLÈVE</h4>
            <div class="field"><span class="label">Matricule:</span> {{ $inscription->eleve->matricule }}</div>
            <div class="field"><span class="label">Nom et Prénom:</span> {{ $inscription->eleve->nom }} {{ $inscription->eleve->prenom }}</div>
            <div class="field"><span class="label">Classe:</span> {{ $inscription->classe->nom }}</div>
            <div class="field"><span class="label">Date de Naissance:</span> {{ $inscription->eleve->naissance->format('d/m/Y') }}</div>
        </div>

        <!-- Tableau de fréquentation par mois -->
        <div class="section">
            <h4 style="background: #f0f0f0; padding: 5px; margin-bottom: 10px;">SUIVI DE FRÉQUENTATION</h4>
            
            <!-- Mois 1 -->
            <div class="mois-section">
                <h5 style="margin: 10px 0 5px 0;">Mois: ____________________</h5>
                <table class="table-frequentation">
                    <thead>
                        <tr>
                            <th>Semaine</th>
                            <th>Lundi</th>
                            <th>Mardi</th>
                            <th>Mercredi</th>
                            <th>Jeudi</th>
                            <th>Vendredi</th>
                            <th>Samedi</th>
                            <th>Total Présences</th>
                            <th>Total Absences</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for($semaine = 1; $semaine <= 4; $semaine++)
                        <tr>
                            <td>Semaine {{ $semaine }}</td>
                            <td>□</td>
                            <td>□</td>
                            <td>□</td>
                            <td>□</td>
                            <td>□</td>
                            <td>□</td>
                            <td></td>
                            <td></td>
                        </tr>
                        @endfor
                        <tr style="font-weight: bold;">
                            <td>TOTAL MOIS</td>
                            <td colspan="6"></td>
                            <td></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mois 2 -->
            <div class="mois-section">
                <h5 style="margin: 10px 0 5px 0;">Mois: ____________________</h5>
                <table class="table-frequentation">
                    <thead>
                        <tr>
                            <th>Semaine</th>
                            <th>Lundi</th>
                            <th>Mardi</th>
                            <th>Mercredi</th>
                            <th>Jeudi</th>
                            <th>Vendredi</th>
                            <th>Samedi</th>
                            <th>Total Présences</th>
                            <th>Total Absences</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for($semaine = 1; $semaine <= 4; $semaine++)
                        <tr>
                            <td>Semaine {{ $semaine }}</td>
                            <td>□</td>
                            <td>□</td>
                            <td>□</td>
                            <td>□</td>
                            <td>□</td>
                            <td>□</td>
                            <td></td>
                            <td></td>
                        </tr>
                        @endfor
                        <tr style="font-weight: bold;">
                            <td>TOTAL MOIS</td>
                            <td colspan="6"></td>
                            <td></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Légende -->
        <div class="section">
            <h4 style="background: #f0f0f0; padding: 5px; margin-bottom: 10px;">LÉGENDE</h4>
            <div class="field"><span class="label">□ Présent:</span> Cocher la case</div>
            <div class="field"><span class="label">■ Absent:</span> Rayer la case ou laisser vide</div>
            <div class="field"><span class="label">R Retard:</span> Indiquer "R" dans la case</div>
            <div class="field"><span class="label">E Exclu:</span> Indiquer "E" pour exclusion temporaire</div>
        </div>
    </div>

    <!-- Signatures sur la même ligne avec float -->
    <div class="signatures-row">
        <div class="signature-box signature-left">
            Le Professeur Principal<br><br>
            _________________________<br>
            <small>Nom, Prénom et Signature</small>
        </div>
        <div class="signature-box signature-right">
            Le Directeur<br><br>
            _________________________<br>
            <small>Nom, Prénom et Signature</small>
        </div>
        <div style="clear: both;"></div>
    </div>

    <!-- Cachet et date en bas à droite -->
    <div class="cachet-section">
        <p>Cachet de l'établissement</p>
        <div style="width: 80px; height: 80px; border: 2px dashed #000; display: inline-block; margin-bottom: 5px;"></div>
        <p>Fait à ____________________, le {{ date('d/m/Y') }}</p>
    </div>
</body>
</html>