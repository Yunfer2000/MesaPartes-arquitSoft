<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Expediente;
use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** CU18 Monitorear plazos y pendientes: semáforo (RF24) y panel de pendientes (RF25). */
class SemaforoTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;
    protected $seeder = DatabaseSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    /** Presenta un trámite por el formulario público un lunes a las 9:00. */
    private function presentarUnLunes(string $dni = '41234567'): Expediente
    {
        $this->travelTo('2026-11-02 09:00');

        $this->withSession(['captcha' => '7'])->post('/tramite', [
            'tipo_doc_identidad' => 'DNI',
            'numero_documento' => $dni,
            'nombres' => 'Rosa',
            'apellido_paterno' => 'Pariona', 'apellido_materno' => 'Quispe',
            'tipo_persona' => 'natural',
            'celular' => '966111222',
            'correo' => 'rosa@example.com',
            'asunto' => 'Constancia de estudios',
            'archivo' => UploadedFile::fake()->create('solicitud.pdf', 100, 'application/pdf'),
            'acepta_datos' => '1',
            'captcha' => '7',
        ]);

        return Expediente::latest('id')->firstOrFail();
    }

    public function test_cambia_de_color_segun_los_dias_habiles(): void
    {
        $exp = $this->presentarUnLunes();

        $this->travelTo('2026-11-05 09:00'); // jueves: 3 días hábiles
        $this->assertSame('verde', $exp->fresh()->semaforo());

        $this->travelTo('2026-11-09 09:00'); // lunes siguiente: 5 días hábiles (no cuenta sábado ni domingo)
        $this->assertSame(5, $exp->fresh()->diasEnArea());
        $this->assertSame('ambar', $exp->fresh()->semaforo());

        $this->travelTo('2026-11-11 09:00'); // miércoles: 7 días hábiles
        $this->assertSame('rojo', $exp->fresh()->semaforo());
    }

    public function test_finalizado_u_observado_no_lleva_semaforo(): void
    {
        $exp = $this->presentarUnLunes();
        $this->travelTo('2026-11-20 09:00');

        $exp->update(['estado' => Expediente::FINALIZADO]);
        $this->assertNull($exp->fresh()->semaforo());

        $exp->update(['estado' => Expediente::OBSERVADO]);
        $this->assertNull($exp->fresh()->semaforo());
    }

    public function test_bandeja_de_mesa_de_partes_muestra_el_semaforo(): void
    {
        $this->presentarUnLunes();
        $this->travelTo('2026-11-11 09:00');

        $this->actingAs(Usuario::where('correo', 'mesadepartes@demo.test')->first())
            ->get('/mesa-de-partes')->assertOk()->assertSee('semaforo-rojo');
    }

    public function test_panel_de_pendientes_para_administrador_y_direccion(): void
    {
        $exp = $this->presentarUnLunes();
        $this->travelTo('2026-11-11 09:00');

        $this->actingAs(Usuario::where('correo', 'admin@demo.test')->first())
            ->get('/pendientes')->assertOk()->assertSee($exp->numero)->assertSee('semaforo-rojo')
            ->assertSee('Atrasados')->assertSee('de 1 · 100 %')->assertSee('Requieren atención')
            ->assertSee('<option value="'.Area::where('sigla', 'SA')->value('id').'"', false);

        $this->actingAs(Usuario::where('correo', 'direccion@demo.test')->first())
            ->get('/pendientes')->assertOk()->assertSee($exp->numero);
    }

    public function test_panel_de_pendientes_filtra_por_estado(): void
    {
        $exp = $this->presentarUnLunes(); // queda en estado Presentado
        $admin = Usuario::where('correo', 'admin@demo.test')->first();

        // El desplegable muestra los estados y las etiquetas están unidas a su campo.
        $this->actingAs($admin)->get('/pendientes')->assertOk()
            ->assertSee('Presentado')
            ->assertSee('for="filtro-estado"', false)->assertSee('id="filtro-estado"', false);

        $this->actingAs($admin)->get('/pendientes?estado=Presentado')->assertOk()
            ->assertSee($exp->numero);

        $this->actingAs($admin)->get('/pendientes?estado=Derivado')->assertOk()
            ->assertDontSee($exp->numero)
            ->assertSee('No hay expedientes pendientes en estado <b>Derivado</b>', false);

        // Un estado que no existe se ignora y se muestran todos.
        $this->actingAs($admin)->get('/pendientes?estado=Inventado')->assertOk()
            ->assertSee($exp->numero);
    }

    public function test_alerta_cuando_un_area_tiene_mas_de_cinco_en_rojo(): void
    {
        $admin = Usuario::where('correo', 'admin@demo.test')->first();
        $mesa = Area::mesaDePartes();

        // Con 5 expedientes en rojo en Mesa de Partes todavía no hay aviso.
        foreach (range(1, 5) as $i) {
            $this->presentarUnLunes('4123456'.$i);
        }
        $this->travelTo('2026-11-11 09:00');
        $this->actingAs($admin)->get('/pendientes')->assertOk()
            ->assertDontSee('expedientes en rojo (más de 5)');

        // El sexto pasa el límite: sale el aviso con el área, la cantidad y el enlace para filtrarla.
        $this->presentarUnLunes('41234566');
        $this->travelTo('2026-11-11 09:00');
        $this->actingAs($admin)->get('/pendientes')->assertOk()
            ->assertSee('class="alert alert-rojo" role="alert"', false)
            ->assertSee('<b>'.$mesa->nombre.'</b> tiene <b>6</b> expedientes en rojo (más de 5)', false)
            ->assertSee(route('pendientes', ['area_id' => $mesa->id]), false);
    }

    public function test_otros_usuarios_no_entran_al_panel(): void
    {
        $this->actingAs(Usuario::where('correo', 'secretaria@demo.test')->first())
            ->get('/pendientes')->assertForbidden();

        $this->actingAs(Usuario::where('correo', 'mesadepartes@demo.test')->first())
            ->get('/pendientes')->assertForbidden();
    }
}
