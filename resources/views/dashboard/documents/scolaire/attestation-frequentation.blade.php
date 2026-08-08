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
            background: #d9d9d9;
            background: linear-gradient(180deg, #e8e8e8 0%, #cfcfcf 100%);
            padding: 10px 30px;
            font-size: 19px;
            font-weight: bold;
            letter-spacing: 1px;
            border-radius: 4px;
        }
        .content {
            line-height: 2.1;
            font-size: 13.5px;
        }
        .content strong {
            font-weight: bold;
        }
        .content u {
            text-decoration: underline;
        }
        .signature-block {
            margin-top: 45px;
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
                <span class="title-box">CERTIFICAT DE FREQUENTATION</span>
            </td>
            <td style="width:150px;"></td>
        </tr>
    </table>

    {{-- ============ CORPS DU DOCUMENT ============ --}}
    <div class="content">
        <p>Je soussigné,</p>

        <p>
            <strong>M. {{ $ecole->directeur_etudes ?? $ecole->directeur ?? '' }}</strong>,
            Directeur des Etudes de l'E.PV <strong>{{ $ecole->sigle_ecole ?? $ecole->nom_ecole ?? '' }}</strong>.
        </p>

        <p>
            Atteste que l'élève
            <strong>{{ strtoupper($eleve->nom ?? '') }} {{ $eleve->prenom ?? '' }}</strong>
        </p>

        <p>
            Matricule :
            <strong>{{ $eleve->code_national ?? $eleve->matricule ?? '' }}</strong>
            @if(!empty($eleve->num_extrait))
                / Acte de naissance N° <strong>{{ $eleve->num_extrait }}</strong>
            @endif
        </p>

        <p>
            Né(e) le
            <strong>{{ isset($eleve->naissance) ? \Carbon\Carbon::parse($eleve->naissance)->format('d/m/Y') : '' }}</strong>
            à la <strong>{{ $eleve->lieu_naissance ?? '' }}</strong>
        </p>

        <p>Cours suivi: <strong>{{ $classe->libelle ?? $classe->nom ?? '' }}</strong></p>

        <p>Fils/Fille de : <strong>{{ $eleve->pere_nom ?? '' }}</strong></p>

        <p>Et de: <strong>{{ $eleve->mere_nom ?? '' }}</strong></p>

        <p>
            Est effectivement inscrit à l'<strong>E.PV {{ $ecole->sigle_ecole ?? $ecole->nom_ecole ?? '' }}</strong>.
        </p>

        <p>
            Depuis le <strong>{{ $dateDebut ?? '____________' }}</strong>
            à ce jour <strong>{{ now()->format('d F Y') }}</strong>
        </p>

        <br>

        <p>En foi de quoi, cette attestation lui est délivrée pour servir et valoir ce que de droit.</p>
    </div>

    <div class="signature-block">
        <p>Fait à {{ $ecole->ville ?? 'Korhogo' }}, le {{ now()->format('d F Y') }}</p>
    </div>

</body>
</html>