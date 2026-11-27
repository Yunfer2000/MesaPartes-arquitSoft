<?php

namespace Tests\Feature;

use App\Models\Expediente;
use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\PresentaTramite;
use Tests\TestCase;

/** Issue #30: búsqueda básica por N.° de expediente o DNI y botón «Búsqueda por filtros» (CU14). */
class BusquedaBasicaTest extends TestCase
{
    use PresentaTramite;
    use RefreshDatabase;

    protected $seed = true;
    protected $seeder = DatabaseSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Notification::fake();
    }

    private function admin(): Usuario
    {
        return Usuario::where('correo', 'admin@demo.test')->first();
    }

    /** CA-CU14-02 */
    public function test_busqueda_basica_por_expediente_o_documento(): void
    {
        $natural = $this->presentarPorFormulario(['asunto' => 'Constancia de estudios']);
        $juridica = $this->presentarPorFormulario([
            'tipo_persona' => 'juridica', 'ruc' => '20123456789', 'razon_social' => 'Empresa Andina SAC',
            'numero_documento' => '41234567', 'nombres' => 'Rosa', 'apellido_paterno' => 'Pariona', 'apellido_materno' => 'Cárdenas',
            'asunto' => 'Convenio de prácticas',
        ]);

        $this->actingAs($this->admin());
        // Parte del número (sin "EXP-2026-")
        $this->get('/busqueda?q='.substr($natural->numero, -6))->assertOk()
            ->assertSee($natural->numero)->assertDontSee($juridica->numero);
        // DNI de la persona natural
        $this->get('/busqueda?q=70451236')->assertSee($natural->numero)->assertDontSee($juridica->numero);
        // RUC de la entidad y DNI de su representante
        $this->get('/busqueda?q=20123456789')->assertSee($juridica->numero)->assertDontSee($natural->numero);
        $this->get('/busqueda?q=41234567')->assertSee($juridica->numero)->assertDontSee($natural->numero);
        // Un DNI que no existe
        $this->get('/busqueda?q=99999999')->assertSee('No se encontraron');
    }

    /** CA-CU14-02 */
    public function test_boton_busqueda_por_filtros(): void
    {
        $this->actingAs($this->admin());

        // Búsqueda básica: un solo campo y los filtros plegados detrás del botón
        $this->get('/busqueda')->assertOk()
            ->assertSee('N.° de expediente o DNI')
            ->assertSee('Búsqueda por filtros')
            ->assertSee('<details class="filtros" id="filtros">', false);

        // Con un filtro en uso, el panel se abre solo para que se vea qué se filtró
        $this->get('/busqueda?estado=Finalizado')->assertOk()
            ->assertSee('<details class="filtros" id="filtros" open>', false)
            ->assertSee('No se encontraron');
    }
}
