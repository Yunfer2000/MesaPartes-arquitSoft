<?php

namespace Tests\Feature\Api;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\PresentaTramite;
use Tests\TestCase;

/** API REST v1: CU19 verificar documento (specs/API02-verificar-documento.md). */
class VerificacionApiTest extends TestCase
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

    public function test_verifica_una_constancia_y_devuelve_la_huella(): void
    {
        $exp = $this->presentarPorFormulario([
            'archivo' => UploadedFile::fake()->createWithContent('solicitud.pdf', '%PDF-1.4 solicitud original'),
        ]);
        $codigo = strtolower(str_replace('-', '', $exp->codigo_verificacion));

        $this->getJson("/api/v1/verificaciones/{$codigo}")->assertOk()
            ->assertJsonPath('data.autentico', true)
            ->assertJsonPath('data.codigo', $exp->codigo_verificacion)
            ->assertJsonPath('data.tipo', 'Constancia de presentación')
            ->assertJsonPath('data.expediente', $exp->numero)
            ->assertJsonPath('data.documentos.0.hash_sha256', hash('sha256', '%PDF-1.4 solicitud original'));
    }

    public function test_un_codigo_inexistente_responde_404(): void
    {
        $this->getJson('/api/v1/verificaciones/AAAA-BBBB-CCCC')->assertNotFound()
            ->assertJsonPath('autentico', false);
    }
}
