<?php

namespace Tests\Feature;

use App\Models\Expediente;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Issue #22: captcha de Cloudflare Turnstile en el formulario público (RNF05). */
class CaptchaTurnstileTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected $seeder = DatabaseSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Notification::fake();
    }

    private function activarTurnstile(): void
    {
        config(['services.turnstile.site_key' => '1x00000000000000000000AA',
            'services.turnstile.secret_key' => '1x0000000000000000000000000000000AA']);
    }

    private function enviar(array $cambios = [])
    {
        return $this->post('/tramite', $cambios + [
            'tipo_doc_identidad' => 'DNI',
            'numero_documento' => '70451236',
            'nombres' => 'José Luis',
            'apellido_paterno' => 'Quispe', 'apellido_materno' => 'Huamán',
            'tipo_persona' => 'natural',
            'celular' => '966123456',
            'correo' => 'estudiante@demo.test',
            'asunto' => 'Certificado modular',
            'archivo' => UploadedFile::fake()->create('s.pdf', 100, 'application/pdf'),
            'acepta_datos' => '1',
        ]);
    }

    public function test_sin_claves_sigue_la_pregunta_de_suma(): void
    {
        $this->get('/tramite/nuevo')->assertOk()->assertSee('¿Cuánto es', false)->assertDontSee('cf-turnstile');
    }

    public function test_con_claves_muestra_turnstile_y_no_la_suma(): void
    {
        $this->activarTurnstile();

        $this->get('/tramite/nuevo')->assertOk()
            ->assertSee('class="cf-turnstile"', false)
            ->assertSee('data-sitekey="1x00000000000000000000AA"', false)
            ->assertSee('challenges.cloudflare.com/turnstile/v0/api.js', false)
            ->assertDontSee('¿Cuánto es', false);
    }

    public function test_cloudflare_aprueba_y_se_registra_el_tramite(): void
    {
        $this->activarTurnstile();
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true])]);

        $this->enviar(['cf-turnstile-response' => 'token-de-prueba'])->assertSessionHasNoErrors();

        $this->assertSame(1, Expediente::count());
        Http::assertSent(fn ($r) => $r['response'] === 'token-de-prueba' && $r['secret'] === '1x0000000000000000000000000000000AA');
    }

    public function test_cloudflare_rechaza_o_falta_el_token(): void
    {
        $this->activarTurnstile();
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => false, 'error-codes' => ['invalid-input-response']])]);

        $this->enviar(['cf-turnstile-response' => 'token-falso'])->assertSessionHasErrors('captcha');
        $this->enviar()->assertSessionHasErrors('captcha');
        $this->assertSame(0, Expediente::count());
    }

    public function test_si_cloudflare_no_responde_no_se_acepta(): void
    {
        $this->activarTurnstile();
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('sin conexión'));

        $this->enviar(['cf-turnstile-response' => 'token'])->assertSessionHasErrors('captcha');
        $this->assertSame(0, Expediente::count());
    }
}
