<?php
// app/Rules/UniqueInDynamicTable.php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UniqueInDynamicTable implements Rule
{
    protected $tableName;
    protected $column;
    protected $ignoreId;
    protected $ignoreColumn;
    protected $ecoleId;
    protected $anneeScolaireId;
    protected $extraConditions;

    /**
     * Créer une nouvelle instance de la règle
     */
    public function __construct(
        string $tableName, 
        string $column, 
        ?int $ignoreId = null, 
        string $ignoreColumn = 'id',
        ?int $ecoleId = null, 
        ?int $anneeScolaireId = null,
        array $extraConditions = []
    ) {
        $this->tableName = $tableName;
        $this->column = $column;
        $this->ignoreId = $ignoreId;
        $this->ignoreColumn = $ignoreColumn;
        $this->ecoleId = $ecoleId;
        $this->anneeScolaireId = $anneeScolaireId;
        $this->extraConditions = $extraConditions;
    }

    /**
     * Vérifier si la valeur est unique
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

        // Ignorer un enregistrement spécifique (pour les mises à jour)
        if ($this->ignoreId !== null) {
            $query->where($this->ignoreColumn, '!=', $this->ignoreId);
        }

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

        return !$query->exists();
    }

    /**
     * Message d'erreur
     */
    public function message()
    {
        return 'La valeur :attribute est déjà utilisée.';
    }
}