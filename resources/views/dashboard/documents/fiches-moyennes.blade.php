<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Fiche de Notes - {{ $classe->nom }}</title>
    <style>
        
        body { 
            font-family: DejaVu Sans, sans-serif; 
            font-size: 10px; 
            margin: 0;
            padding: 0;
        }
        .header { 
            text-align: center; 
            margin-bottom: 15px; 
            border-bottom: 2px solid #000; 
            padding-bottom: 10px; 
        }
        .info-ecole {
            font-size: 16px;
            font-weight: bold;
        }
        .info-classe {
            font-size: 14px;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
        }
        table, th, td { 
            border: 1px solid #000; 
        }
        th, td { 
            padding: 8px; 
            text-align: center; 
            height: 35px;
        }
        th { 
            background: #f0f0f0; 
            font-weight: bold;
        }
        .nom-eleve {
            text-align: left;
            width: 25%;
        }
        .champ-note {
            width: 60px;
            background: white;
        }
        .numero-ligne {
            width: 40px;
        }
    </style>
</head>
<body>
    <!-- En-tête -->
    <div class="header">
        <div class="info-ecole">
            {{ $ecole->nom_ecole ?? $ecole->nom ?? 'GS EXCELLE' }}
        </div>
        <div class="info-classe">
            FICHE DE NOTES - {{ $classe->nom ?? '' }} - {{ $mois->nom ?? '' }}
        </div>
    </div>

    <!-- Tableau des notes -->
    <table>
        <thead>
            <tr>
                <th class="numero-ligne">N°</th>
                <th class="nom-eleve">NOM ET PRÉNOMS</th>
                @foreach($matieres as $matiere)
                <th class="champ-note">{{ $matiere->nom }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($elevesAvecNotes as $index => $eleveData)
            @php $eleve = $eleveData['eleve']; @endphp
            <tr>
                <td class="numero-ligne">{{ $index + 1 }}</td>
                <td class="nom-eleve">
                    {{ $eleve->nom ?? '' }} {{ $eleve->prenom ?? '' }}
                </td>
                
                @foreach($matieres as $matiere)
                <td class="champ-note">
                    @php
                        // Récupérer la base de la matière
                        $base = $matiere->pivot->denominateur;
                    @endphp
                    .../{{ $base }}
                </td>
                @endforeach
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>