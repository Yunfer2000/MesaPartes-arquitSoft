<?php

namespace Tests\Feature\Api;

use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** API REST v1: token de acceso del personal (specs/API04-token-de-acceso.md). */
class TokenApiTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;
    protected $seeder = DatabaseSeeder::class;

    public function test_entrega_un_token_bearer_y_con_el_se_opera(): void
    {
        $token = $this->postJson('/api/v1/tokens', ['correo' => 'admin@demo.test', 'contrasena' => 'password'])
            ->assertCreated()
            ->assertJsonPath('tipo', 'Bearer')
            ->assertJsonPath('usuario.rol', 'Administrador')
            ->json('token');

        $this->withToken($token)->postJson('/api/v1/tipos-documento', ['nombre' => 'Creado con token'])->assertCreated();
    }

    public function test_credenciales_incorrectas_o_usuario_inactivo_responden_401(): void
    {
        $this->postJson('/api/v1/tokens', ['correo' => 'admin@demo.test', 'contrasena' => 'otra'])->assertUnauthorized();

        Usuario::where('correo', 'admin@demo.test')->update(['activo' => false]);
        $this->postJson('/api/v1/tokens', ['correo' => 'admin@demo.test', 'contrasena' => 'password'])->assertUnauthorized();
        $this->postJson('/api/v1/tokens', ['correo' => 'no-es-correo'])->assertUnprocessable();
    }

    public function test_revocar_el_token_lo_deja_sin_uso(): void
    {
        $token = $this->postJson('/api/v1/tokens', ['correo' => 'admin@demo.test', 'contrasena' => 'password'])->json('token');

        $this->withToken($token)->deleteJson('/api/v1/tokens/actual')->assertNoContent();
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->postJson('/api/v1/tipos-documento', ['nombre' => 'Ya no'])->assertUnauthorized();
    }
}
