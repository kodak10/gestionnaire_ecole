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
            background-image: url('{{ public_path('storage/documents/certificat-scolarite.png') }}');
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

        .tab{
            position: absolute;
            top: 170mm;
            left: 20mm;
            width: 170mm;
        }

        .table-scolarite {
            
            
            border-collapse: collapse;
            font-size: 3.5mm;
        }

        .table-scolarite th,
        .table-scolarite td {
            border: 1px solid #000;
            padding: 2mm;
            text-align: center;
        }

        .table-scolarite th {
            font-weight: bold;
        }


        
    </style>
</head>
<body>

<div class="diplome">

    <div class="annee">
        {{ $anneeScolaire->annee }}
    </div>

    {{-- 

    
 

    <div class="aCeJour">
        {{ now()->format('d/m/Y') }}
    </div>

    <div class="faitLe">
        {{ now()->format('d/m/Y') }}
    </div>

    --}}


    <div style="position:absolute; top:115mm; left:20mm; right:20mm; font-size:4mm; text-align:justify;">
        Le Directeur de L’E.P.V <strong>EXCELLE</strong> <br>
        Sous-préfecture de <strong> {{ $ecole->sous_prefecture ?? 'KORHOGO' }} </strong><br>
        Circonscription primaire de <strong> {{ $ecole->circonscription ?? 'KORHOGO-SUD' }} </strong><br>
        Soussigné, certifie que l’élève :
        <strong>{{ $inscription->eleve->nom }} {{ $inscription->eleve->prenom }}</strong>,
        né(e) le <strong>{{ $inscription->eleve->naissance->format('d-m-Y') }}</strong>
        à <strong>{{ $inscription->eleve->lieu_naissance }}</strong>,
        selon
        @if($inscription->eleve->jugement)
            le jugement supplétif du {{ $inscription->eleve->jugement_date }} N° {{ $inscription->eleve->jugement_numero }}
        @else
            l’acte de naissance N° {{ !empty($inscription->eleve->num_extrait) ? $inscription->eleve->num_extrait : '.................' }}
        @endif,
        inscrit(e) sous le N° <strong>{{ $inscription->eleve->code_national ?? $inscription->eleve->matricule }}</strong>,
        a fréquenté son école du
        <strong>{{ $inscription->eleve->created_at->format('d/m/Y') }}</strong> au
        <strong>{{ now()->format('d/m/Y') }}</strong>.
    </div>

    <div style="position:absolute; top:155mm; left:20mm; right:20mm; font-size:4mm; text-align:justify; text-decoration: underline; text-weight: bold !important;">
        <p> <strong>SA SCOLARITE TOTALE S’ETABLIE COMME SUIT: </strong></p>
    </div>

    <div class="tab">
        <table class="table-scolarite">
            <thead>
                <tr>
                    <th>Année scolaire</th>
                    <th>Classe</th>
                    <th>Moyenne annuelle</th>
                    <th>Classement</th>
                    <th>Observation</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tableauScolarite as $ligne)
                    <tr>
                        <td>{{ $ligne['annee_scolaire'] }}</td>
                        <td>{{ $ligne['classe'] }}</td>
                        <td>{{ $ligne['moyenne'] ?? 'N/A' }} / 20</td>
                        <td>{{ $ligne['rang'] }}</td>
                        <td>{{ $ligne['observation'] }}</td>
                    </tr>
                @endforeach
            </tbody>

            
        </table>

        

        <div style="">
            <strong>APPRÉCIATIONS GÉNÉRALES</strong><br>
            Travail : <br>
            Conduite : <br>
            Motif de départ : 
        </div>

        <!-- Conteneur pour les signatures -->
        <div style="bottom: 14mm; width: 100%; display: flex; font-size: 4mm; color: #333; font-weight: bold;">

            <!-- Bloc 1 : Nom et Prénom du Directeur -->
            <div style="flex: 1; text-align: center;">
                NOM ET PRENOMS DU DIRECTEUR<br>
                Dr. Amed DOUMOUYA
            </div>

            <!-- Bloc 2 : Cachet de l’Etablissement -->
            <div style="flex: 1; text-align: center;">
                Cachet de l’Etablissement<br>
                Civilisation Germanique
            </div>

            <!-- Bloc 3 : Signature du Directeur -->
            <div style="flex: 1; text-align: center;">
                Signature du Directeur<br>
                __________________
            </div>

        </div>

    </div>

    
    

    <div>

    </div>


    {{--  --}}

    




</div>

</body>

</html>
