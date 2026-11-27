<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Expediente;
use App\Models\TipoDocumento;
use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\PresentaTramite;
use Tests\TestCase;

/** Mejora de interfaz, pasos 3 a 5: contadores y pestañas en las bandejas, y formulario del ciudadano. */
class InterfazBandejasFormularioTest extends TestCase
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

    public function test_el_menu_muestra_cuantos_esperan_en_la_bandeja(): void
    {
        $this->presentarPorFormulario();
        $mesa = Usuario::where('correo', 'mesadepartes@demo.test')->first();

        $this->actingAs($mesa)->get('/mesa-de-partes')->assertOk()
            ->assertSeeInOrder(['Bandeja de entrada', '<span class="contador"', '>1</span>'], false);
    }

    public function test_bandeja_del_area_tiene_pestanas_con_su_numero(): void
    {
        $direccion = Usuario::where('correo', 'direccion@demo.test')->first();

        $this->actingAs($direccion)->get('/area')->assertOk()
            ->assertSeeInOrder(['Por atender', 'class="cuenta"', 'Para conocimiento', 'class="cuenta"'], false)
            ->assertSee('class="responsiva"', false);
        $this->get('/area?ver=conocimiento')->assertOk()->assertSee('No tienes copias pendientes de leer.');
    }

    public function test_el_ciudadano_puede_indicar_el_tipo_de_documento(): void
    {
        $this->get('/tramite/nuevo')->assertOk()
            ->assertSee('¿Qué vas a presentar?')
            ->assertSee('name="tipo_documento_id"', false)
            ->assertSee('Revisa tus datos antes de enviar')
            ->assertDontSee('Opciones adicionales');

        $solicitud = TipoDocumento::where('nombre', 'Solicitud')->first();
        $exp = $this->presentarPorFormulario(['tipo_documento_id' => $solicitud->id]);
        $this->assertSame($solicitud->id, $exp->tipo_documento_id);
        $this->assertSame(Area::mesaDePartes()->id, $exp->area_destino_id);

        // Mesa de Partes lo ve ya marcado al recepcionar y puede cambiarlo
        $mesa = Usuario::where('correo', 'mesadepartes@demo.test')->first();
        $this->actingAs($mesa)->get("/mesa-de-partes/expedientes/{$exp->id}")->assertOk()
            ->assertSee('<option value="'.$solicitud->id.'" selected>', false);
    }

    public function test_tipo_de_documento_invalido_se_rechaza(): void
    {
        $this->withSession(['captcha' => '7'])->post('/tramite', ['tipo_documento_id' => 9999])
            ->assertSessionHasErrors('tipo_documento_id');
        $this->assertSame(0, Expediente::count());
    }
}
