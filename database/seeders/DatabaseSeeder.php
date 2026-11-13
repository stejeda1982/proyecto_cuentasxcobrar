<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Seguridad\Database\Seeders\SeguridadDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Semilla base del proyecto: roles y usuarios de la Fase 0.
     */
    public function run(): void
    {
        $this->call([
            SeguridadDatabaseSeeder::class,
        ]);
    }
}
