<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Expediente;
use App\Models\RegistroAuditoria;
use App\Models\Rol;
use App\Models\TipoDocumento;
use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\PresentaTramite;
use Tests\TestCase;

/** CU03, CU04, CU14 y CU15. */
class AdminReportesTest extends TestCase
{
    use PresentaTramite;
    use RefreshDatabase;

    protected $seed = true;
    protected $seeder = DatabaseSeeder::class;

    private Usuario $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Notification::fake();
        $this->admin = Usuario::where('correo', 'admin@demo.test')->first();
    }

    private function usuario(string $correo): Usuario
    {
        return Usuario::where('correo', $correo)->first();
    }

    /** Presenta, recepciona y deriva un expediente a Secretaría Académica. */
    private function expedienteEnSecretaria(): Expediente
    {
        $exp = $this->presentarPorFormulario(['asunto' => 'Convalidación de cursos']);
        $this->actingAs($this->usuario('mesadepartes@demo.test'))->post("/mesa-de-partes/expedientes/{$exp->id}/recepcionar", $this->datosRecepcion());
        $this->post("/expedientes/{$exp->id}/derivar", ['area_id' => Area::where('sigla', 'SA')->value('id')]);

        return $exp->fresh();
    }

    public function test_crea_edita_y_desactiva_usuarios_con_auditoria(): void
    {
        $this->actingAs($this->admin)->get('/admin/usuarios')->assertOk()->assertSee('mesadepartes@demo.test');

        $rolArea = Rol::where('nombre', Rol::AREA)->value('id');
        $this->post('/admin/usuarios', ['nombres' => 'Ana', 'apellidos' => 'Ccahuana', 'correo' => 'ana@demo.test',
            'rol_id' => $rolArea, 'contrasena' => 'clave-segura'])->assertSessionHasErrors('area_id');
        $this->post('/admin/usuarios', ['nombres' => 'Ana', 'apellidos' => 'Ccahuana', 'correo' => 'secretaria@demo.test',
            'rol_id' => $rolArea, 'area_id' => Area::where('sigla', 'UA')->value('id'), 'contrasena' => 'clave-segura'])
            ->assertSessionHasErrors('correo');
        $this->post('/admin/usuarios', ['nombres' => 'Ana', 'apellidos' => 'Ccahuana', 'correo' => 'ana@demo.test',
            'rol_id' => $rolArea, 'area_id' => Area::where('sigla', 'UA')->value('id'), 'contrasena' => 'clave-segura'])
            ->assertRedirect('/admin/usuarios');

        $ana = $this->usuario('ana@demo.test');
        $this->put("/admin/usuarios/{$ana->id}", ['nombres' => 'Ana María', 'apellidos' => 'Ccahuana', 'correo' => 'ana@demo.test',
            'rol_id' => $rolArea, 'area_id' => $ana->area_id])->assertRedirect();
        $this->assertSame('Ana María', $ana->fresh()->nombres);
        $this->patch("/admin/usuarios/{$ana->id}/estado");
        $this->assertFalse($ana->fresh()->activo);

        $this->assertSame(['crear usuario', 'editar usuario', 'desactivar usuario'],
            RegistroAuditoria::orderBy('id')->pluck('accion')->all());
        $this->get('/admin/auditoria')->assertSee('desactivar usuario');

        // No puede desactivarse a sí mismo
        $this->patch("/admin/usuarios/{$this->admin->id}/estado")->assertSessionHas('aviso');
    }

    public function test_areas_y_tipos(): void
    {
        $this->actingAs($this->admin)->post('/admin/areas', ['nombre' => 'Bienestar Estudiantil', 'sigla' => 'BE'])->assertRedirect();
        $this->post('/admin/areas', ['nombre' => 'Bienestar Estudiantil', 'sigla' => 'BE2'])->assertSessionHasErrors('nombre');
        $this->post('/admin/tipos-documento', ['nombre' => 'Constancia'])->assertRedirect();
        $this->assertTrue(TipoDocumento::where('nombre', 'Constancia')->exists());

        // 3a. No se desactiva un área con expedientes pendientes
        $this->expedienteEnSecretaria();
        $sa = Area::where('sigla', 'SA')->first();
        $this->actingAs($this->admin)->patch("/admin/areas/{$sa->id}/estado")->assertSessionHas('aviso');
        $this->assertTrue($sa->fresh()->activa);
        $this->patch('/admin/areas/'.Area::where('sigla', 'BE')->value('id').'/estado');
        $this->assertFalse(Area::where('sigla', 'BE')->first()->activa);
    }

    public function test_busqueda_segun_rol(): void
    {
        $exp = $this->expedienteEnSecretaria();

        $this->actingAs($this->admin)->get('/busqueda?numero='.$exp->numero)->assertSee($exp->numero);
        $this->get('/busqueda?estado=Finalizado')->assertSee('No se encontraron');
        $this->get("/expedientes/{$exp->id}")->assertOk()->assertSee('Historial');

        $this->actingAs($this->usuario('secretaria@demo.test'))->get('/busqueda?asunto=Convalidación')->assertSee($exp->numero);
        // Dirección nunca recibió el expediente: no lo encuentra ni lo abre (RNF15)
        $this->actingAs($this->usuario('direccion@demo.test'))->get('/busqueda?asunto=Convalidación')->assertDontSee($exp->numero);
        $this->get("/expedientes/{$exp->id}")->assertForbidden();
        // Sin iniciar sesión no se entra a la búsqueda del personal
        auth()->logout();
        $this->get('/busqueda')->assertRedirect('/ingresar');
    }

    public function test_reportes_y_descarga_excel(): void
    {
        $this->expedienteEnSecretaria();

        $this->actingAs($this->admin)->get('/reportes')->assertOk()
            ->assertSee('Secretaría Académica')
            ->assertViewHas('totales', fn ($t) => $t['Presentados'] === 1 && $t['Recibidos'] === 1 && $t['Derivados'] === 1);

        // Mejora: la descarga es un Excel (.xlsx), ya no un CSV
        $this->get('/reportes?formato=excel')->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        // Periodo sin datos: totales en cero
        $this->get('/reportes?desde=2020-01-01&hasta=2020-01-31')
            ->assertViewHas('totales', fn ($t) => array_sum($t) === 0);

        // Un área solo ve su información
        $this->actingAs($this->usuario('direccion@demo.test'))->get('/reportes')
            ->assertViewHas('totales', fn ($t) => $t['Presentados'] === 0);
    }
}
