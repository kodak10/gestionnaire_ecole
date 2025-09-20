<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Bulletin - {{ $classe->nom }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; margin: 0; padding: 0; }
        .header { background: #0d274d; color: #fff; text-align: center; padding: 15px; }
        .header h1 { margin: 0; font-size: 20px; }
        .header h3 { margin: 3px 0 0 0; font-size: 14px; font-weight: normal; }
        .info-section { width: 100%; margin: 15px 0; border-collapse: collapse; }
        .info-section td { vertical-align: top; padding: 10px; }
        .eleve-box { border: 1px solid #ccc; padding: 10px; }
        .photo { width: 100px; height: 120px; border: 1px solid #ccc; margin-bottom: 5px; }
        .title { font-weight: bold; margin-top: 10px; background: #eee; padding: 5px; }
        table.grades { width: 100%; border-collapse: collapse; margin-top: 5px; }
        table.grades th, table.grades td { border: 1px solid #000; padding: 6px; text-align: center; }
        table.grades th { background: #e8eef7; }
        .footer { margin-top: 20px; padding: 10px; font-size: 11px; }
        .footer td { padding-top: 30px; text-align: center; }
        .matric-scale { font-size: 11px; margin-top: 10px; }
    </style>
</head>
<body>
@foreach($elevesAvecMoyennes as $eleveData)
    <div class="bulletin" style="page-break-after: always;">
        <!-- HEADER -->
        <div class="header">
            <h1>BULLETIN SCOLAIRE</h1>
            <h3>{{ auth()->user()->ecole->nom_ecole }}</h3>
            <p>PÉRIODE: {{ $mois->nom }} | ANNÉE SCOLAIRE: {{ auth()->user()->current_annee_scolaire }}</p>
        </div>

        <!-- INFO + PHOTO -->
        <table class="info-section" width="100%">
            <tr>
                <!-- Infos élève -->
                <td width="70%">
                    <div class="eleve-box">
                        <strong>Classe :</strong> {{ $classe->nom }} <br>
                        <strong>Matricule :</strong> {{ $eleveData['inscription']->eleve->matricule }} <br>
                        <strong>Élève :</strong> {{ $eleveData['inscription']->eleve->nom }} {{ $eleveData['inscription']->eleve->prenom }} <br>
                        <strong>Moyenne générale :</strong> {{ number_format($eleveData['moyenne'], 2) }}/20 <br>
                        <strong>Classement général :</strong> {{ $eleveData['rang_general'] }}<sup>e</sup>
                        @if(!empty($eleveData['exaequo']) && $eleveData['exaequo'])
                            (Ex-aequo)
                        @endif <br>
                        <strong>Mention :</strong> {{ $eleveData['mention'] }}
                    </div>
                </td>
                <!-- Zone photo et infos -->
                <td width="30%" align="center">
                    <div class="photo">
                        {{-- Si tu veux afficher la photo de l'élève --}}
                        @php
                            $photo = $eleveData['inscription']->eleve->photo_path;
                            $sexe = $eleveData['inscription']->eleve->sexe;

                            if ($photo && file_exists(public_path('photos/' . $photo))) {
                                $photoPath = public_path('photos/' . $photo);
                            } else {
                                if ($sexe === 'Masculin') {
                                    $photoPath = public_path('images/default_masculin.png');
                                } else {
                                    $photoPath = public_path('images/default_feminin.png');
                                }
                            }
                        @endphp

                        <img src="{{ $photoPath }}" width="100" height="120">
                    </div>
                    <strong>{{ $eleveData['inscription']->eleve->nom }} {{ $eleveData['inscription']->eleve->prenom }}</strong><br>
                    <small>ID: {{ $eleveData['inscription']->eleve->id }}</small>
                    
                </td>
            </tr>
        </table>

        <!-- NOTES -->
        <div class="title">DÉTAIL DES NOTES</div>
        <table class="grades">
            <thead>
                <tr>
                    <th>Matière</th>
                    <th>Note</th>
                    <th>Rang</th>
                    <th>Appréciation</th>
                </tr>
            </thead>
            <tbody>
                @foreach($eleveData['notes'] as $note)
                    <tr>
                        <td>{{ $note->matiere->nom }}</td>
                        <td>{{ $note->valeur }}</td>
                        <td>{{ $note->rang_matiere }}</td>
                        <td></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- FOOTER -->
        <table class="footer" width="100%">
            <tr>
                <td width="50%">Le Directeur<br><br>___________________<br>{{ auth()->user()->ecole->directeur ?? 'Directeur' }}</td>
                <td width="50%">Fait le {{ now()->format('d/m/Y') }}<br><br>___________________<br>Professeur principal</td>
            </tr>
        </table>
    </div>
@endforeach
</body>
</html>
