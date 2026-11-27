<?php

namespace Tests\Feature;

use App\Models\Area;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Áreas del organigrama 2026 cargadas al instalar el sistema. */
class AreasOrganigramaTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;
    protected $seeder = DatabaseSeeder::class;

    public function test_se_cargan_las_areas_del_organigrama(): void
    {
        foreach (['MP', 'DG', 'UAC', 'PE-ARC', 'PE-EI', 'PE-GA', 'PE-MA', 'UFC', 'UI', 'UBE', 'CAL', 'UA', 'CAJ', 'SA', 'ADM'] as $sigla) {
            $this->assertTrue(Area::where('sigla', $sigla)->where('activa', true)->exists(), "Falta el área {$sigla}");
        }
        $this->assertSame(28, Area::where('activa', true)->count());
        $this->assertFalse(Area::where('sigla', 'PE')->exists());
    }

    public function test_volver_a_cargar_no_duplica_areas(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->assertSame(28, Area::count());
    }
}
