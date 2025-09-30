<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Relance - {{ $mois ?? '' }}</title>
    <style>
    body { font-family: Arial, sans-serif; font-size: 12px; margin:0; padding:0; }
    h2 { text-align: center; margin:0; }

    .eleve { 
        margin: 0 0 20px 0; /* supprime le margin-top */
        padding: 10px; 
        border-bottom: 1px solid #ccc; 
        page-break-inside: avoid; 
    }

    .eleve p { 
        margin: 0; /* supprime les marges par défaut des p */
        padding: 0; 
    }

    .details { 
        margin: 0; /* supprime le margin-top */
    }

    .bold { font-weight: bold; }
</style>

</head>
<body>


@foreach($recus as $recusEleve)
    <div class="eleve">
        <p style="text-align: center;"><strong>Relance de {{ $type_frais }} - {{ $mois }}</strong></p>
        <p>Bonjour cher parent <span class="bold">{{ $recusEleve['parent'] }}</span>,</p>

        <p>
            Votre enfant <span class="bold">{{ $recusEleve['eleve'] }}</span>, inscrit en classe de 
            <span class="bold">{{ $recusEleve['classe'] }}</span>, 
            pour le mois de <span class="bold">{{ $recusEleve['mois'] }}</span> 
            présente le détail suivant :
        </p>

       <div class="details">
            <p>
                Montant attendu pour le mois : <span class="bold">{{ number_format($recusEleve['montant_attendu'], 0, ',', ' ') }} FCFA</span> |
                Montant déjà payé pour le mois : <span class="bold">{{ number_format($recusEleve['montant_paye'], 0, ',', ' ') }} FCFA</span> |
                Reste à payer sur le mois : <span class="bold">{{ number_format($recusEleve['reste_mois'], 0, ',', ' ') }} FCFA</span> |
                Reste total de {{ $type_frais }} : <span class="bold">{{ number_format($recusEleve['reste_total'], 0, ',', ' ') }} FCFA</span>
            </p>
        </div>

        <p>Nous vous prions de bien vouloir régulariser le paiement avant le 05 du mois.</p>
        <p>La Direction</p>
    </div>


@endforeach

@if(count($recus) == 0)
    <p>Aucun élève n'est en retard pour ce mois.</p>
@endif

</body>
</html>
