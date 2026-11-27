<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Expediente;
use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\PresentaTramite;
use Tests\TestCase;

/** RF26 y CU19: firma electrónica del sistema (código de verificación, huella SHA-256 y QR). */
class VerificacionTest extends TestCase
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

    /** Presenta, recepciona, deriva a Secretaría y responde con un PDF. */
    private function expedienteRespondido(UploadedFile $pdf): Expediente
    {
        $exp = $this->presentarPorFormulario();
        $this->actingAs(Usuario::where('correo', 'mesadepartes@demo.test')->first())
            ->post("/mesa-de-partes/expedientes/{$exp->id}/recepcionar", $this->datosRecepcion());
        $this->post("/expedientes/{$exp->id}/derivar", ['area_id' => Area::where('sigla', 'SA')->value('id')]);
        $this->actingAs(Usuario::where('correo', 'secretaria@demo.test')->first())
            ->post("/area/expedientes/{$exp->id}/confirmar");
        $this->post("/area/expedientes/{$exp->id}/responder", ['contenido' => 'Se emite la constancia', 'documento' => $pdf])
            ->assertSessionHasNoErrors();

        return $exp->fresh();
    }

    public function test_constancias_respuestas_y_archivos_llevan_codigo_y_huella(): void
    {
        $pdf = UploadedFile::fake()->createWithContent('constancia.pdf', '%PDF-1.4 respuesta del area');
        $exp = $this->expedienteRespondido($pdf);

        $this->assertMatchesRegularExpression('/^[A-Z2-9]{4}-[A-Z2-9]{4}-[A-Z2-9]{4}$/', $exp->codigo_verificacion);
        $respuesta = $exp->respuestas()->firstOrFail();
        $this->assertNotNull($respuesta->codigo_verificacion);
        $this->assertNotSame($exp->codigo_verificacion, $respuesta->codigo_verificacion);

        $this->assertSame(hash('sha256', '%PDF-1.4 respuesta del area'), $respuesta->documentos()->first()->hash_sha256);
        $this->assertSame(0, $exp->documentos()->whereNull('hash_sha256')->count());
    }

    public function test_la_constancia_muestra_el_codigo_y_el_qr(): void
    {
        $exp = $this->presentarPorFormulario();

        $this->get("/consulta/{$exp->numero}/constancia")->assertOk()
            ->assertSee('Firma electrónica del sistema')->assertSee($exp->codigo_verificacion)
            ->assertSee('/verificar/'.$exp->codigo_verificacion)->assertSee('<svg', false);
    }

    public function test_verificar_constancia_y_comprobar_el_archivo(): void
    {
        $exp = $this->presentarPorFormulario([
            'archivo' => UploadedFile::fake()->createWithContent('solicitud.pdf', '%PDF-1.4 solicitud original'),
        ]);

        // Con el código escrito en minúsculas y sin guiones también lo encuentra
        $sinGuiones = strtolower(str_replace('-', '', $exp->codigo_verificacion));
        $this->get('/verificar?codigo='.$sinGuiones)->assertRedirect('/verificar/'.$exp->codigo_verificacion);
        $this->get('/verificar/'.$exp->codigo_verificacion)->assertOk()
            ->assertSee('Documento auténtico')->assertSee($exp->numero)->assertSee('Constancia de presentación')
            ->assertSee(hash('sha256', '%PDF-1.4 solicitud original'));

        $this->post('/verificar/'.$exp->codigo_verificacion, [
            'archivo' => UploadedFile::fake()->createWithContent('copia.pdf', '%PDF-1.4 solicitud original'),
        ])->assertOk()->assertSee('el archivo no fue modificado');

        $this->post('/verificar/'.$exp->codigo_verificacion, [
            'archivo' => UploadedFile::fake()->createWithContent('cambiado.pdf', '%PDF-1.4 solicitud CAMBIADA'),
        ])->assertOk()->assertSee('No coincide');
    }

    public function test_verificar_respuesta_muestra_quien_la_firmo(): void
    {
        $exp = $this->expedienteRespondido(UploadedFile::fake()->createWithContent('constancia.pdf', '%PDF-1.4 respuesta'));
        $respuesta = $exp->respuestas()->firstOrFail();

        auth()->logout();
        $this->get('/verificar/'.$respuesta->codigo_verificacion)->assertOk()
            ->assertSee('Respuesta del área')->assertSee('Secretaría Académica')->assertSee($exp->numero);
        $this->consultarComoAdministrado($exp)->assertSee($respuesta->codigo_verificacion);
    }

    public function test_codigo_que_no_existe(): void
    {
        $this->get('/verificar')->assertOk()->assertSee('Código de verificación');
        $this->get('/verificar/AAAA-BBBB-CCCC')->assertOk()->assertSee('No encontramos ningún documento');
    }
}
