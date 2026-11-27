<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\RegistroAuditoria;
use App\Models\Rol;
use App\Models\TipoDocumento;
use App\Models\Usuario;
use App\Support\Menu;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** Panel del Administrador: issues #23, #24, #25 y #26 (CU03 y CU04). */
class PanelAdministradorTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;
    protected $seeder = DatabaseSeeder::class;

    private Usuario $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Usuario::where('correo', 'admin@demo.test')->first();
    }

    /** ¿El formulario flotante (<dialog>) se abre solo al cargar la pantalla? */
    private function abierto(string $html, string $id): bool
    {
        return (bool) preg_match('/<dialog[^>]*id="'.$id.'"[^>]*data-abierto/', $html);
    }

    /** CA-CU03-04 (issue #24). */
    public function test_el_menu_del_administrador_empieza_por_usuarios_y_areas(): void
    {
        $this->actingAs($this->admin)->get('/admin/areas');

        $this->assertSame(
            ['Usuarios', 'Áreas', 'Tipos de documento', 'Pendientes', 'Historial de expedientes', 'Reportes', 'Auditoría'],
            array_column(Menu::para($this->admin), 'texto'),
        );
        $this->assertSame(route('admin.usuarios.index'), Menu::inicio($this->admin));
        $this->get('/admin/areas')->assertSeeInOrder(['Usuarios', 'Áreas', 'Tipos de documento', 'Pendientes']);
    }

    /** CA-CU04-06 (issue #23). */
    public function test_crea_un_area_desde_el_boton_crear_nueva_area(): void
    {
        $pantalla = $this->actingAs($this->admin)->get('/admin/areas')->assertOk()
            ->assertSee('Crear nueva área')->assertSee('data-abrir="form-area"', false);
        $this->assertFalse($this->abierto($pantalla->getContent(), 'form-area'));

        // Así lo envía el navegador: los campos opcionales llegan vacíos
        $this->post('/admin/areas', ['nombre' => 'Oficina de Imagen Institucional', 'sigla' => ' oii ',
            'area_padre_id' => '', 'responsable_id' => ''])
            ->assertRedirect('/admin/areas')->assertSessionHas('exito', 'Área creada.');
        $area = Area::where('nombre', 'Oficina de Imagen Institucional')->first();
        $this->assertSame('OII', $area->sigla);
        $this->assertTrue($area->activa);
        $this->assertTrue(RegistroAuditoria::where('accion', 'crear área')->exists());

        // Sigla repetida: el formulario flotante vuelve a abrirse con el error
        $pantalla = $this->from('/admin/areas')->followingRedirects()
            ->post('/admin/areas', ['nombre' => 'Otra oficina', 'sigla' => 'OII'])->assertSee('class="error"', false);
        $this->assertTrue($this->abierto($pantalla->getContent(), 'form-area'));
        $this->assertSame(1, Area::where('sigla', 'OII')->count());

        // Editar abre el mismo formulario flotante con los datos del área
        $pantalla = $this->get('/admin/areas?editar='.$area->id)->assertSee('Editar área');
        $this->assertTrue($this->abierto($pantalla->getContent(), 'form-area'));
    }

    /** CA-CU03-05 (issue #25). */
    public function test_crea_un_usuario_desde_el_boton_crear_nuevo_usuario(): void
    {
        $pantalla = $this->actingAs($this->admin)->get('/admin/usuarios')->assertOk()
            ->assertSee('Crear nuevo usuario')->assertSee('data-abrir="form-usuario"', false);
        $this->assertFalse($this->abierto($pantalla->getContent(), 'form-usuario'));

        $mesa = ['rol_id' => Rol::where('nombre', Rol::MESA_DE_PARTES)->value('id'), 'area_id' => Area::mesaDePartes()->id];
        $this->post('/admin/usuarios', $mesa + ['nombres' => 'Rosa', 'apellidos' => 'Huamaní', 'correo' => 'rosa@demo.test',
            'contrasena' => 'clave123'])
            ->assertRedirect('/admin/usuarios')->assertSessionHas('exito', 'Usuario creado.');
        $rosa = Usuario::where('correo', 'rosa@demo.test')->first();
        $this->assertTrue($rosa->activo);
        $this->assertTrue(Hash::check('clave123', $rosa->contrasena));
        $this->assertTrue(RegistroAuditoria::where('accion', 'crear usuario')->exists());

        // Contraseña corta: el formulario flotante vuelve a abrirse con el error
        $pantalla = $this->from('/admin/usuarios')->followingRedirects()
            ->post('/admin/usuarios', $mesa + ['nombres' => 'Luis', 'apellidos' => 'Pariona', 'correo' => 'luis@demo.test',
                'contrasena' => 'corta'])->assertSee('class="error"', false);
        $this->assertTrue($this->abierto($pantalla->getContent(), 'form-usuario'));
        $this->assertFalse(Usuario::where('correo', 'luis@demo.test')->exists());

        $pantalla = $this->get('/admin/usuarios?editar='.$rosa->id)->assertSee('Editar usuario');
        $this->assertTrue($this->abierto($pantalla->getContent(), 'form-usuario'));
    }

    /** CA-CU04-07 (issue #26). */
    public function test_agrega_un_tipo_de_documento_desde_su_boton(): void
    {
        $pantalla = $this->actingAs($this->admin)->get('/admin/tipos-documento')->assertOk()
            ->assertSee('Agregar tipo de documento')->assertSee('data-abrir="form-tipo"', false);
        $this->assertFalse($this->abierto($pantalla->getContent(), 'form-tipo'));

        $this->post('/admin/tipos-documento', ['nombre' => 'Constancia de estudios', 'descripcion' => ''])
            ->assertRedirect('/admin/tipos-documento')->assertSessionHas('exito', 'Tipo de documento creado.');
        $tipo = TipoDocumento::where('nombre', 'Constancia de estudios')->first();
        $this->assertTrue($tipo->activo);
        $this->assertTrue(RegistroAuditoria::where('accion', 'crear tipo de documento')->exists());

        // Nombre repetido (aunque tenga espacios): el formulario flotante vuelve a abrirse con el error
        $pantalla = $this->from('/admin/tipos-documento')->followingRedirects()
            ->post('/admin/tipos-documento', ['nombre' => ' Constancia de estudios '])->assertSee('class="error"', false);
        $this->assertTrue($this->abierto($pantalla->getContent(), 'form-tipo'));
        $this->assertSame(1, TipoDocumento::where('nombre', 'Constancia de estudios')->count());

        $pantalla = $this->get('/admin/tipos-documento?editar='.$tipo->id)->assertSee('Editar tipo de documento');
        $this->assertTrue($this->abierto($pantalla->getContent(), 'form-tipo'));

        // El tipo nuevo ya sale en el formulario público
        auth()->logout();
        $this->get('/tramite/nuevo')->assertOk()->assertSee('Constancia de estudios');
    }
}
