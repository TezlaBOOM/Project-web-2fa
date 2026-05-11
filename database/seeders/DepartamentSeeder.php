<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Departament;

class DepartamentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Departament::firstOrCreate(
            ['Nazwa' => 'all'],
            ['Description' => 'Wgląd do każdego departamentu']
        );
    }
}
