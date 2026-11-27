<?php

namespace Tests\Feature\Api;

use App\Models\RegistroAuditoria;
use App\Models\TipoDocumento;
use App\Models\Usuario;
use App\Services\CatalogoTiposDocumento;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\PresentaTramite;
use Tests\TestCase;

/** API REST v1: recurso tipos-documento (especificación specs/API03-tipos-de-documento.md). */
class TiposDocumentoApiTest extends TestCase
{
    use PresentaTramite;
    use RefreshDatabase;

    protected $seed = true;
    protected $seeder = DatabaseSeeder::class;

    private function comoAdministrador(): Usuario
    {
        $admin = Usuario::where('correo', 'admin@demo.test')->firstOrFail();
        Sanctum::actingAs($admin);

        return $admin;
    }

    public function test_lista_los_tipos_de_documento_en_json(): void
    {
        $total = TipoDocumento::count();

        $this->getJson('/api/v1/tipos-documento')->assertOk()
            ->assertJsonCount($total, 'data')
            ->assertJsonStructure(['data' => [['id', 'nombre', 'descripcion', 'activo']]]);
    }

    public function test_filtra_solo_los_activos(): void
    {
        TipoDocumento::first()->update(['activo' => false]);
        app(CatalogoTiposDocumento::class)->olvidar();

        $respuesta = $this->getJson('/api/v1/tipos-documento?activo=1')->assertOk();
        $this->assertNotContains(false, array_column($respuesta->json('data'), 'activo'));
        $this->assertCount(TipoDocumento::where('activo', true)->count(), $respuesta->json('data'));
    }

    public function test_consulta_por_id_y_404_si_no_existe(): void
    {
        $tipo = TipoDocumento::first();

        $this->getJson("/api/v1/tipos-documento/{$tipo->id}")->assertOk()
            ->assertJsonPath('data.id', $tipo->id)->assertJsonPath('data.nombre', $tipo->nombre);
        $this->getJson('/api/v1/tipos-documento/999999')->assertNotFound()
            ->assertJsonPath('message', 'Recurso no encontrado.');
    }

    public function test_el_administrador_registra_un_tipo_y_responde_201(): void
    {
        $this->comoAdministrador();

        $this->postJson('/api/v1/tipos-documento', ['nombre' => '  Solicitud de traslado  ', 'descripcion' => 'Traslado externo'])
            ->assertCreated()
            ->assertHeader('Location')
            ->assertJsonPath('data.nombre', 'Solicitud de traslado')
            ->assertJsonPath('data.activo', true);

        $this->assertDatabaseHas('tipos_documento', ['nombre' => 'Solicitud de traslado']);
        $this->assertSame(1, RegistroAuditoria::where('accion', 'crear tipo de documento')->count());
    }

    public function test_rechaza_datos_invalidos_sin_guardar_nada(): void
    {
        $this->comoAdministrador();
        $antes = TipoDocumento::count();
        $existente = TipoDocumento::first()->nombre;

        $this->postJson('/api/v1/tipos-documento', ['nombre' => ''])->assertUnprocessable()->assertJsonValidationErrors('nombre');
        $this->postJson('/api/v1/tipos-documento', ['nombre' => $existente])->assertUnprocessable()
            ->assertJsonPath('errors.nombre.0', 'Ya existe un tipo de documento con ese nombre.');
        $this->postJson('/api/v1/tipos-documento', ['nombre' => str_repeat('x', 101)])->assertUnprocessable();

        $this->assertSame($antes, TipoDocumento::count());
    }

    public function test_actualiza_y_el_cambio_se_ve_al_volver_a_consultar(): void
    {
        $this->comoAdministrador();
        $tipo = TipoDocumento::first();

        $this->putJson("/api/v1/tipos-documento/{$tipo->id}", ['nombre' => 'Oficio múltiple', 'activo' => false])
            ->assertOk()->assertJsonPath('data.nombre', 'Oficio múltiple')->assertJsonPath('data.activo', false);

        $this->getJson("/api/v1/tipos-documento/{$tipo->id}")->assertJsonPath('data.nombre', 'Oficio múltiple');
        $this->putJson('/api/v1/tipos-documento/999999', ['nombre' => 'No existe'])->assertNotFound();
    }

