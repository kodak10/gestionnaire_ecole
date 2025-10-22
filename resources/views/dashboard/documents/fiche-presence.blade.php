<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Fiche de Présence - {{ $classe->nom }}</title>
    <style>
        @page { 
            size: landscape;
            margin: 1cm; 
        }
        body { 
            font-family: DejaVu Sans, sans-serif; 
            font-size: 9px; 
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
            font-size: 14px;
            font-weight: bold;
        }
        .info-classe {
            font-size: 12px;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
        }
        table, th, td { 
            border: 1px solid #000; 
        }
        th, td { 
            padding: 4px; 
            text-align: center; 
            height: 25px;
        }
        th { 
            background: #f0f0f0; 
            font-weight: bold;
        }
        .nom-eleve {
            text-align: left;
            width: 15%;
        }
        .numero-ligne {
            width: 30px;
        }
        .jour-cell {
            width: 20px;
        }
        .signature {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
        }
        .signature-box {
            width: 45%;
            text-align: center;
            border-top: 1px solid #000;
            padding-top: 5px;
        }
        .checkbox {
            width: 12px;
            height: 12px;
            border: 1px solid #000;
            display: inline-block;
        }
        .month-header {
            background: #e0e0e0 !important;
        }
    </style>
</head>
<body>
    <!-- En-tête -->
    <div class="header">
        <div class="info-ecole">
            {{ auth()->user()->ecole->nom ?? 'GS EXCELLE' }}
        </div>
        <div class="info-classe">
            FICHE DE PRESENCE - {{ $classe->nom }} - Année Scolaire {{ $anneeScolaire->annee }}
        </div>
        <div style="font-size: 10px; margin-top: 5px;">
            Mois: ____________________
        </div>
    </div>

    <!-- Tableau de fréquentation -->
    <table>
        <thead>
            <tr>
                <th class="numero-ligne">N°</th>
                <th class="nom-eleve">NOM ET PRÉNOM DE L'ÉLÈVE</th>
                <!-- Jours du mois -->
                @for($i = 1; $i <= 31; $i++)
                <th class="jour-cell">{{ $i }}</th>
                @endfor
                <th>Total<br>Absences</th>
            </tr>
        </thead>
        <tbody>
            @foreach($eleves as $index => $eleve)
            <tr>
                <td class="numero-ligne">{{ $index + 1 }}</td>
                <td class="nom-eleve">
                    {{ $eleve->eleve->nom }} {{ $eleve->eleve->prenom }}
                </td>
                
                <!-- Cases pour chaque jour du mois -->
                @for($i = 1; $i <= 31; $i++)
                <td class="jour-cell">
                    <div class="checkbox"></div>
                </td>
                @endfor
                
                <!-- Total absences -->
                <td></td>
                
            </tr>
            @endforeach
            
            <!-- Ligne pour les totaux -->
            <tr>
                <td colspan="2" style="text-align: center; font-weight: bold;">TOTAL ABSENCES PAR JOUR</td>
                @for($i = 1; $i <= 31; $i++)
                <td></td>
                @endfor
                <td style="font-weight: bold;"></td>
                <td></td>
            </tr>
        </tbody>
    </table>


    <!-- Signatures -->
    <div class="signature">
        <div class="signature-box">
            Le Professeur<br><br>
            _________________________
        </div>
    </div>
</body>
</html>