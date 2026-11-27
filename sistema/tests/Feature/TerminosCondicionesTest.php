<?php

namespace Tests\Feature;

use App\Models\Expediente;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\PresentaTramite;
use Tests\TestCase;

/** Issue #20: términos y condiciones en ventana emergente al abrir el formulario (CU05). */
class TerminosCondicionesTest extends TestCase
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

    /** CA-CU05-18 */
    public function test_terminos_y_condiciones_al_abrir_el_formulario(): void
    {
        // Al abrir el formulario, la ventana de términos se abre sola
        $this->get('/tramite/nuevo')->assertOk()
            ->assertSee('id="terminos"', false)
            ->assertSee('data-al-inicio', false)
            ->assertSee('Términos y condiciones')
            ->assertSee('He leído y acepto')
            ->assertSee('Aceptar y continuar')
            ->assertSee('No acepto')
            // La casilla pertenece al formulario aunque esté dentro de la ventana
            ->assertSee('form="formulario"', false);

        // Sin aceptar no se crea el expediente
        $this->withSession(['captcha' => '7'])->post('/tramite', [
            'tipo_persona' => 'natural', 'tipo_doc_identidad' => 'DNI', 'numero_documento' => '70451236',
            'nombres' => 'José Luis', 'apellido_paterno' => 'Quispe', 'apellido_materno' => 'Huamán',
            'celular' => '966123456', 'correo' => 'jose@gmail.com', 'asunto' => 'Constancia',
            'archivo' => UploadedFile::fake()->create('s.pdf', 100, 'application/pdf'), 'captcha' => '7',
        ])->assertSessionHasErrors(['acepta_datos' => 'Debes aceptar los términos y condiciones.']);
        $this->assertSame(0, Expediente::count());

        // Si el formulario vuelve con errores y ya había aceptado, la ventana no se abre otra vez
        $this->withSession(['_old_input' => ['acepta_datos' => '1']])->get('/tramite/nuevo')->assertOk()
            ->assertSee('id="terminos"', false)
            ->assertDontSee('data-al-inicio', false)
            ->assertSee('Aceptaste los términos y condiciones');

        // Aceptando, el trámite se presenta como siempre
        $this->presentarPorFormulario();
        $this->assertSame(1, Expediente::count());
    }
}
