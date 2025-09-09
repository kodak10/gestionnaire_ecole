<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        

        $this->call([
            EcolesSeeder::class,
            AnneeScolaireSeeder::class,
            NiveauxSeeder::class,
            TypeFraisSeeder::class,
            DepenseCategorySeeder::class,
            MoisScolaireSeeder::class,

            UserSeeder::class,
            

            // Pas besoin de re-seeder ces tables si elles sont déjà peuplées
            // TarifSeeder::class,
            // TarifMensuelSeeder::class,
            // ClasseSeeder::class,
            // MatiereSeeder::class,
            // MentionSeeder::class,
            // EleveSeeder::class,
            // TarifSeeder::class,
            // TarifMensuelSeeder::class,
            // ClasseSeeder::class,
            // MatiereSeeder::class,
            // MentionSeeder::class,
            
            
        ]);

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        //     'ecole_id' => 1,
        // ]);
    }
}
