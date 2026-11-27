<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Usuario;
use App\Support\AreasDerivacion;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\PresentaTramite;
use Tests\TestCase;

/** Mejora de interfaz, paso 1: organigrama en las áreas, acciones de a una y pantalla Derivar más simple. */
class InterfazDerivarTest extends TestCase
{
    use PresentaTramite;
    use RefreshDatabase;

    protected $seed = true;
    protected $seeder = DatabaseSeeder::class;

    private function area(string $sigla): Area
    {
        return Area::where('sigla', $sigla)->firstOrFail();
    }

    private function siglas($areas): array
    {
        return $areas->pluck('sigla')->sort()->values()->all();
    }

    public function test_el_seeder_guarda_el_organigrama(): void
    {
        $this->assertSame($this->area('DG')->id, $this->area('UBE')->area_padre_id);
        $this->assertSame($this->area('UBE')->id, $this->area('TOP')->area_padre_id);
        $this->assertSame($this->area('UA')->id, $this->area(Area::MESA_DE_PARTES)->area_padre_id);
        $this->assertNull($this->area('DG')->area_padre_id);
    }

    public function test_sugiere_areas_segun_quien_deriva(): void
    {
        $areas = Area::where('activa', true)->get();

        // Mesa de Partes: Dirección General y las unidades que dependen de ella
        $mp = AreasDerivacion::para($areas, $this->area(Area::MESA_DE_PARTES));
        $this->assertContains('DG', $this->siglas($mp['sugeridas']));
        $this->assertContains('UBE', $this->siglas($mp['sugeridas']));
        $this->assertNotContains('TOP', $this->siglas($mp['sugeridas']));

        // Una unidad: sus subáreas, Dirección General y Mesa de Partes
        $ube = AreasDerivacion::para($areas, $this->area('UBE'));
        $this->assertSame(['DG', 'MP', 'SBC', 'SEM', 'SPP', 'TOP'], $this->siglas($ube['sugeridas']));

        // Una subárea: su jefe, sus hermanas y Mesa de Partes (nunca a sí misma)
        $top = AreasDerivacion::para($areas, $this->area('TOP'));
        $this->assertSame(['MP', 'SBC', 'SEM', 'SPP', 'UBE'], $this->siglas($top['sugeridas']));

        // Solo sugiere: todas las demás áreas siguen disponibles, agrupadas por unidad
        $this->assertSame($areas->count() - 1, collect($top['grupos'])->flatten()->count());
        $this->assertContains('CAJ', $this->siglas($top['grupos']['Área Administrativa']));
    }

    public function test_la_pantalla_muestra_acciones_de_a_una_y_derivar_simple(): void
    {
        Storage::fake('local');
        Notification::fake();
        $mesa = Usuario::where('correo', 'mesadepartes@demo.test')->first();
        $exp = $this->presentarPorFormulario();
        $this->actingAs($mesa)->post("/mesa-de-partes/expedientes/{$exp->id}/recepcionar", $this->datosRecepcion());

        $this->get("/mesa-de-partes/expedientes/{$exp->id}")->assertOk()
            ->assertSee('¿Qué deseas hacer?')
            ->assertSeeInOrder(['data-panel="panel-derivar"', 'data-panel="panel-observar"'], false)
            ->assertSee('Enviar copia a otras áreas (opcional)')
            ->assertSee('data-padre=', false); // Mejora: subáreas en lugar de personas
    }

    public function test_el_administrador_asigna_de_que_unidad_depende_un_area(): void
    {
        $admin = Usuario::where('correo', 'admin@demo.test')->first();
        $this->actingAs($admin)->post('/admin/areas', [
            'nombre' => 'Comisión de Titulación', 'sigla' => 'CTI', 'area_padre_id' => $this->area('UAC')->id,
        ])->assertRedirect('/admin/areas');
        $this->assertSame($this->area('UAC')->id, $this->area('CTI')->area_padre_id);

        $cti = $this->area('CTI');
        $this->put("/admin/areas/{$cti->id}", ['nombre' => 'Comisión de Titulación', 'sigla' => 'CTI', 'area_padre_id' => $cti->id])
            ->assertSessionHasErrors('area_padre_id');
    }
}
