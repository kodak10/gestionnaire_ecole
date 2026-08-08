<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 25px 30px; }
        body {
            font-family: Arial, sans-serif;
            font-size: 13px;
            color: #1a1a1a;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .header-left {
            width: 58%;
            vertical-align: top;
            text-align: left;
            font-weight: bold;
            font-size: 12px;
            line-height: 1.5;
            text-transform: uppercase;
        }
        .header-right {
            width: 42%;
            vertical-align: top;
            text-align: center;
            font-size: 12px;
            font-weight: bold;
        }
        .header-right .subtitle {
            font-weight: normal;
            font-style: italic;
        }
        .header-right img {
            height: 60px;
            margin: 6px 0;
        }
        .annee-scolaire {
            margin-top: 8px;
            font-weight: bold;
        }
        .dotted-line {
            letter-spacing: 1px;
        }
        .top-block {
            width: 100%;
            margin: 15px 0 25px 0;
        }
        .top-block td {
            vertical-align: middle;
        }
        .logo-ecole-cell {
            width: 150px;
            text-align: center;
        }
        .logo-ecole-cell img {
            max-width: 130px;
            max-height: 100px;
        }
        .title-cell {
            text-align: center;
        }
        .title-box {
            display: inline-block;
            border: 2px solid #444;
            background: linear-gradient(180deg, #e8e8e8 0%, #cfcfcf 100%);
            padding: 10px 30px;
            font-size: 19px;
            font-weight: bold;
            letter-spacing: 1px;
            border-radius: 4px;
        }
        .content {
            line-height: 1.9;
            font-size: 13.5px;
            margin-bottom: 15px;
        }
        .content strong {
            font-weight: bold;
        }
        .underline-word {
            text-decoration: underline;
            text-decoration-style: wavy;
        }
        .scolarite-title {
            font-weight: bold;
            font-size: 13.5px;
            margin: 20px 0 8px 0;
        }
        table.scolarite {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        table.scolarite th {
            border: 1px solid #999;
            background: #f0f0f0;
            padding: 8px 6px;
            text-align: center;
        }
        table.scolarite td {
            border: 1px solid #999;
            padding: 10px 6px;
            text-align: center;
        }
        .signature-block {
            margin-top: 30px;
            text-align: right;
            font-size: 13px;
        }
    </style>
</head>
<body>

    {{-- ============ EN-TÊTE OFFICIEL ============ --}}
    <table class="header-table">
        <tr>
            <td class="header-left">
                Ministère de l'Education Nationale<br>
                et de l'Alphabétisation<br>
                <span class="dotted-line">------------------</span><br>
                Direction Régionale de {{ strtoupper($ecole->ville ?? '') }}<br>
                <span class="dotted-line">..........................</span><br>
                I.E.P.P : {{ strtoupper($ecole->iepp ?? '') }}<br>
                <span class="dotted-line">.......................</span><br>
                Secteur Pédagogique: {{ strtoupper($ecole->secteur_pedagogique ?? '') }}<br>
                E.PV : {{ strtoupper($ecole->sigle_ecole ?? '') }}
            </td>
            <td class="header-right">
                République de Côte d'Ivoire<br>
                <span class="subtitle">Union-Discipline-Travail</span><br>
                @if(!empty($ecole->logo_republique))
                    @php
                        $logoRepPath = public_path($ecole->logo_republique);
                        $logoRepUrl = file_exists($logoRepPath) ? $logoRepPath : asset($ecole->logo_republique);
                    @endphp
                    <img src="{{ $logoRepUrl }}" alt="Armoiries">
                @endif
                <div class="annee-scolaire">Année scolaire : {{ $annee }}</div>
            </td>
        </tr>
    </table>

    {{-- ============ LOGO ÉCOLE + TITRE ============ --}}
    <table class="top-block">
        <tr>
            <td class="logo-ecole-cell">
                @if(!empty($ecole->logo_ecole))
                    @php
                        $logoEcolePath = public_path($ecole->logo_ecole);
                        $logoEcoleUrl = file_exists($logoEcolePath) ? $logoEcolePath : asset($ecole->logo_ecole);
                    @endphp
                    <img src="{{ $logoEcoleUrl }}" alt="Logo école">
                @endif
            </td>
            <td class="title-cell">
                <span class="title-box">CERTIFICAT DE SCOLARITE</span>
            </td>
            <td style="width:150px;"></td>
        </tr>
    </table>

    {{-- ============ CORPS DU DOCUMENT ============ --}}
    <div class="content">
        <p>Le Directeur de L'E.PV « <strong>{{ $ecole->sigle_ecole ?? $ecole->nom_ecole ?? '' }}</strong> »</p>

        <p>
            Sous préfecture de
            <span class="underline-word">{{ $ecole->sous_prefecture ?? $ecole->ville ?? '' }}</span>
        </p>

        <p>
            Circonscription primaire de
            <span class="underline-word">{{ $ecole->circonscription_primaire ?? '' }}</span>
        </p>

        <p>
            Soussigné, certifie que l'élève :
            <strong>{{ strtoupper($eleve->nom ?? '') }} {{ $eleve->prenom ?? '' }}</strong>
        </p>

        <p>
            Né(e) le :
            <strong>{{ isset($eleve->naissance) ? \Carbon\Carbon::parse($eleve->naissance)->format('d-m-Y') : '' }}</strong>
            à <strong>{{ $eleve->lieu_naissance ?? '' }}</strong>
        </p>

        <p>Selon le jugement supplétif du : ……//….. N°…..//…..</p>

        <p>
            Ou Acte de naissance du :
            <strong>{{ isset($eleve->naissance) ? \Carbon\Carbon::parse($eleve->naissance)->format('d-m-Y') : '' }}</strong>
            N°<strong>{{ $eleve->num_extrait ?? '' }}</strong>
        </p>

        <p>
            Inscrit sur le registre de l'établissement sous le
            N°<strong>{{ $ecole->num_registre ?? '' }}</strong>
        </p>

        <p>
            A fréquenté son école du <strong>{{ $dateDebut ?? '____________' }}</strong>
            à ce jour <strong>{{ now()->format('d-m-Y') }}</strong>
        </p>
    </div>

    <p class="scolarite-title">SA SCOLARITE TOTALE S'ETABLIE COMME SUIT :</p>

    <table class="scolarite">
        <thead>
            <tr>
                <th>ANNEE<br>SCOLAIRE</th>
                <th>COURS<br>FREQUENTE</th>
                <th>MOYENNE<br>ANNUELLE</th>
                <th>CLASSEMENT</th>
                <th>OBSERVATION</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $annee }}</td>
                <td>{{ $classe->libelle ?? $classe->nom ?? '' }}</td>
                <td>{{ $moyenneAnnuelle ?? '____' }} / 20</td>
                <td>{{ $rang ?? '___' }} / {{ $effectif ?? '___' }}</td>
                <td>{{ $observation ?? '' }}</td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
            </tr>
        </tbody>
    </table>

    <div class="signature-block">
        <p>Fait à {{ $ecole->ville ?? 'Korhogo' }}, le {{ now()->format('d/m/Y') }}</p>
    </div>

</body>
</html>