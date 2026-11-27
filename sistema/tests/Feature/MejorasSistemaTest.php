<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Expediente;
use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Reader\XLSX\Reader;
use Tests\PresentaTramite;
use Tests\TestCase;

/** Mejoras del sistema (noviembre 2026): derivar a subáreas, historial, respuesta sin documento y reportes. */
class MejorasSistemaTest extends TestCase
{
    use PresentaTramite;
    use RefreshDatabase;

    protected $seed = true;
    protected $seeder = DatabaseSeeder::class;

    private Usuario $mesa;
    private Usuario $secretaria;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Notification::fake();
        $this->mesa = Usuario::where('correo', 'mesadepartes@demo.test')->first();
        $this->secretaria = Usuario::where('correo', 'secretaria@demo.test')->first();
    }

    private function area(string $sigla): Area
    {
        return Area::where('sigla', $sigla)->firstOrFail();
    }

    private function recibido(): Expediente
    {
        $exp = $this->presentarPorFormulario();
        $this->actingAs($this->mesa)->post("/mesa-de-partes/expedientes/{$exp->id}/recepcionar", $this->datosRecepcion());

        return $exp;
    }

    /** Lo deja En atención en Secretaría Académica. */
    private function enSecretaria(): Expediente
    {
        $exp = $this->recibido();
        $this->post("/expedientes/{$exp->id}/derivar", ['area_id' => $this->area('SA')->id]);
        $this->actingAs($this->secretaria)->post("/area/expedientes/{$exp->id}/confirmar");

        return $exp->refresh();
    }

    public function test_derivar_muestra_subareas_sin_buscador_ni_personas(): void
    {
        $exp = $this->recibido();

        $this->get("/mesa-de-partes/expedientes/{$exp->id}")->assertOk()
            ->assertDontSee('Escribe para buscar')
            ->assertDontSee('¿A una persona en especial?')
            ->assertDontSee('name="usuario_destino_id"', false)
            ->assertSee('¿A qué subárea en especial? (opcional)')
            // Caja es subárea de la Unidad Administrativa: va en el segundo desplegable con su área
            ->assertSee('data-padre="'.$this->area('UA')->id.'"', false)
            ->assertSee('Caja');
    }

    public function test_deriva_a_una_subarea_y_le_llega_a_esa_subarea(): void
    {
        $exp = $this->recibido();

        $this->post("/expedientes/{$exp->id}/derivar", [
            'area_id' => $this->area('UA')->id, 'subarea_id' => $this->area('CAJ')->id, 'proveido' => 'Verificar el pago',
        ])->assertRedirect('/mesa-de-partes');

        $exp->refresh();
        $this->assertSame(Expediente::DERIVADO, $exp->estado);
        $this->assertSame('CAJ', $exp->areaActual()->sigla);
    }

    public function test_sin_subarea_llega_al_area_y_no_acepta_subarea_de_otra_area(): void
    {
        $exp = $this->recibido();

        // La subárea del Tópico no pertenece a la Unidad Administrativa
        $this->post("/expedientes/{$exp->id}/derivar", ['area_id' => $this->area('UA')->id, 'subarea_id' => $this->area('TOP')->id])
            ->assertSessionHas('aviso', 'La subárea elegida no pertenece al área.');
        $this->assertSame(Expediente::RECIBIDO, $exp->refresh()->estado);

        $this->post("/expedientes/{$exp->id}/derivar", ['area_id' => $this->area('UA')->id])->assertRedirect('/mesa-de-partes');
        $this->assertSame('UA', $exp->refresh()->areaActual()->sigla);
    }

    public function test_historial_muestra_los_15_ultimos_y_mantiene_la_busqueda(): void
    {
        // Un expediente por el formulario y 16 copias más recientes (el formulario limita los envíos seguidos)
        $primero = $this->presentarPorFormulario();
        foreach (range(1, 16) as $i) {
            $ultimo = $primero->replicate(['codigo_verificacion']);
            $ultimo->forceFill(['numero' => Expediente::generarNumero(), 'asunto' => "Trámite {$i}",
                'fecha_presentacion' => $primero->fecha_presentacion->copy()->addMinutes($i)])->save();
        }

        $this->actingAs($this->mesa)->get('/busqueda')->assertOk()
            ->assertSee('Historial de expedientes')
            ->assertSee('Últimos 15 expedientes')
            ->assertSee('N.° de expediente o DNI')
            ->assertSee('Búsqueda por filtros')
            ->assertViewHas('ultimos', fn ($l) => $l->count() === 15 && $l->first()->id === $ultimo->id)
            ->assertDontSee($primero->numero);

        // Al buscar se ven los resultados en lugar de los últimos
        $this->get('/busqueda?q='.$primero->numero)->assertSee($primero->numero)->assertViewHas('ultimos', null);
    }

    public function test_un_area_solo_ve_en_el_historial_lo_que_paso_por_ella(): void
    {
        $exp = $this->enSecretaria();
        $otro = $this->presentarPorFormulario(['asunto' => 'No llegó a Secretaría', 'numero_documento' => '70451299']);

        $this->actingAs($this->secretaria)->get('/busqueda')
            ->assertSee($exp->numero)->assertDontSee($otro->numero);
    }

    public function test_finaliza_sin_documento_de_respuesta(): void
    {
        $exp = $this->enSecretaria();

        $this->get("/area/expedientes/{$exp->id}")->assertSee('Documento de respuesta (opcional)');
        $this->post("/area/expedientes/{$exp->id}/responder", ['contenido' => 'Atendido'])
            ->assertSessionHasNoErrors()->assertRedirect('/area');

        $exp->refresh();
        $this->assertSame(Expediente::FINALIZADO, $exp->estado);
        $this->assertCount(0, $exp->respuestas->first()->documentos);
        // El PDF de la respuesta se genera igual, con su código de verificación
        $this->get('/respuestas/'.$exp->respuestas->first()->id.'/pdf')->assertOk();
    }

    public function test_reporte_sin_panel_repetido_y_descarga_en_excel(): void
    {
        $this->enSecretaria();
        $admin = Usuario::where('correo', 'admin@demo.test')->first();

        $this->actingAs($admin)->get('/reportes')->assertOk()
            ->assertDontSee('Estado actual de los expedientes')
            ->assertSee('Descargar en Excel')->assertDontSee('CSV');

        $respuesta = $this->get('/reportes?formato=excel');
        $respuesta->assertOk()->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringEndsWith('.xlsx', trim($respuesta->headers->get('content-disposition'), '"'));

        // Se abre como Excel y trae los totales
        $archivo = $respuesta->baseResponse->getFile()->getPathname();
        $lector = new Reader;
        $lector->open($archivo);
        $filas = [];
        foreach ($lector->getSheetIterator() as $hoja) {
            foreach ($hoja->getRowIterator() as $fila) {
                $filas[] = $fila->toArray();
            }
        }
        $lector->close();
        unlink($archivo);

        $this->assertContains(['Derivados', 1], $filas);
        $this->assertContains(['Presentados', 1], $filas);
    }
}
