<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class POperacjeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $operations = ['tworzenie', 'edytowanie', 'usuwanie'];

        foreach ($operations as $operation) {
            \App\Models\POperacje::firstOrCreate(['nazwa' => $operation]);
        }
    }
}
