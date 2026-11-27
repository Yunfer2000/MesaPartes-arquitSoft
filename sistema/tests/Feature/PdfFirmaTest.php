<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Expediente;
use App\Models\Rol;
use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\PresentaTramite;
use Tests\TestCase;

/** RF26: la constancia y las respuestas se descargan en PDF con el código de verificación y el QR. */
class PdfFirmaTest extends TestCase
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

    /** Presenta, recepciona, deriva a Secretaría y responde. */
    private function expedienteRespondido(): Expediente
    {
        $exp = $this->presentarPorFormulario();
        $this->actingAs(Usuario::where('correo', 'mesadepartes@demo.test')->first())
            ->post("/mesa-de-partes/expedientes/{$exp->id}/recepcionar", $this->datosRecepcion());
        $this->post("/expedientes/{$exp->id}/derivar", ['area_id' => Area::where('sigla', 'SA')->value('id')]);
        $this->actingAs(Usuario::where('correo', 'secretaria@demo.test')->first())
            ->post("/area/expedientes/{$exp->id}/confirmar");
        $this->post("/area/expedientes/{$exp->id}/responder", [
            'contenido' => 'Se emite la constancia',
            'documento' => UploadedFile::fake()->createWithContent('respuesta.pdf', '%PDF-1.4 respuesta'),
        ])->assertSessionHasNoErrors();

        return $exp->fresh();
    }

    private function assertEsPdf($respuesta, string $nombre): void
    {
        $respuesta->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString($nombre, $respuesta->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF', $respuesta->getContent());
    }

    public function test_el_administrado_descarga_su_constancia_en_pdf(): void
    {
        $exp = $this->presentarPorFormulario();

        $this->get("/consulta/{$exp->numero}/constancia")->assertSee('Descargar constancia (PDF)');
        $this->assertEsPdf($this->get("/consulta/{$exp->numero}/constancia/pdf"), 'Constancia_'.$exp->numero.'.pdf');
    }

    public function test_sin_consultar_con_dni_no_descarga_el_pdf(): void
    {
        $exp = $this->presentarPorFormulario();
        $this->flushSession();

        $this->get("/consulta/{$exp->numero}/constancia/pdf")->assertRedirect('/consulta');
    }

    public function test_el_pdf_lleva_codigo_y_qr(): void
    {
        $exp = $this->presentarPorFormulario();

        $html = view('pdf.constancia', [
            'expediente' => $exp->load('administrado', 'tipoDocumento', 'areaDestino', 'documentos'),
            'titulo' => 'Constancia de presentación',
            'documentos' => $exp->documentos,
        ])->render();
        $this->assertStringContainsString($exp->codigo_verificacion, $html);
        $this->assertStringContainsString('data:image/svg+xml;base64,', $html);
        $this->assertStringContainsString('SHA-256: '.$exp->documentos->first()->hash_sha256, $html);
    }

    public function test_respuesta_en_pdf_para_el_area_y_el_administrado(): void
    {
        $exp = $this->expedienteRespondido();
        $respuesta = $exp->respuestas()->firstOrFail();

        $this->get("/expedientes/{$exp->id}/constancia")->assertOk();
        $this->assertEsPdf($this->get("/respuestas/{$respuesta->id}/pdf"), 'Respuesta_'.$exp->numero);

        $this->consultarComoAdministrado($exp)->assertSee('Descargar respuesta en PDF');
        $this->assertEsPdf($this->get("/consulta/{$exp->numero}/respuestas/{$respuesta->id}/pdf"), 'Respuesta_'.$exp->numero);
    }

    public function test_otra_area_no_descarga_la_respuesta(): void
    {
        $exp = $this->expedienteRespondido();
        $respuesta = $exp->respuestas()->firstOrFail();
        $otra = Usuario::whereHas('rol', fn ($q) => $q->where('nombre', Rol::AREA))
            ->where('area_id', '!=', Area::where('sigla', 'SA')->value('id'))->firstOrFail();

        $this->actingAs($otra)->get("/respuestas/{$respuesta->id}/pdf")->assertForbidden();
    }
}
