<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Récapitulatif des moyennes</title>
    <style>
        @page {
            margin: 90px 25px 50px 25px; /* marge pour header et footer */
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
        }

        header {
            position: fixed;
            top: -70px;
            left: 0;
            right: 0;
            height: 60px;
            text-align: center;
            border-bottom: 1px solid #000;
        }

        header img.logo {
            height: 50px;
            vertical-align: middle;
        }

        header .ecole-info {
            display: inline-block;
            vertical-align: middle;
            margin-left: 10px;
        }

        footer {
            position: fixed;
            bottom: -30px;
            left: 0;
            right: 0;
            height: 20px;
            font-size: 10px;
            text-align: center;
            border-top: 1px solid #000;
        }

        h2, h3 {
            text-align: center;
            margin: 0;
            padding: 0;
        }

        .info-classe {
            text-align: center;
            margin-bottom: 10px;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        th, td {
            border: 1px solid #000;
            padding: 4px;
            text-align: center;
        }

        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        .left {
            text-align: left;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>

<header>
    <img class="logo" src="{{ public_path('assets/img/logo_excelle.jpg') }}" alt="Logo école">
    <div class="ecole-info">
        <strong>{{ strtoupper(session('current_ecole_nom', 'ECOLE EXCELLE')) }}</strong><br>
        <small>Année scolaire : {{ session('current_annee_scolaire_nom', '') }}</small>
    </div>
</header>

<footer>
    <span>Imprimé le {{ now()->format('d/m/Y') }}</span> — <span>Page : {PAGE_NUM} / {PAGE_COUNT}</span>
</footer>

<main>
@foreach($data as $index => $classeData)
    <h2>{{ strtoupper($classeData['classe']->nom) }}</h2>
    <div class="info-classe">
        <strong>Enseignant :</strong> {{ strtoupper($classeData['enseignant']) }}
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:3%">N°</th>
                <th style="width:20%">Nom & Prénoms</th>
                @foreach($classeData['matieres'] as $matiere)
                    <th>
                        {{ strtoupper($matiere->nom) }}<br>
                        <small>(Coeff {{ $matiere->pivot->coefficient }})</small>
                    </th>
                @endforeach
                <th style="width:8%">Moyenne / {{ $classeData['classe']->moy_base }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($classeData['eleves'] as $key => $eleve)
                <tr>
                    <td>{{ $key + 1 }}</td>
                    <td class="left">{{ strtoupper($eleve['nom']) }}</td>
                    @foreach($classeData['matieres'] as $matiere)
                        <td>
                            {{ $eleve['notes'][$matiere->nom]['valeur'] ?? '' }}
                        </td>
                    @endforeach
                    <td><strong>{{ $eleve['moyenne'] }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if(!$loop->last)
        <div class="page-break"></div>
    @endif
@endforeach
</main>

</body>
</html>
