SELECT *
FROM inscriptions
WHERE eleve_id = 73
  AND annee_scolaire_id = 1;



INSERT INTO `matieres` (`id`, `ecole_id`, `niveau_id`, `nom`, `created_at`, `updated_at`) VALUES
(2, 1, 1, 'DESSIN', '2025-09-14 21:27:13', '2025-09-14 21:27:13'),
(3, 1, 1, 'ANGLAIS', '2025-10-07 08:22:14', '2025-10-07 08:22:14'),
(4, 1, 1, 'COPIE', '2025-10-07 08:22:31', '2025-10-07 08:22:31'),
(5, 1, 1, 'ECRITURE', '2025-10-07 08:23:06', '2025-10-07 08:23:06'),
(6, 1, 1, 'EDHC', '2025-10-07 08:23:27', '2025-10-07 08:23:27'),
(7, 1, 1, 'EXPRESSION ECRITE', '2025-10-07 08:23:49', '2025-10-07 08:23:49'),
(8, 1, 1, 'GRAPHISME', '2025-10-07 08:24:07', '2025-10-07 08:24:07'),
(9, 1, 1, 'LECTURE', '2025-10-07 08:24:26', '2025-10-07 08:24:26'),
(10, 1, 1, 'MATHEMATIQUES', '2025-10-07 08:24:41', '2025-10-21 13:52:44'),
(11, 1, 1, 'DICTEE', '2025-10-07 08:26:41', '2025-10-07 08:26:41'),
(12, 1, 1, 'EVEIL AU MILIEU', '2025-10-07 08:27:24', '2025-10-07 08:27:24'),
(13, 1, 1, 'INFORMATIQUE', '2025-10-07 08:27:37', '2025-10-07 08:27:37'),
(14, 1, 1, 'EXPLOITATION DE TEXTE', '2025-10-07 08:29:03', '2025-10-17 07:09:15'),
(15, 1, 1, 'POESIE', '2025-10-20 13:14:00', '2025-10-20 13:14:00'),
(16, 1, 1, 'ORTHOGRAPHE', '2025-11-26 11:28:13', '2025-11-26 11:28:13'),
(18, 1, 1, 'COMPREHENSION DE TEXTE', '2025-11-26 11:38:37', '2025-11-26 11:38:37');



INSERT INTO `niveaux` (`id`, `ecole_id`, `nom`, `ordre`, `created_at`, `updated_at`) VALUES
(1, 1, 'Petite Section', 1, '2025-09-11 23:15:27', '2026-01-27 17:24:46'),
(2, 1, 'Moyenne Section', 2, '2025-09-11 23:15:27', '2026-01-27 17:24:46'),
(3, 1, 'Grande Section', 3, '2025-09-11 23:15:28', '2026-01-27 17:24:46'),
(4, 1, 'CP1', 4, '2025-09-11 23:15:28', '2026-01-27 17:24:46'),
(5, 1, 'CP2', 5, '2025-09-11 23:15:28', '2026-01-27 17:24:46'),
(6, 1, 'CE1', 6, '2025-09-11 23:15:28', '2026-01-27 17:24:46'),
(7, 1, 'CE2', 7, '2025-09-11 23:15:28', '2026-01-27 17:24:46'),
(8, 1, 'CM1', 8, '2025-09-11 23:15:28', '2026-01-27 17:24:46'),
(9, 1, 'CM2', 9, '2025-09-11 23:15:28', '2026-01-27 17:24:46');


php artisan make:migration remove_annee_scolaire_from_notes_table

public function up(): void
{
    Schema::table('notes', function (Blueprint $table) {
        $table->dropColumn('annee_scolaire');
    });
}

public function down(): void
{
    Schema::table('notes', function (Blueprint $table) {
        $table->string('annee_scolaire')->nullable();
    });
}


php artisan make:migration migrate_type_frais_to_tarif_in_paiement_details
public function up(): void
{
    DB::statement("
        UPDATE paiement_details pd
        INNER JOIN tarifs t 
            ON t.type_frais_id = pd.type_frais_id
        SET pd.tarif_id = t.id
    ");

    Schema::table('paiement_details', function (Blueprint $table) {
        $table->dropForeign(['type_frais_id']);
        $table->dropColumn('type_frais_id');
    });
}





Schema::table('reductions', function (Blueprint $table) {
    $table->dropForeign(['type_frais_id']);
    $table->dropColumn('type_frais_id');
});



Schema::table('tarifs_mensuels', function (Blueprint $table) {
    $table->dropForeign(['type_frais_id']);
    $table->dropColumn('type_frais_id');
});


public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn('photo');
    });
}

public function down(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->string('photo')->nullable();
    });
}