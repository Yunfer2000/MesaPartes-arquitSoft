<?php

namespace Tests\Feature;

use App\Models\Administrado;
use App\Models\Area;
use App\Models\Expediente;
use App\Models\Notificacion;
use App\Models\TipoDocumento;
use App\Models\Usuario;
use App\Notifications\AvisoExpediente;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\PresentaTramite;
use Tests\TestCase;

/** CU08 a CU13, CU16 y CU17: recorrido completo del expediente. */
class FlujoMesaAreaTest extends TestCase
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

    private function expedientePresentado(): Expediente
    {
        return $this->presentarPorFormulario();
    }

    public function test_recorrido_completo_hasta_finalizar(): void
    {
        $exp = $this->expedientePresentado();
        $sa = Area::where('sigla', 'SA')->first();

        // CU08 Recepcionar
        $this->actingAs($this->mesa)->get('/mesa-de-partes')->assertSee($exp->numero);
        $this->get("/mesa-de-partes/expedientes/{$exp->id}")->assertOk()->assertSee('Recepcionar');
        $this->post("/mesa-de-partes/expedientes/{$exp->id}/recepcionar", $this->datosRecepcion())->assertRedirect();
        $exp->refresh();
        $this->assertSame(Expediente::RECIBIDO, $exp->estado);
        $this->assertNotNull($exp->fecha_recepcion);

        // CU10 Derivar a Secretaría Académica
        $this->post("/expedientes/{$exp->id}/derivar", ['area_id' => $sa->id, 'proveido' => 'Atender'])->assertRedirect('/mesa-de-partes');
        $exp->refresh();
        $this->assertSame(Expediente::DERIVADO, $exp->estado);
        $this->assertSame($sa->id, $exp->areaActual()->id);
        $this->assertSame(Area::mesaDePartes()->id, $exp->ultimoMovimiento()->area_origen_id);

        // Mesa de Partes ya no puede operar el expediente
        $this->post("/mesa-de-partes/expedientes/{$exp->id}/observar", ['motivo' => 'x'])->assertForbidden();

        // CU11 Confirmar recepción en área
        $this->actingAs($this->direccion)->post("/area/expedientes/{$exp->id}/confirmar")->assertForbidden();
        $this->actingAs($this->secretaria)->get('/area')->assertSee($exp->numero);
        $this->post("/area/expedientes/{$exp->id}/confirmar")->assertRedirect();
        $exp->refresh();
        $this->assertSame(Expediente::EN_ATENCION, $exp->estado);
        $this->assertTrue($exp->ultimoMovimiento()->confirmado);

        // CU12 Avance
        $this->post("/area/expedientes/{$exp->id}/avance", ['nota' => ''])->assertSessionHasErrors('nota');
        $this->post("/area/expedientes/{$exp->id}/avance", ['nota' => 'Se pidió el sílabo'])->assertRedirect();
        $this->assertSame('avance', $exp->ultimoMovimiento()->accion);

        // CU13 Respuesta
        $this->post("/area/expedientes/{$exp->id}/responder", ['contenido' => ''])->assertSessionHasErrors('contenido');
        $this->post("/area/expedientes/{$exp->id}/responder", [
            'contenido' => 'Se emite el certificado',
            'documento' => UploadedFile::fake()->create('certificado.pdf', 80, 'application/pdf'),
        ])->assertRedirect('/area');
        $exp->refresh();
        $this->assertSame(Expediente::FINALIZADO, $exp->estado);
        $this->assertCount(1, $exp->respuestas);

        // CU16: recepción, derivación y respuesta notificadas y enviadas por correo
        $this->assertSame(['presentación', 'recepción', 'derivación', 'respuesta'], Notificacion::orderBy('id')->pluck('evento')->all());
        $this->assertTrue(Notificacion::where('enviada', false)->doesntExist());
        Notification::assertSentOnDemandTimes(AvisoExpediente::class, 4);

        // El administrado ve la respuesta al consultar con su N.° de expediente y DNI
        $this->consultarComoAdministrado($exp)->assertSee('Se emite el certificado');
    }

    public function test_observar_y_volver_a_bandeja_tras_subsanar(): void
    {
        $exp = $this->expedientePresentado();

        $this->actingAs($this->mesa)->post("/mesa-de-partes/expedientes/{$exp->id}/observar", ['motivo' => ''])
            ->assertSessionHasErrors('motivo');
        $this->post("/mesa-de-partes/expedientes/{$exp->id}/observar", ['motivo' => 'Falta voucher'])->assertRedirect();
        $this->assertSame(Expediente::OBSERVADO, $exp->fresh()->estado);
        $this->get('/mesa-de-partes')->assertDontSee("/mesa-de-partes/expedientes/{$exp->id}");

        $this->consultarComoAdministrado($exp);
        $this->post("/consulta/{$exp->numero}/subsanar", ['descripcion' => 'Adjunto voucher'])->assertRedirect();

        $this->actingAs($this->mesa)->get('/mesa-de-partes')->assertSee("/mesa-de-partes/expedientes/{$exp->id}");
    }

    public function test_no_deriva_a_su_propia_area_ni_desde_estado_invalido(): void
    {
        $exp = $this->expedientePresentado();
        // Presentado: aún no se puede derivar
        $this->actingAs($this->mesa)->post("/expedientes/{$exp->id}/derivar", ['area_id' => Area::where('sigla', 'SA')->value('id')])
            ->assertSessionHas('aviso');

        $this->post("/mesa-de-partes/expedientes/{$exp->id}/recepcionar", $this->datosRecepcion());
        $this->post("/expedientes/{$exp->id}/derivar", ['area_id' => Area::mesaDePartes()->id])->assertSessionHas('aviso');
        $this->assertSame(Expediente::RECIBIDO, $exp->fresh()->estado);
    }

    public function test_area_rederiva_a_otra_area(): void
    {
        $exp = $this->expedientePresentado();
        $this->actingAs($this->mesa)->post("/mesa-de-partes/expedientes/{$exp->id}/recepcionar", $this->datosRecepcion());
        $this->post("/expedientes/{$exp->id}/derivar", ['area_id' => Area::where('sigla', 'SA')->value('id')]);
        $this->actingAs($this->secretaria)->post("/area/expedientes/{$exp->id}/confirmar");

        $this->post("/expedientes/{$exp->id}/derivar", [
            'area_id' => Area::where('sigla', 'DG')->value('id'),
            'usuario_destino_id' => $this->direccion->id,
        ])->assertRedirect('/area');

        $this->assertSame('DG', $exp->fresh()->areaActual()->sigla);
        $this->actingAs($this->direccion)->get('/area')->assertSee($exp->numero);
        $this->actingAs($this->secretaria)->get('/area')->assertDontSee($exp->numero);
        // Secretaría aún puede ver el expediente (pasó por su área), pero no operarlo
        $this->get("/area/expedientes/{$exp->id}")->assertOk()->assertDontSee('Registrar avance');
    }

    public function test_registro_presencial_de_administrado_nuevo_y_aviso_sin_correo(): void
    {
        $this->actingAs($this->mesa)->get('/mesa-de-partes/presencial?documento=28301456')->assertSee('El solicitante no está registrado');

        $this->post('/mesa-de-partes/presencial', $this->datosPresencial([
            'folios' => 2, 'archivo' => UploadedFile::fake()->create('oficio.pdf', 500, 'application/pdf'),
        ]))->assertRedirect()->assertSessionHasNoErrors();

        $exp = Expediente::firstOrFail();
        $this->assertSame('Presencial', $exp->modalidad);
        $this->assertSame(Expediente::RECIBIDO, $exp->estado);
        $this->assertNotNull($exp->fecha_recepcion);
        $this->assertNull(Administrado::where('numero_documento', '28301456')->value('usuario_id'));
        $this->get("/expedientes/{$exp->id}/constancia")->assertSee('Cargo de recepción');

        // CU16 3a: los registros nuevos piden correo, pero un solicitante antiguo puede no tenerlo:
        // entonces la notificación queda registrada como no enviada
        $exp->administrado->update(['correo' => null]);
        $this->post("/expedientes/{$exp->id}/derivar", ['area_id' => Area::where('sigla', 'DG')->value('id')]);
        $this->assertFalse(Notificacion::where('evento', 'derivación')->first()->enviada);
    }

    /** CA-CU09-02 y CA-CU16-03: la observación guarda motivo y detalle, y avisa al administrado. */
    public function test_observar_guarda_motivo_y_notifica(): void
    {
        $exp = $this->expedientePresentado();

        $this->actingAs($this->mesa)->post("/mesa-de-partes/expedientes/{$exp->id}/observar", [
            'motivo' => 'Falta el voucher', 'detalle' => 'Adjunta el voucher de pago del Banco de la Nación',
        ])->assertRedirect('/mesa-de-partes');

        $observacion = $exp->observaciones()->firstOrFail();
        $this->assertSame('Falta el voucher', $observacion->motivo);
        $this->assertSame('Adjunta el voucher de pago del Banco de la Nación', $observacion->detalle);
        $this->assertSame('observado', $exp->ultimoMovimiento()->accion);

        $aviso = Notificacion::where('evento', 'observación')->firstOrFail();
        $this->assertTrue($aviso->enviada);
        $this->assertStringContainsString($exp->numero, $aviso->mensaje);
    }

    /** CA-CU12-02: el avance del área se ve en el seguimiento público y no cambia el estado. */
    public function test_avance_se_ve_en_la_consulta_publica(): void
    {
        $exp = $this->expedientePresentado();
        $this->actingAs($this->mesa)->post("/mesa-de-partes/expedientes/{$exp->id}/recepcionar", $this->datosRecepcion());
        $this->post("/expedientes/{$exp->id}/derivar", ['area_id' => Area::where('sigla', 'SA')->value('id')]);
        $this->actingAs($this->secretaria)->post("/area/expedientes/{$exp->id}/confirmar");
        $this->post("/area/expedientes/{$exp->id}/avance", ['nota' => 'Se pidió el sílabo'])->assertRedirect();

        $exp->refresh();
        $this->assertSame(Expediente::EN_ATENCION, $exp->estado);
        $this->assertContains('avance', $exp->burbujas()->pluck('accion')->all());

        auth()->logout();
        $this->consultarComoAdministrado($exp)->assertOk()->assertSee('Avance');
    }
}
