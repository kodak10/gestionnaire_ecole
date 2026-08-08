@php
    $classeLabel = \App\Models\Classe::find($eleve->classe_id)->libelle
        ?? \App\Models\Classe::find($eleve->classe_id)->nom
        ?? '';

    $naissance = isset($eleve->naissance)
        ? \Carbon\Carbon::parse($eleve->naissance)->format('d/m/Y')
        : '';

    $photoPath = $eleve->photo_path ?? null;
    $photoAbsolute = $photoPath ? storage_path('app/public/' . $photoPath) : null;
    $photoExists = $photoAbsolute && file_exists($photoAbsolute);

    $contactUrgence = $eleve->contact_urgence ?? $eleve->parent_telephone ?? '';
    $groupeSanguin = $eleve->groupe_sanguin ?? '';

    $initiales = strtoupper(substr($eleve->nom ?? '', 0, 1) . substr($eleve->prenom ?? '', 0, 1));

    // Génère un motif de triangles en zigzag (2 colonnes croisées façon "X")
    $nbTriangles = 7;
    $colors = ['orange', 'cyan'];
@endphp

<div class="carte">

    {{-- ============ MOTIF DE TRIANGLES (zigzag "X") ============ --}}
    @for ($i = 0; $i < $nbTriangles; $i++)
        @php
            $couleurGauche = $colors[$i % 2];
            $couleurDroite = $colors[($i + 1) % 2];
            $topPos = $i * 6.8; // en mm, espacement vertical
            $decalageGauche = ($i % 2 == 0) ? 12 : 20; // en mm, zigzag horizontal
            $decalageDroite = ($i % 2 == 0) ? 24 : 16;
            $pointe = ($i % 2 == 0) ? 'down' : 'up';
        @endphp
        <div class="triangle triangle-{{ $pointe }}-{{ $couleurGauche }}"
             style="top: {{ $topPos }}mm; left: {{ $decalageGauche }}mm;"></div>
        <div class="triangle triangle-{{ $pointe == 'down' ? 'up' : 'down' }}-{{ $couleurDroite }}"
             style="top: {{ $topPos + 3.4 }}mm; left: {{ $decalageDroite }}mm;"></div>
    @endfor

    {{-- ============ LOGO ÉCOLE ============ --}}
    <div class="logo-bloc">
        <div class="logo-badge">★</div>
        <div class="logo-texte-1">GROUPE SCOLAIRE</div>
        <div class="logo-texte-2">{{ strtoupper($ecole->sigle_ecole ?? 'EXCELLE') }}</div>
        <div class="logo-slogan">L'Excellence au cœur<br>de l'éducation</div>
    </div>

    {{-- ============ CONTACT (bas gauche) ============ --}}
    <div class="contact-bloc">
        @if(!empty($ecole->telephone))
            <div class="contact-ligne">
                <span class="contact-puce">T</span>{{ $ecole->telephone }}
            </div>
        @endif
        <div class="contact-adresse">
            <span class="contact-puce">@</span>{{ strtoupper($ecole->ville ?? '') }} ZONE
        </div>
    </div>

    {{-- ============ TITRE ============ --}}
    <div class="titre-carte">CARTE ÉLÈVE</div>

    {{-- ============ PHOTO / CERCLE ============ --}}
    <div class="photo-cercle">
        @if($photoExists)
            <img src="{{ $photoAbsolute }}" alt="Photo">
        @else
            <div class="initiales">{{ $initiales }}</div>
        @endif
    </div>

    {{-- ============ INFOS ============ --}}
    <div class="infos-bloc">
        <div class="info-ligne">NOM : <span class="valeur">{{ strtoupper($eleve->nom ?? '') }}</span></div>
        <div class="info-ligne">PRENOMS : <span class="valeur">{{ strtoupper($eleve->prenom ?? '') }}</span></div>
        <div class="info-ligne">CLASSE : <span class="valeur">{{ $classeLabel }}</span></div>
        <div class="info-ligne">DATE DE NAISSANCE : <span class="valeur">{{ $naissance }}</span></div>
        <div class="info-ligne">GROUPE SANGUIN : <span class="valeur">{{ $groupeSanguin }}</span></div>
        <div class="info-ligne">CONTACT D'URGENCE : <span class="valeur">{{ $contactUrgence }}</span></div>
    </div>

</div>