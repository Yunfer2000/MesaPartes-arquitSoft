<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CatalogoSeeder::class);

        // Usuarios y expedientes de ejemplo: solo en desarrollo, nunca en el servidor de la institución.
        if (app()->environment('local', 'testing')) {
            $this->call(DemoSeeder::class);
        }
    }
}
