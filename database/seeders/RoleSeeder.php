<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run()
    {
        Role::create(['name' => 'Administrateur']);
        Role::create(['name' => 'Directeur']);
        Role::create(['name' => 'Caissiere']);
    }
}