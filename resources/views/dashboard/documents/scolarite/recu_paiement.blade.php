<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Reçu de Paiement</title>
<style>
  @page { size: A4 portrait; margin: 0; } /* plus de marges */
  body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    -webkit-print-color-adjust: exact;
  }

  .receipt {
    width: 100%;
    border: 1px solid #000;
    box-sizing: border-box;
  }

  /* Bandeau d'en-tête */
  .header {
    background: #f6b60a;
    padding: 8px 12px;
    position: relative;
    color: #0f2740;
  }
  .header h1 {
    margin: 0;
    font-size: 18px;
    font-weight: bold;
  }
  .header span {
    font-size: 11px;
  }
  .header .meta {
    position: absolute;
    top: 8px;
    right: 12px;
    text-align: right;
    font-size: 11px;
  }

  /* Contenu */
  .content {
    padding: 10px 12px;
    background: #fff;
  }

  .field {
    margin: 6px 0;
    font-size: 12px;
  }
  .label {
    display: inline-block;
    min-width: 120px;
    font-weight: bold;
    font-size: 12px;
  }
  .value {
    display: inline-block;
    width: calc(100% - 130px);
    border-bottom: 1px dashed #555;
    min-height: 16px;
    padding-bottom: 2px;
    font-size: 12px;
    text-align: left;
  }

  /* Table pour alignement 2 colonnes */
  .two-cols {
    width: 100%;
    border-collapse: collapse;
    margin: 6px 0;
    font-size: 12px;
  }
  .two-cols td {
    vertical-align: top;
    padding: 3px 6px 3px 0;
    width: 50%;
  }
  .two-cols .label {
    min-width: auto;
    width: auto;
  }
  .two-cols .value {
    width: 100%;
  }

  /* Signature */
  .signature {
    text-align: right;
    margin-top: 20px;
  }
  .signature .line {
    display: inline-block;
    width: 180px;
    border-top: 1px solid #000;
    margin-bottom: 4px;
  }
  .signature div {
    font-size: 10px;
  }
</style>
</head>
<body>
  <div class="receipt">
    <div class="header">
      <!-- Logo à gauche -->
      <img src="{{ public_path('assets/img/logo_ecxelle.png') }}" alt="Logo école" style="height:40px; vertical-align:middle; margin-right:10px;">

      <!-- Nom et téléphone -->
      <h1 style="display:inline-block; margin:0; font-size:18px; vertical-align:middle;">
          {{ $ecole->nom ?? 'GS EXCELLE' }}
      </h1>
      <span style="display:block; font-size:11px; margin-top:2px;">
          {{ $ecole->telephone ?? '0708395524 / 0708395524' }}
      </span>

      <!-- Infos du reçu (numéro + date) -->
      <div class="meta">
          N°: {{ str_pad($paiement->id, 6, '0', STR_PAD_LEFT) }}<br>
          Date: {{ date('d/m/Y H:i', strtotime($paiement->created_at)) }}
      </div>
    </div>

    <div class="content">
      <div class="field">
        <span class="label">Type de Reçu :</span>
        <span class="value">
          <strong>
          @php
            $typesFrais = [];
            foreach($paiement->details as $detail) {
              if($detail->typeFrais) {
                $typesFrais[] = $detail->typeFrais->nom;
              }
            }
            echo implode(', ', array_unique($typesFrais));
          @endphp
          </strong>
        </span>
      </div>

      <div class="field">
        <span class="label">Classe :</span>
        <span class="value">{{ $classe->nom ?? '' }}</span>
      </div>

      <!-- Matricule + Nom et prénoms -->
      <table class="two-cols">
        <tr>
          <td>
            <span class="label">Matricule :</span>
            <span class="value">{{ $eleve->matricule ?? '' }}</span>
          </td>
          <td>
            <span class="label">Nom & Prénoms :</span>
            <span class="value">{{ $eleve->nom ?? '' }} {{ $eleve->prenom ?? '' }}</span>
          </td>
        </tr>
      </table>

      <div class="field">
        <span class="label">Libellé :</span>
        <span class="value">
          <strong>
          @php
            $typesFrais = [];
            foreach($paiement->details as $detail) {
              if($detail->typeFrais) {
                $typesFrais[] = $detail->typeFrais->nom;
              }
            }
            echo implode(', ', array_unique($typesFrais));
          @endphp
          </strong>
        </span>
      </div>

      <!-- Montant versé + Reste à payer -->
      <table class="two-cols">
        <tr>
          <td>
            <span class="label">Montant versé :</span>
            <span class="value">{{ number_format($montant_total, 0, ',', ' ') }} FCFA</span>
          </td>
          <td>
            <span class="label">Reste à payer :</span>
            <span class="value">{{ number_format($reste_total, 0, ',', ' ') }} FCFA</span>
          </td>
        </tr>
      </table>

      <!-- Mode paiement + Encaissé par -->
      <table class="two-cols">
        <tr>
          <td>
            <span class="label">Mode paiement :</span>
            <span class="value">{{ $paiement->mode_paiement ?? 'Non spécifié' }}</span>
          </td>
          <td>
            <span class="label">Encaissé par :</span>
            <span class="value">{{ $paiement->user->name ?? '' }}</span>
          </td>
        </tr>
      </table>

      
    </div>
  </div>
</body>
</html>