<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reçu de Relance - {{ $mois ?? '' }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        h2 { text-align: center; }
        .eleve { margin-bottom: 20px; padding: 10px; border-bottom: 1px solid #ccc; }
        .details { margin-top: 5px; }
        .bold { font-weight: bold; }
    </style>
</head>
<body>

<h2>Reçu de Relance - {{ $type_frais }} - {{ $mois }}</h2>

@foreach($recus as $recusEleve)
    <div class="eleve">
        <p style="text-align: center;"><strong>Relance de {{ $type_frais }}</strong></p>
        <p>Bonjour cher parent <span class="bold">{{ $recusEleve['parent'] }}</span>,</p>

        <p>
            Votre enfant <span class="bold">{{ $recusEleve['eleve'] }}</span>, inscrit en classe de 
            <span class="bold">{{ $recusEleve['classe'] }}</span>, 
            pour le mois de <span class="bold">{{ $recusEleve['mois'] }}</span> 
            présente le détail suivant :
        </p>

        <div class="details">
            <p>Montant attendu pour le mois : <span class="bold">{{ number_format($recusEleve['montant_attendu'], 0, ',', ' ') }} FCFA</span></p>
            <p>Montant déjà payé pour le mois : <span class="bold">{{ number_format($recusEleve['montant_paye'], 0, ',', ' ') }} FCFA</span></p>
            <p>Reste à payer sur le mois : <span class="bold">{{ number_format($recusEleve['reste_mois'], 0, ',', ' ') }} FCFA</span></p>
            <p>Reste total de la scolarité : <span class="bold">{{ number_format($recusEleve['reste_total'], 0, ',', ' ') }} FCFA</span></p>
        </div>

        <p>Nous vous prions de bien vouloir régulariser le paiement dans les meilleurs délais.</p>
        <p>La Direction</p>
    </div>


@endforeach

@if(count($recus) == 0)
    <p>Aucun élève n'est en retard pour ce mois.</p>
@endif

</body>
</html>