    public function test_elimina_un_tipo_sin_uso_y_responde_204(): void
    {
        $this->comoAdministrador();
        $tipo = TipoDocumento::create(['nombre' => 'Temporal', 'activo' => true]);

        $this->deleteJson("/api/v1/tipos-documento/{$tipo->id}")->assertNoContent();
        $this->assertDatabaseMissing('tipos_documento', ['id' => $tipo->id]);
        $this->deleteJson("/api/v1/tipos-documento/{$tipo->id}")->assertNotFound();
    }

    public function test_no_elimina_un_tipo_usado_por_expedientes_y_responde_409(): void
    {
        $exp = $this->presentarPorFormulario();
        $tipo = TipoDocumento::first();
        $exp->update(['tipo_documento_id' => $tipo->id]);
        $this->comoAdministrador();

        $this->deleteJson("/api/v1/tipos-documento/{$tipo->id}")->assertStatus(409)
            ->assertJsonPath('message', 'No se puede eliminar: hay expedientes con este tipo de documento. Desactívalo en su lugar.');
        $this->assertDatabaseHas('tipos_documento', ['id' => $tipo->id]);
    }

    public function test_sin_token_responde_401_y_sin_rol_administrador_403(): void
    {
        $this->postJson('/api/v1/tipos-documento', ['nombre' => 'Sin token'])->assertUnauthorized()
            ->assertJsonPath('message', 'Falta el token de acceso o no es válido.');

        Sanctum::actingAs(Usuario::where('correo', 'mesadepartes@demo.test')->firstOrFail());
        $this->postJson('/api/v1/tipos-documento', ['nombre' => 'Sin rol'])->assertForbidden();
        $this->assertDatabaseMissing('tipos_documento', ['nombre' => 'Sin rol']);
    }

    public function test_la_lista_sale_de_la_cache_y_se_renueva_al_cambiar(): void
    {
        Cache::flush();
        DB::enableQueryLog();
        $this->getJson('/api/v1/tipos-documento')->assertOk();
        $consultasPrimera = count(array_filter(DB::getQueryLog(), fn ($q) => str_contains($q['query'], 'tipos_documento')));
        DB::flushQueryLog();
        $this->getJson('/api/v1/tipos-documento')->assertOk();
        $consultasSegunda = count(array_filter(DB::getQueryLog(), fn ($q) => str_contains($q['query'], 'tipos_documento')));

        $this->assertSame(1, $consultasPrimera, 'la primera vez consulta la base de datos');
        $this->assertSame(0, $consultasSegunda, 'la segunda vez la lista sale de la caché');

        $this->comoAdministrador();
        $this->postJson('/api/v1/tipos-documento', ['nombre' => 'Nuevo por API'])->assertCreated();
        $this->assertFalse(Cache::has(CatalogoTiposDocumento::CLAVE), 'al crear se borra la caché');
        $this->assertContains('Nuevo por API', array_column($this->getJson('/api/v1/tipos-documento')->json('data'), 'nombre'));
    }

    public function test_la_cache_funciona_con_un_almacen_que_serializa(): void
    {
        // En producción la caché (database, file o redis) guarda texto serializado y no acepta clases.
        config(['cache.stores.array.serialize' => true]);
        Cache::forgetDriver('array');
        Cache::flush();

        $primera = $this->getJson('/api/v1/tipos-documento')->assertOk()->json('data');
        $segunda = $this->getJson('/api/v1/tipos-documento')->assertOk()->json('data');

        $this->assertSame($primera, $segunda, 'la segunda respuesta sale de la caché y es igual');
        $this->assertIsBool($segunda[0]['activo']);
        $this->getJson('/api/v1/tipos-documento?activo=1')->assertOk()->assertJsonMissing(['activo' => false]);
    }
}
