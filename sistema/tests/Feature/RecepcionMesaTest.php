<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Expediente;
use App\Models\TipoDocumento;
use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\PresentaTramite;
use Tests\TestCase;

/** Bloque 2: recepción en Mesa de Partes (CU08) y registro presencial (CU17). */
class RecepcionMesaTest extends TestCase
{
    use PresentaTramite;
    use RefreshDatabase;

    protected $seed = true;
    protected $seeder = DatabaseSeeder::class;

    private Usuario $mesa;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Notification::fake();
        $this->mesa = Usuario::where('correo', 'mesadepartes@demo.test')->first();
    }

    public function test_no_se_recepciona_dos_veces(): void
    {
        $exp = $this->presentarPorFormulario();

        $this->actingAs($this->mesa)->post("/mesa-de-partes/expedientes/{$exp->id}/recepcionar", $this->datosRecepcion())
            ->assertSessionHasNoErrors();
        // Segundo clic (o un segundo operador al mismo tiempo): el expediente ya está Recibido
        $this->post("/mesa-de-partes/expedientes/{$exp->id}/recepcionar", $this->datosRecepcion())->assertSessionHas('aviso');

        $this->assertSame(1, $exp->movimientos()->where('accion', 'recibido')->count());
    }

    public function test_mesa_completa_tipo_folios_y_numero_al_recepcionar(): void
    {
        $exp = $this->presentarPorFormulario();
        $tipo = TipoDocumento::where('id', '!=', $exp->tipo_documento_id)->firstOrFail();

        $this->actingAs($this->mesa)->get("/mesa-de-partes/expedientes/{$exp->id}")
            ->assertOk()->assertSee('Folios *')->assertSee('N.° de documento');

        $this->post("/mesa-de-partes/expedientes/{$exp->id}/recepcionar", [
            'tipo_documento_id' => $tipo->id, 'folios' => 4, 'nro_documento' => 'Solicitud N.° 015-2026',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $exp->refresh();
        $this->assertSame(Expediente::RECIBIDO, $exp->estado);
        $this->assertSame($tipo->id, $exp->tipo_documento_id);
        $this->assertSame(4, $exp->folios);
        $this->assertSame('Solicitud N.° 015-2026', $exp->nro_documento);
        $this->get("/mesa-de-partes/expedientes/{$exp->id}")->assertSee('Solicitud N.° 015-2026');
    }

    public function test_si_faltan_los_folios_no_se_recepciona(): void
    {
        $exp = $this->presentarPorFormulario();
        $exp->forceFill(['folios' => null])->save();

        $this->actingAs($this->mesa)->post("/mesa-de-partes/expedientes/{$exp->id}/recepcionar", ['folios' => ''])
            ->assertSessionHasErrors('folios');
        $this->post("/mesa-de-partes/expedientes/{$exp->id}/recepcionar", ['folios' => 0])
            ->assertSessionHasErrors('folios');

        $this->assertSame(Expediente::PRESENTADO, $exp->fresh()->estado);
    }

    public function test_registro_presencial_sin_escaneo(): void
    {
        $this->actingAs($this->mesa)->get('/mesa-de-partes/presencial?documento=28301456')
            ->assertSee('Documento escaneado')->assertSee('máximo 10 MB (opcional)');

        $this->post('/mesa-de-partes/presencial', $this->datosPresencial())->assertRedirect()->assertSessionHasNoErrors();

        $exp = Expediente::firstOrFail();
        $this->assertSame(Expediente::RECIBIDO, $exp->estado);
        $this->assertSame(3, $exp->folios);
        $this->assertSame('Oficio N.° 012-2026', $exp->nro_documento);
        $this->assertSame(0, $exp->documentos()->count());
        $this->get("/expedientes/{$exp->id}/constancia")->assertSee('Documento físico (sin escaneo)');
    }

    public function test_registro_presencial_muestra_formulario_completo_sin_buscar(): void
    {
        $this->actingAs($this->mesa)->get('/mesa-de-partes/presencial')->assertOk()
            ->assertSee('Tipo de persona')->assertSee('N.° de documento')
            ->assertSee('Nombres')->assertSee('Celular')->assertSee('Correo electrónico')
            ->assertSee('Asunto')->assertSee('Folios')
            ->assertSee('Registrar')
            ->assertSee('Todavía no hay registros presenciales.');
    }

    public function test_registro_presencial_dice_solicitante_y_no_administrado(): void
    {
        $respuesta = $this->actingAs($this->mesa)->get('/mesa-de-partes/presencial?documento=99999999')->assertOk()
            ->assertSee('<span class="num">1</span> Solicitante', false)->assertSee('El solicitante no está registrado');

        // Solo los textos visibles: los nombres de campos (tipo_administrado) no cuentan
        $texto = strip_tags(preg_replace('#<(script|style)\b.*?</\1>#s', '', $respuesta->getContent()));
        $this->assertStringNotContainsStringIgnoringCase('administrado', $texto);
    }

    public function test_registro_presencial_lista_solo_los_presenciales_con_enlace_a_constancia(): void
    {
        $virtual = $this->presentarPorFormulario(['asunto' => 'Trámite virtual']);
        $this->actingAs($this->mesa)->post('/mesa-de-partes/presencial', $this->datosPresencial(['folios' => 2]))
            ->assertSessionHasNoErrors();
        $presencial = Expediente::where('modalidad', 'Presencial')->firstOrFail();

        // Lo ve cualquier operador de Mesa de Partes, no solo quien lo registró
        $otroOperador = $this->mesa->replicate();
        $otroOperador->correo = 'otro.operador@demo.test';
        $otroOperador->save();

        $this->actingAs($otroOperador)->get('/mesa-de-partes/presencial')->assertOk()
            ->assertSeeInOrder(['Expediente', 'Solicitante', 'Fecha', 'Estado'])
            ->assertSee($presencial->numero)
            ->assertSee(route('tramites.constancia', $presencial), false)
            ->assertSee('Rosa')
            ->assertSee($presencial->fecha_presentacion->format('d/m/Y H:i'))
            ->assertSee(Expediente::RECIBIDO)
            ->assertDontSee($virtual->numero);
    }

    public function test_bandeja_busca_por_codigo_propio(): void
    {
        // El formulario público ya no pide código propio; los expedientes que ya lo tienen se siguen encontrando
        $exp = $this->presentarPorFormulario();
        $exp->update(['codigo_propio' => 'CONSTANCIA2026']);
        $otro = $this->presentarPorFormulario(['asunto' => 'Otro trámite']);

        $this->actingAs($this->mesa)->get('/mesa-de-partes?q=constancia2026')
            ->assertSee($exp->numero)->assertDontSee($otro->numero);
    }

    public function test_registro_presencial_exige_folios(): void
    {
        $sinFolios = $this->datosPresencial();
        unset($sinFolios['folios']);
        $this->actingAs($this->mesa)->post('/mesa-de-partes/presencial', $sinFolios)->assertSessionHasErrors('folios');
        $this->post('/mesa-de-partes/presencial', $this->datosPresencial(['folios' => 0]))->assertSessionHasErrors('folios');

        $this->assertSame(0, Expediente::count());
    }

    /** CA-CU08-03: si el ciudadano no eligió el tipo, Mesa de Partes debe elegirlo. */
    public function test_si_falta_el_tipo_no_se_recepciona(): void
    {
        $exp = $this->presentarPorFormulario();
        $exp->forceFill(['tipo_documento_id' => null])->save();

        $this->actingAs($this->mesa)->post("/mesa-de-partes/expedientes/{$exp->id}/recepcionar", ['folios' => 2])
            ->assertSessionHasErrors('tipo_documento_id');
        $this->assertSame(Expediente::PRESENTADO, $exp->fresh()->estado);
    }

    /** CA-CU17-05: el escaneo guarda su huella y el cargo lleva código de verificación y QR. */
    public function test_cargo_presencial_lleva_codigo_y_huella(): void
    {
        $this->actingAs($this->mesa)->post('/mesa-de-partes/presencial', $this->datosPresencial([
            'archivo' => UploadedFile::fake()->createWithContent('oficio.pdf', '%PDF-1.4 oficio escaneado'),
        ]))->assertRedirect()->assertSessionHasNoErrors();

        $exp = Expediente::firstOrFail();
        $this->assertSame(hash('sha256', '%PDF-1.4 oficio escaneado'), $exp->documentos()->firstOrFail()->hash_sha256);
        $this->assertMatchesRegularExpression('/^[A-Z2-9]{4}-[A-Z2-9]{4}-[A-Z2-9]{4}$/', $exp->codigo_verificacion);
        $this->get("/expedientes/{$exp->id}/constancia")->assertOk()
            ->assertSee('Cargo de recepción')->assertSee($exp->codigo_verificacion)
            ->assertSee('/verificar/'.$exp->codigo_verificacion)->assertSee('<svg', false);
    }
}
