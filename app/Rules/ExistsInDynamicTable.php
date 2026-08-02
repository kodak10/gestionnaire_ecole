<?php
// app/Rules/ExistsInDynamicTable.php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ExistsInDynamicTable implements Rule
{
    protected $tableName;
    protected $column;
    protected $ecoleId;
    protected $anneeScolaireId;
    protected $extraConditions;

    /**
     * Créer une nouvelle instance de la règle
     */
    public function __construct(
        string $tableName, 
        string $column = 'id', 
        ?int $ecoleId = null, 
        ?int $anneeScolaireId = null,
        array $extraConditions = []
    ) {
        $this->tableName = $tableName;
        $this->column = $column;
        $this->ecoleId = $ecoleId;
        $this->anneeScolaireId = $anneeScolaireId;
        $this->extraConditions = $extraConditions;
    }

    /**
     * Vérifier si la valeur est valide
     */
    public function passes($attribute, $value)
    {
        // Vérifier si la table existe
        if (!Schema::hasTable($this->tableName)) {
            return false;
        }

        // Construire la requête
        $query = DB::table($this->tableName)
            ->where($this->column, $value);

        // Ajouter les conditions
        if ($this->ecoleId) {
            $query->where('ecole_id', $this->ecoleId);
        }

        if ($this->anneeScolaireId) {
            $query->where('annee_scolaire_id', $this->anneeScolaireId);
        }

        // Ajouter les conditions supplémentaires
        foreach ($this->extraConditions as $key => $val) {
            $query->where($key, $val);
        }

        return $query->exists();
    }

    /**
     * Message d'erreur
     */
    public function message()
    {
        return 'La valeur sélectionnée est invalide ou n\'existe pas dans la base de données.';
    }
}