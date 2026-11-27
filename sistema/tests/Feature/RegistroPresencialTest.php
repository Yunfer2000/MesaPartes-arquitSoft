<?php

namespace Tests\Feature;

use App\Models\Administrado;
use App\Models\Area;
use App\Models\Expediente;
use App\Models\Movimiento;
use App\Models\Notificacion;
use App\Models\TipoDocumento;
use App\Models\Usuario;
use App\Notifications\AvisoExpediente;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\PresentaTramite;
use Tests\TestCase;

/**
 * CU17 Registro presencial con los mismos datos del solicitante y la misma carga de archivo
 * que el formulario público (CU05), conservando lo propio del presencial.
 */
class RegistroPresencialTest extends TestCase
{
    use PresentaTramite;
    use RefreshDatabase;

    protected $seed = true;
    protected $seeder = DatabaseSeeder::class;

    private Usuario $mesa;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        Notification::fake();
        $this->mesa = Usuario::where('correo', 'mesadepartes@demo.test')->first();
    }

    private function registrar(array $datos)
    {
        return $this->actingAs($this->mesa)->post('/mesa-de-partes/presencial', $datos);
    }

    /** 1, 11, 14 y 15: persona natural, folios y N.° de documento, estado Recibido y operador responsable. */
    public function test_registra_persona_natural(): void
    {
        // Aunque llegue un tipo de solicitante, ya no se pide: el nuevo queda como "Ciudadano"
        $this->registrar($this->datosPresencial(['tipo_administrado' => 'Egresado']))
            ->assertRedirect()->assertSessionHasNoErrors();

        $exp = Expediente::with('administrado')->firstOrFail();
        $this->assertSame('Presencial', $exp->modalidad);
        $this->assertSame(Expediente::RECIBIDO, $exp->estado);
        $this->assertNotNull($exp->fecha_recepcion);
        $this->assertSame(3, $exp->folios);
        $this->assertSame('Oficio N.° 012-2026', $exp->nro_documento);
        $this->assertSame('Solicita información de admisión', $exp->asunto);
        $this->assertSame(Area::where('sigla', 'DG')->value('id'), $exp->area_destino_id);
        $this->assertFalse($exp->tieneRepresentante());

        $this->assertSame('DNI', $exp->administrado->tipo_doc_identidad);
        $this->assertSame('28301456', $exp->administrado->numero_documento);
        $this->assertSame('966555444', $exp->administrado->telefono);
        $this->assertSame('rosa.huamani@demo.test', $exp->administrado->correo);
        $this->assertSame('Ciudadano', $exp->administrado->tipo_administrado);
        $this->assertNull($exp->administrado->usuario_id); // sin cuenta de acceso

        // Trazabilidad: el movimiento guarda al operador que lo registró, en Mesa de Partes
        $movimiento = Movimiento::where('expediente_id', $exp->id)->sole();
        $this->assertSame('recibido', $movimiento->accion);
        $this->assertSame($this->mesa->id, $movimiento->usuario_id);
        $this->assertSame(Area::mesaDePartes()->id, $movimiento->area_destino_id);
    }

    /** 2: persona jurídica: la entidad va en administrados y el representante en el expediente. */
    public function test_registra_persona_juridica_con_su_representante(): void
    {
        $this->registrar($this->datosPresencialJuridica())->assertRedirect()->assertSessionHasNoErrors();

        $exp = Expediente::with('administrado')->firstOrFail();
        $this->assertSame('RUC', $exp->administrado->tipo_doc_identidad);
        $this->assertSame('20123456789', $exp->administrado->numero_documento);
        $this->assertSame('Constructora Los Andes S.A.C.', $exp->administrado->razon_social);
        $this->assertNull($exp->administrado->nombres);
        $this->assertSame('45678912', $exp->representante_dni);
        $this->assertSame('Carlos Alberto Gutiérrez Palomino', $exp->representante_nombre);
        $this->assertSame('987654321', $exp->representante_celular);
        $this->assertSame('carlos.gutierrez@losandes.test', $exp->representante_correo);
        $this->assertNull(Administrado::where('numero_documento', '45678912')->first()); // el representante no es un administrado

        $this->get("/expedientes/{$exp->id}/constancia")->assertOk()->assertSee('Constructora Los Andes S.A.C.');
    }

    /** 3: DNI, carné de extranjería, pasaporte y RUC con las reglas del formulario público. */
    public function test_valida_formato_de_cada_documento(): void
    {
        $this->registrar($this->datosPresencial(['numero_documento' => '2830145']))
            ->assertSessionHasErrors(['numero_documento' => 'El DNI debe tener exactamente 8 dígitos.']);
        $this->registrar($this->datosPresencial(['tipo_doc_identidad' => 'Carné de extranjería', 'numero_documento' => 'AB12']))
            ->assertSessionHasErrors(['numero_documento' => 'El carné de extranjería debe tener de 6 a 12 letras o números.']);
        $this->registrar($this->datosPresencial(['tipo_doc_identidad' => 'Pasaporte', 'numero_documento' => 'P-123']))
            ->assertSessionHasErrors(['numero_documento' => 'El pasaporte debe tener de 6 a 12 letras o números.']);
        $this->registrar($this->datosPresencialJuridica(['ruc' => '30123456789']))
            ->assertSessionHasErrors(['ruc' => 'El RUC debe tener 11 dígitos y empezar con 10, 15, 17 o 20.']);
        $this->registrar($this->datosPresencialJuridica(['numero_documento' => 'AB123456']))
            ->assertSessionHasErrors(['numero_documento' => 'El DNI del representante debe tener exactamente 8 dígitos.']);
        // El RUC no es un documento de persona natural: se registra como persona jurídica
        $this->registrar($this->datosPresencial(['tipo_doc_identidad' => 'RUC', 'numero_documento' => '20123456789']))
            ->assertSessionHasErrors('tipo_doc_identidad');
        $this->assertSame(0, Expediente::count());

        // Válidos: carné y pasaporte, con un solo apellido
        $this->registrar($this->datosPresencial(['tipo_doc_identidad' => 'Carné de extranjería', 'numero_documento' => 'CE001234', 'apellido_materno' => null]))
            ->assertSessionHasNoErrors();
        $this->registrar($this->datosPresencial(['tipo_doc_identidad' => 'Pasaporte', 'numero_documento' => 'PA1234567', 'apellido_materno' => null]))
            ->assertSessionHasNoErrors();
        $this->assertSame('Pasaporte', Administrado::where('numero_documento', 'PA1234567')->value('tipo_doc_identidad'));
    }

    /** Pasaporte: los números antiguos guardados sin validar el formato se siguen aceptando. */
    public function test_pasaporte_historico_con_otro_formato_se_acepta(): void
    {
        Administrado::create(['tipo_doc_identidad' => 'Pasaporte', 'numero_documento' => 'X-99.123/2020',
            'nombres' => 'John', 'apellido_paterno' => 'Smith', 'tipo_administrado' => 'Ciudadano']);

        $this->registrar($this->datosPresencial(['tipo_doc_identidad' => 'Pasaporte', 'numero_documento' => 'X-99.123/2020', 'apellido_materno' => null]))
            ->assertSessionHasNoErrors();
        $this->assertSame(1, Administrado::where('numero_documento', 'X-99.123/2020')->sole()->expedientes()->count());

        // Un número nuevo con ese formato no se acepta
        $this->registrar($this->datosPresencial(['tipo_doc_identidad' => 'Pasaporte', 'numero_documento' => 'Y-11.222/2021']))
            ->assertSessionHasErrors('numero_documento');
    }

    /** 4 y 5: celular y correo obligatorios y con formato. */
    public function test_celular_y_correo_obligatorios_y_con_formato(): void
    {
        $this->registrar($this->datosPresencial(['celular' => null, 'correo' => null]))->assertSessionHasErrors(['celular', 'correo']);
        $this->registrar($this->datosPresencial(['celular' => '866555444']))
            ->assertSessionHasErrors(['celular' => 'El celular debe tener 9 dígitos y empezar con 9.']);
        $this->registrar($this->datosPresencial(['celular' => '96655544']))->assertSessionHasErrors('celular');
        $this->registrar($this->datosPresencial(['correo' => 'rosa@demo']))
            ->assertSessionHasErrors(['correo' => 'Escribe un correo completo, por ejemplo nombre@gmail.com.']);
        $this->registrar($this->datosPresencialJuridica(['correo' => 'sin-arroba']))->assertSessionHasErrors('correo');

        $this->assertSame(0, Expediente::count());
        $this->assertSame(0, Administrado::where('numero_documento', '28301456')->count());
    }

    /** 6 y 10: PDF válido, guardado en el disco privado con su huella SHA-256. */
    public function test_pdf_valido_se_guarda_en_privado_con_su_huella(): void
    {
        $contenido = '%PDF-1.4 solicitud escaneada';
        $this->registrar($this->datosPresencial(['archivo' => UploadedFile::fake()->createWithContent('solicitud.pdf', $contenido)]))
            ->assertSessionHasNoErrors();

        $exp = Expediente::firstOrFail();
        $doc = $exp->documentos()->sole();
        $this->assertTrue($doc->es_principal);
        $this->assertSame('solicitud.pdf', $doc->nombre_archivo);
        $this->assertSame(hash('sha256', $contenido), $doc->hash_sha256);
        $this->assertStringStartsWith("expedientes/{$exp->fecha_presentacion->year}/{$exp->numero}/", $doc->ruta);
        Storage::disk('local')->assertExists($doc->ruta);
        $this->assertSame([], Storage::disk('public')->allFiles()); // nada en la carpeta pública

        // Se descarga solo con sesión del personal
        $this->get("/documentos/{$doc->id}")->assertOk();
        auth()->logout();
        $this->get("/documentos/{$doc->id}")->assertRedirect('/ingresar');
    }

    /** 7: ZIP válido. */
    public function test_zip_valido(): void
    {
        $this->registrar($this->datosPresencial(['archivo' => UploadedFile::fake()->create('anexos.zip', 300, 'application/zip')]))
            ->assertSessionHasNoErrors();

        $this->assertSame('anexos.zip', Expediente::firstOrFail()->documentos()->sole()->nombre_archivo);
    }

    /** 8: sin archivo, queda registrado solo el documento físico. */
    public function test_sin_archivo_se_registra(): void
    {
        $this->registrar($this->datosPresencial())->assertSessionHasNoErrors();

        $this->assertSame(0, Expediente::firstOrFail()->documentos()->count());
    }

    /** 9 y 20: formatos no permitidos y archivos de más de 10 MB; sin expediente ni aviso. */
    public function test_rechaza_archivos_invalidos_o_mayores_de_10_mb(): void
    {
        $this->registrar($this->datosPresencial(['archivo' => UploadedFile::fake()->image('foto.jpg')]))
            ->assertSessionHasErrors(['archivo' => 'El archivo debe ser PDF o ZIP.']);
        $this->registrar($this->datosPresencial(['archivo' => UploadedFile::fake()->create('notas.docx', 50, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')]))
            ->assertSessionHasErrors('archivo');
        $this->registrar($this->datosPresencial(['archivo' => UploadedFile::fake()->create('grande.pdf', 10241, 'application/pdf')]))
            ->assertSessionHasErrors(['archivo' => 'El archivo no debe pesar más de 10 MB.']);

        $this->assertSame(0, Expediente::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertSame(0, Notificacion::count());
        Notification::assertNothingSent();

        // Justo 10 MB sí se acepta
        $this->registrar($this->datosPresencial(['archivo' => UploadedFile::fake()->create('limite.pdf', 10240, 'application/pdf')]))
            ->assertSessionHasNoErrors();
    }

    /** Mejora: el N.° de expediente que Mesa anotó a mano se guarda en mayúsculas y no se repite. */
    public function test_numero_de_expediente_a_mano_se_guarda_y_no_se_repite(): void
    {
        $this->actingAs($this->mesa)->get('/mesa-de-partes/presencial')->assertOk()
            ->assertSee('name="codigo_propio"', false)->assertSee('Ingrese un número de expediente que puso al documento')
            ->assertSee('Ejemplo: EXP-2026-000005');

        $this->registrar($this->datosPresencial(['codigo_propio' => 'mp-0012']))->assertSessionHasNoErrors();
        $this->assertSame('MP-0012', Expediente::firstOrFail()->codigo_propio);

        // No se repite, no lleva espacios y no puede ser un N.° que ya dio el sistema
        $this->registrar($this->datosPresencial(['codigo_propio' => 'MP-0012', 'asunto' => 'Otro']))->assertSessionHasErrors('codigo_propio');
        $this->registrar($this->datosPresencial(['codigo_propio' => 'MP 9', 'asunto' => 'Otro']))->assertSessionHasErrors('codigo_propio');
        $this->registrar($this->datosPresencial(['codigo_propio' => Expediente::firstOrFail()->numero, 'asunto' => 'Otro']))->assertSessionHasErrors('codigo_propio');

        // Es opcional
        $this->registrar($this->datosPresencial(['asunto' => 'Sin número a mano']))->assertSessionHasNoErrors();
        $this->assertSame(2, Expediente::count());
    }

    /** 13: los expedientes presenciales antiguos conservan su código del sello en el cargo y en la bandeja. */
    public function test_codigo_del_sello_historico_se_sigue_mostrando_y_buscando(): void
    {
        $this->registrar($this->datosPresencial())->assertSessionHasNoErrors();
        $exp = Expediente::firstOrFail();
        $exp->update(['codigo_propio' => '001234']);

        $this->get("/expedientes/{$exp->id}/constancia")->assertSee('Código del sello')->assertSee('001234');
        $this->get("/expedientes/{$exp->id}/constancia/pdf")->assertOk();
    }

    /** Tipo documental activo y área activa distinta de Mesa de Partes. */
    public function test_tipo_documental_y_area_deben_estar_activos(): void
    {
        $tipoInactivo = TipoDocumento::create(['nombre' => 'Memorando antiguo', 'activo' => false]);
        $areaInactiva = Area::create(['nombre' => 'Área temporal', 'sigla' => 'TMP', 'activa' => false]);

        $this->registrar($this->datosPresencial(['tipo_documento_id' => $tipoInactivo->id]))
            ->assertSessionHasErrors(['tipo_documento_id' => 'Elige un tipo de documento activo.']);
        $this->registrar($this->datosPresencial(['area_destino_id' => $areaInactiva->id]))
            ->assertSessionHasErrors(['area_destino_id' => 'Elige un área activa distinta de Mesa de Partes.']);
        $this->registrar($this->datosPresencial(['area_destino_id' => Area::mesaDePartes()->id]))->assertSessionHasErrors('area_destino_id');
        $this->registrar($this->datosPresencial(['area_destino_id' => 99999]))->assertSessionHasErrors('area_destino_id');

        $this->assertSame(0, Expediente::count());
    }

    /** Un solicitante ya registrado conserva sus datos: el formulario no los sobrescribe. */
    public function test_solicitante_registrado_conserva_sus_datos(): void
    {
        Administrado::create(['tipo_doc_identidad' => 'DNI', 'numero_documento' => '28301456', 'nombres' => 'Rosa',
            'apellido_paterno' => 'Huamaní', 'apellido_materno' => 'Cárdenas', 'tipo_administrado' => 'Docente',
            'telefono' => '955111222', 'correo' => 'rosa.original@demo.test']);

        // El formulario muestra sus datos y no deja cambiarlos
        $this->actingAs($this->mesa)->get('/mesa-de-partes/presencial?documento=28301456')->assertOk()
            ->assertSee('Rosa Huamaní Cárdenas · Docente')->assertSee('value="rosa.original@demo.test" required', false);

        $this->registrar($this->datosPresencial(['nombres' => 'Otra', 'correo' => 'otro@demo.test', 'celular' => '944000111']))
            ->assertSessionHasNoErrors();

        $rosa = Administrado::where('numero_documento', '28301456')->sole();
        $this->assertSame('Rosa', $rosa->nombres);
        $this->assertSame('rosa.original@demo.test', $rosa->correo);
        $this->assertSame('955111222', $rosa->telefono);
        $this->assertSame('Docente', $rosa->tipo_administrado);
        // El aviso va al correo registrado, no al escrito
        Notification::assertSentOnDemand(AvisoExpediente::class, fn ($n, $c, AnonymousNotifiable $a) => $a->routes['mail'] === 'rosa.original@demo.test');
    }

    /** Búsqueda del personal: datos completos para Mesa de Partes; la pública sigue ocultando el contacto. */
    public function test_busqueda_del_personal_y_busqueda_publica(): void
    {
        Administrado::create(['tipo_doc_identidad' => 'DNI', 'numero_documento' => '28301456', 'nombres' => 'Rosa',
            'apellido_paterno' => 'Huamaní', 'tipo_administrado' => 'Ciudadano', 'telefono' => '955111222', 'correo' => 'rosa@demo.test']);

        $this->actingAs($this->mesa)->getJson('/mesa-de-partes/presencial/solicitante?documento=28301456')->assertOk()
            ->assertJson(['encontrado' => true, 'nombres' => 'Rosa', 'celular' => '955111222', 'correo' => 'rosa@demo.test']);
        $this->getJson('/mesa-de-partes/presencial/solicitante?documento=00000000')->assertOk()->assertExactJson(['encontrado' => false]);

        auth()->logout();
        $this->getJson('/tramite/remitente?documento=28301456')->assertOk()
            ->assertJson(['correo' => 'ro***@demo.test'])->assertJsonMissing(['celular' => '955111222']);
    }

    /** 16: solo Mesa de Partes registra o busca; la autorización está en el servidor. */
    public function test_solo_mesa_de_partes_registra_y_busca(): void
    {
        $datos = $this->datosPresencial();

        $this->get('/mesa-de-partes/presencial')->assertRedirect('/ingresar');
        $this->post('/mesa-de-partes/presencial', $datos)->assertRedirect('/ingresar');
        $this->getJson('/mesa-de-partes/presencial/solicitante?documento=28301456')->assertUnauthorized();

        foreach (['secretaria@demo.test', 'direccion@demo.test', 'admin@demo.test'] as $correo) {
            $this->actingAs(Usuario::where('correo', $correo)->first());
            $this->get('/mesa-de-partes/presencial')->assertForbidden();
            $this->post('/mesa-de-partes/presencial', $datos)->assertForbidden();
            $this->getJson('/mesa-de-partes/presencial/solicitante?documento=28301456')->assertForbidden();
        }

        $this->assertSame(0, Expediente::count());
        Notification::assertNothingSent();
    }

    /** 18 y 19: aviso de registro presencial al solicitante (natural) o al representante (jurídica). */
    public function test_avisa_el_registro_al_destinatario_correcto(): void
    {
        $this->registrar($this->datosPresencial())
            ->assertSessionHas('exito', fn ($m) => str_contains($m, 'Se generó el aviso de registro a ro***@demo.test'));
        $natural = Expediente::where('representante_dni', null)->firstOrFail();

        $this->registrar($this->datosPresencialJuridica())->assertSessionHasNoErrors();
        $juridica = Expediente::whereNotNull('representante_dni')->firstOrFail();

        foreach ([[$natural, 'rosa.huamani@demo.test', 'tu documento de identidad'], [$juridica, 'carlos.gutierrez@losandes.test', 'el RUC de la entidad']] as [$exp, $correo, $consulta]) {
            Notification::assertSentOnDemandTimes(AvisoExpediente::class, 2);
            Notification::assertSentOnDemand(AvisoExpediente::class, function (AvisoExpediente $n, array $canales, AnonymousNotifiable $a) use ($exp, $correo, $consulta) {
                if ($a->routes['mail'] !== $correo) {
                    return false;
                }
                $correoArmado = $n->toMail($a);
                $texto = implode(' ', array_merge($correoArmado->introLines, $correoArmado->outroLines));
                $this->assertStringContainsString($exp->numero, $correoArmado->subject);
                $this->assertStringContainsString('registrado presencialmente', $texto);
                $this->assertStringContainsString($consulta, $texto);
                $this->assertSame(route('externo.consulta'), $correoArmado->actionUrl);
                $this->assertSame([], $correoArmado->attachments); // sin adjuntos
                // Sin datos personales sensibles: ni el documento ni el celular
                $this->assertStringNotContainsString('28301456', $texto);
                $this->assertStringNotContainsString('45678912', $texto);
                $this->assertStringNotContainsString('966555444', $texto);

                return true;
            });

            $aviso = Notificacion::where('expediente_id', $exp->id)->sole();
            $this->assertSame('registro presencial', $aviso->evento);
            $this->assertTrue($aviso->enviada);
            $this->assertSame($exp->administrado_id, $aviso->administrado_id);
        }
    }

    /** Si el correo falla, el expediente se registra igual, el aviso queda como no enviado y el fallo en el log. */
    public function test_fallo_del_correo_no_impide_el_registro(): void
    {
        // Canal de correo real con un transportador que no existe: el envío lanza una excepción (no sale ningún correo)
        Notification::swap(new ChannelManager($this->app));
        config(['mail.default' => 'transporte_caido']);
        Log::spy();

        $this->registrar($this->datosPresencial())->assertRedirect()->assertSessionHasNoErrors()
            ->assertSessionHas('exito', fn ($m) => str_contains($m, 'No se pudo enviar el aviso por correo') && ! str_contains($m, 'Se generó'));

        $exp = Expediente::firstOrFail();
        $this->assertSame(Expediente::RECIBIDO, $exp->estado);
        $this->assertFalse(Notificacion::where('expediente_id', $exp->id)->sole()->enviada);
        Log::shouldHaveReceived('warning')->once()->withArgs(fn ($m) => str_contains($m, 'No se pudo enviar la notificación') && str_contains($m, 'transporte_caido'));
    }

    /** 17: lo virtual y lo presencial siguen diferenciados; el presencial no tiene captcha ni límite diario. */
    public function test_virtual_y_presencial_siguen_diferenciados(): void
    {
        $virtual = $this->presentarPorFormulario();
        $this->assertSame('Virtual', $virtual->modalidad);
        $this->assertSame(Expediente::PRESENTADO, $virtual->estado);
        $this->assertSame(1, Notificacion::where('expediente_id', $virtual->id)->where('evento', 'presentación')->count());

        // El público no puede usar pasaporte; el personal sí
        $this->withSession(['captcha' => '7'])->post('/tramite', [
            'tipo_persona' => 'natural', 'tipo_doc_identidad' => 'Pasaporte', 'numero_documento' => 'PA1234567',
            'nombres' => 'John', 'apellido_paterno' => 'Smith', 'celular' => '966123456', 'correo' => 'john@demo.test',
            'asunto' => 'Trámite', 'archivo' => UploadedFile::fake()->create('s.pdf', 10, 'application/pdf'),
            'acepta_datos' => '1', 'captcha' => '7',
        ])->assertSessionHasErrors('tipo_doc_identidad');

        // Sin captcha ni declaración, y más de 5 registros en el día para el mismo documento
        for ($i = 1; $i <= 6; $i++) {
            $this->registrar($this->datosPresencial(['asunto' => "Trámite presencial {$i}"]))->assertSessionHasNoErrors();
        }
        $this->assertSame(6, Expediente::where('modalidad', 'Presencial')->count());
        $this->assertSame(1, Expediente::where('modalidad', 'Virtual')->count());
    }

    /** Compatibilidad con el flujo posterior: el presencial se deriva y el área lo recibe. */
    public function test_presencial_sigue_el_flujo_de_derivacion(): void
    {
        $this->registrar($this->datosPresencial())->assertSessionHasNoErrors();
        $exp = Expediente::firstOrFail();

        $this->post("/expedientes/{$exp->id}/derivar", ['area_id' => Area::where('sigla', 'DG')->value('id')])->assertSessionHasNoErrors();

        $this->assertSame(Expediente::DERIVADO, $exp->fresh()->estado);
        $this->assertSame('DG', $exp->fresh()->areaActual()->sigla);
        $this->actingAs(Usuario::where('correo', 'direccion@demo.test')->first())->get('/area')->assertSee($exp->numero);
        $this->assertSame(1, Notificacion::where('expediente_id', $exp->id)->where('evento', 'derivación')->count());
    }

    /** Formulario: mismos componentes que el público (tipo de persona, zona de archivo) y token CSRF. */
    public function test_formulario_usa_los_componentes_del_publico(): void
    {
        $this->actingAs($this->mesa)->get('/mesa-de-partes/presencial')->assertOk()
            ->assertSee('name="_token"', false)
            ->assertSee('type="radio" name="tipo_persona" value="juridica"', false)
            ->assertSee('id="zona-archivo"', false)
            ->assertSee('accept=".pdf,.zip,application/pdf,application/zip"', false)
            ->assertDontSee('name="archivo" accept=".pdf,.zip,application/pdf,application/zip" required', false)
            ->assertSee('Pasaporte</option>', false)
            ->assertSee('js/formulario.js', false)
            ->assertDontSee('Tipo de solicitante')->assertDontSee('name="tipo_administrado"', false)
            ->assertDontSee('name="captcha"', false)->assertDontSee('name="sitio_web"', false);
    }
}
