<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** CU02 Iniciar sesión (solo personal). CU01 Registrar cuenta se eliminó. */
class AccesoTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;
    protected $seeder = DatabaseSeeder::class;

    public function test_ya_no_existe_el_registro_de_cuentas(): void
    {
        $this->get('/registro')->assertNotFound();
        $this->post('/registro')->assertNotFound();
        $this->get('/mis-tramites')->assertNotFound();
        $this->get('/ingresar')->assertOk()->assertDontSee('Crear cuenta');
    }

    public function test_cada_rol_entra_a_su_pantalla(): void
    {
        foreach ([
            'mesadepartes@demo.test' => '/mesa-de-partes',
            'secretaria@demo.test' => '/area',
            'admin@demo.test' => '/admin/usuarios',
        ] as $correo => $ruta) {
            $this->post('/ingresar', ['correo' => $correo, 'contrasena' => 'password'])->assertRedirect($ruta);
            $this->get($ruta)->assertOk();
            $this->post('/salir');
        }
    }

    public function test_credenciales_incorrectas_y_cuenta_inactiva(): void
    {
        $this->post('/ingresar', ['correo' => 'admin@demo.test', 'contrasena' => 'otra'])
            ->assertSessionHasErrors('correo');
        $this->assertGuest();

        Usuario::where('correo', 'secretaria@demo.test')->update(['activo' => false]);
        $this->post('/ingresar', ['correo' => 'secretaria@demo.test', 'contrasena' => 'password'])
            ->assertSessionHasErrors('correo');
        $this->assertGuest();
    }

    public function test_un_rol_no_entra_a_pantallas_de_otro(): void
    {
        $this->actingAs(Usuario::where('correo', 'secretaria@demo.test')->first())
            ->get('/mesa-de-partes')->assertForbidden();
        $this->get('/admin/usuarios')->assertForbidden();
        $this->post('/salir');
        $this->get('/area')->assertRedirect('/ingresar');
    }

    /** CA-CU02-03: tras 5 intentos fallidos se bloquea el ingreso por un tiempo (RNF05). */
    public function test_bloquea_tras_cinco_intentos_fallidos(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/ingresar', ['correo' => 'admin@demo.test', 'contrasena' => 'otra'])->assertSessionHasErrors('correo');
        }

        $this->post('/ingresar', ['correo' => 'admin@demo.test', 'contrasena' => 'password'])
            ->assertSessionHasErrors('correo');
        $this->assertStringContainsString('Demasiados intentos', session('errors')->first('correo'));
        $this->assertGuest();
    }
}
