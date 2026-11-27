<?php

namespace Tests\Feature\Api;

use App\Models\Area;
use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\PresentaTramite;
use Tests\TestCase;

/** API REST v1: CU07 consultar seguimiento (specs/API01-consultar-expediente.md). */
class ConsultaApiTest extends TestCase
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

    public function test_devuelve_estado_area_y_recorrido_con_numero_y_dni(): void
    {
        $exp = $this->presentarPorFormulario();

        $this->postJson('/api/v1/consultas', ['numero' => $exp->numero, 'numero_documento' => '70451236'])
            ->assertOk()
            ->assertJsonPath('data.numero', $exp->numero)
            ->assertJsonPath('data.estado', 'Presentado')
            ->assertJsonPath('data.area_actual', 'Mesa de Partes')
            ->assertJsonPath('data.recorrido.0.titulo', 'Presentado')
            ->assertJsonPath('data.recorrido.1.estado', 'pendiente')
            ->assertJsonMissingPath('data.correo');
    }

    public function test_el_recorrido_se_actualiza_al_derivar(): void
    {
        $exp = $this->presentarPorFormulario();
        $this->actingAs(Usuario::where('correo', 'mesadepartes@demo.test')->first())
            ->post("/mesa-de-partes/expedientes/{$exp->id}/recepcionar", $this->datosRecepcion());
        $this->post("/expedientes/{$exp->id}/derivar", ['area_id' => Area::where('sigla', 'SA')->value('id')]);
        auth()->logout();

        $datos = $this->postJson('/api/v1/consultas', ['numero' => strtolower($exp->numero), 'numero_documento' => '70451236'])
            ->assertOk()->json('data');

        $this->assertSame('Derivado', $datos['estado']);
        $this->assertSame(['Presentado', 'Recibido', 'Derivado', 'Respuesta pendiente'], array_column($datos['recorrido'], 'titulo'));
    }

    public function test_no_dice_cual_dato_fallo_y_responde_404(): void
    {
        $exp = $this->presentarPorFormulario();
        $mensaje = 'No encontramos un expediente con ese número o código y ese documento de identidad.';

        $this->postJson('/api/v1/consultas', ['numero' => $exp->numero, 'numero_documento' => '99999999'])
            ->assertNotFound()->assertJsonPath('message', $mensaje);
        $this->postJson('/api/v1/consultas', ['numero' => 'EXP-2000-000001', 'numero_documento' => '70451236'])
            ->assertNotFound()->assertJsonPath('message', $mensaje);
    }

    public function test_valida_los_datos_obligatorios(): void
    {
        $this->postJson('/api/v1/consultas', [])->assertUnprocessable()
            ->assertJsonValidationErrors(['numero', 'numero_documento']);
    }

    public function test_limita_a_10_consultas_por_minuto(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/v1/consultas', ['numero' => 'X', 'numero_documento' => '1'])->assertNotFound();
        }
        $this->postJson('/api/v1/consultas', ['numero' => 'X', 'numero_documento' => '1'])->assertStatus(429)->assertJsonPath('message', 'Demasiadas peticiones. Espera un minuto e inténtalo de nuevo.');
    }

    public function test_la_consulta_usa_pocas_consultas_sql_sin_n_mas_1(): void
    {
        $exp = $this->presentarPorFormulario();
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->postJson('/api/v1/consultas', ['numero' => $exp->numero, 'numero_documento' => '70451236'])->assertOk();

        // 1 expediente (con índice único), 1 administrado, 1 movimientos, 1 áreas: carga anticipada.
        $this->assertLessThanOrEqual(5, count(DB::getQueryLog()));
    }
}
