<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Copia;
use App\Models\Expediente;
use App\Models\Rol;
use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\PresentaTramite;
use Tests\TestCase;

/** CU10 Derivar de uno a uno y de uno a muchos (copias para conocimiento), y doble derivación. */
class DerivacionMultipleTest extends TestCase
{
    use PresentaTramite;
    use RefreshDatabase;

    protected $seed = true;
    protected $seeder = DatabaseSeeder::class;

    private Usuario $mesa;
    private Usuario $secretaria;
    private Usuario $direccion;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Notification::fake();
        $this->mesa = Usuario::where('correo', 'mesadepartes@demo.test')->first();
        $this->secretaria = Usuario::where('correo', 'secretaria@demo.test')->first();
        $this->direccion = Usuario::where('correo', 'direccion@demo.test')->first();
    }

    /** Expediente presentado por el formulario y recepcionado por Mesa de Partes. */
    private function expedienteRecibido(): Expediente
    {
        $exp = $this->presentarPorFormulario();
        $this->actingAs($this->mesa)->post("/mesa-de-partes/expedientes/{$exp->id}/recepcionar", $this->datosRecepcion())->assertRedirect();

        return $exp->refresh();
    }

    private function area(string $sigla): Area
    {
        return Area::where('sigla', $sigla)->firstOrFail();
    }

    public function test_deriva_a_un_area_y_envia_copia_a_varias(): void
    {
        $exp = $this->expedienteRecibido();
        $sa = $this->area('SA');

        $this->post("/expedientes/{$exp->id}/derivar", [
            'area_id' => $sa->id,
            'proveido' => 'Reunión general el viernes',
            'copias' => [$this->area('DG')->id, $this->area('UAC')->id],
        ])->assertRedirect('/mesa-de-partes')->assertSessionHas('exito', fn ($m) => str_contains($m, 'copia a 2 áreas'));

        // El expediente sigue en un solo lugar: el área destino
        $exp->refresh();
        $this->assertSame(Expediente::DERIVADO, $exp->estado);
        $this->assertSame($sa->id, $exp->areaActual()->id);
        $this->assertSame(2, $exp->copias()->where('leido', false)->count());

        // Dirección recibió la copia: la ve en su bandeja y puede abrir el expediente, pero no atenderlo
        $this->actingAs($this->direccion)->get('/area')->assertOk()->assertSee('Para conocimiento')->assertSee($exp->numero);
        $this->get("/area/expedientes/{$exp->id}")->assertOk()->assertSee('Marcar como leído');
        $this->post("/area/expedientes/{$exp->id}/confirmar")->assertForbidden();

        // Marca la copia como leída; una segunda vez avisa que ya estaba leída
        $copia = $exp->copias()->where('area_id', $this->area('DG')->id)->first();
        $this->post("/area/copias/{$copia->id}/leido")->assertRedirect()->assertSessionHas('exito');
        $copia->refresh();
        $this->assertTrue($copia->leido);
        $this->assertSame($this->direccion->id, $copia->leido_por_id);
        $this->post("/area/copias/{$copia->id}/leido")->assertSessionHas('aviso');
        $this->get('/area')->assertDontSee($exp->numero);

        // Secretaría Académica atiende con normalidad
        $this->actingAs($this->secretaria)->post("/area/expedientes/{$exp->id}/confirmar")->assertRedirect();
        $this->assertSame(Expediente::EN_ATENCION, $exp->refresh()->estado);
    }

    public function test_solo_el_area_que_recibio_la_copia_puede_marcarla(): void
    {
        $exp = $this->expedienteRecibido();
        $this->post("/expedientes/{$exp->id}/derivar", ['area_id' => $this->area('SA')->id, 'copias' => [$this->area('UAC')->id]]);
        $copia = Copia::firstOrFail();

        $this->actingAs($this->direccion)->post("/area/copias/{$copia->id}/leido")->assertForbidden();
        $this->actingAs($this->direccion)->get("/area/expedientes/{$exp->id}")->assertForbidden();
        $this->assertFalse($copia->refresh()->leido);
    }

    public function test_no_se_envia_copia_al_area_destino_ni_a_mesa_de_partes(): void
    {
        $exp = $this->expedienteRecibido();
        $sa = $this->area('SA');

        $this->post("/expedientes/{$exp->id}/derivar", ['area_id' => $sa->id, 'copias' => [$sa->id]])
            ->assertSessionHas('aviso', fn ($m) => str_contains($m, 'no puede recibir copia'));
        $this->post("/expedientes/{$exp->id}/derivar", ['area_id' => $sa->id, 'copias' => [$this->area('DG')->id, Area::mesaDePartes()->id]])
            ->assertSessionHas('aviso');

        // No se guardó nada: ni la derivación ni las copias
        $this->assertSame(Expediente::RECIBIDO, $exp->refresh()->estado);
        $this->assertSame(0, Copia::count());
        $this->assertSame(0, $exp->movimientos()->where('accion', 'derivado')->count());
    }

    public function test_derivar_dos_veces_el_mismo_expediente_falla(): void
    {
        $exp = $this->expedienteRecibido();
        $this->post("/expedientes/{$exp->id}/derivar", ['area_id' => $this->area('SA')->id])->assertSessionHas('exito');

        // Otra persona de Mesa de Partes intenta derivarlo otra vez (por ejemplo, con la pantalla aún abierta)
        $otro = Usuario::create(['rol_id' => Rol::where('nombre', Rol::MESA_DE_PARTES)->value('id'),
            'area_id' => Area::mesaDePartes()->id, 'nombres' => 'Segundo', 'apellidos' => 'Operador',
            'correo' => 'mp2@demo.test', 'contrasena' => 'password', 'activo' => true]);
        try {
            app(\App\Services\FlujoExpediente::class)->derivar($exp->fresh(), $otro, $this->area('DG'), null, null);
            $this->fail('Debió rechazar la segunda derivación.');
        } catch (\App\Exceptions\ReglaNegocio $e) {
            $this->assertStringContainsString('estado Derivado', $e->getMessage());
        }
        $this->assertSame(1, $exp->movimientos()->where('accion', 'derivado')->count());

        // Confirmar dos veces tampoco se puede
        $this->actingAs($this->secretaria)->post("/area/expedientes/{$exp->id}/confirmar")->assertRedirect();
        $this->actingAs($this->secretaria)->post("/area/expedientes/{$exp->id}/confirmar")->assertSessionHas('aviso');
    }
}
