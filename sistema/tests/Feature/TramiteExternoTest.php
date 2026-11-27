<?php

namespace Tests\Feature;

use App\Models\Administrado;
use App\Models\Area;
use App\Models\Expediente;
use App\Models\TipoDocumento;
use App\Models\Usuario;
use App\Notifications\AvisoExpediente;
use App\Services\FlujoExpediente;
use App\Services\Notificador;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\PresentaTramite;
use Tests\TestCase;

/** Mesa de Partes sin cuenta: CU05 Presentar, CU06 Subsanar y CU07 Consultar con N.° de expediente y DNI. */
class TramiteExternoTest extends TestCase
{
    use PresentaTramite;
    use RefreshDatabase;

    protected $seed = true;
    protected $seeder = DatabaseSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function enviar(array $cambios = [])
    {
        return $this->withSession(['captcha' => '7'])->post('/tramite', $cambios + [
            'tipo_doc_identidad' => 'DNI',
            'numero_documento' => '41234567',
            'nombres' => 'Rosa',
            'apellido_paterno' => 'Pariona', 'apellido_materno' => 'Quispe',
            'tipo_persona' => 'natural',
            'celular' => '966111222',
            'correo' => 'rosa@example.com',
            'asunto' => 'Solicito información de admisión',
            'archivo' => UploadedFile::fake()->create('solicitud.pdf', 200, 'application/pdf'),
            'acepta_datos' => '1',
            'captcha' => '7',
        ]);
    }

    /**
     * Expediente virtual con código propio. El formulario ya no lo pide, pero los presentados antes
     * lo conservan en la base y la consulta (CU07 B1) los sigue encontrando por ese código.
     */
    private function enviarConCodigo(string $codigo, array $cambios = []): Expediente
    {
        $this->enviar($cambios)->assertSessionHasNoErrors();
        $expediente = Expediente::latest('id')->firstOrFail();
        $expediente->update(['codigo_propio' => $codigo]);

        return $expediente;
    }

    /** Persona jurídica: RUC y razón social de la entidad, y los datos de su representante (su DNI va en el N.° de documento). */
    private function enviarJuridica(array $cambios = [])
    {
        return $this->withSession(['captcha' => '7'])->post('/tramite', $cambios + [
            'tipo_persona' => 'juridica',
            'ruc' => '20123456789',
            'razon_social' => 'Constructora Wari S.A.C.',
            'numero_documento' => '45678912',
            'apellido_paterno' => 'Gutiérrez', 'apellido_materno' => 'Palomino',
            'nombres' => 'Carlos Alberto',
            'celular' => '987654321',
            'correo' => 'carlos@wari.pe',
            'asunto' => 'Solicito convenio de prácticas',
            'archivo' => UploadedFile::fake()->create('carta.pdf', 200, 'application/pdf'),
            'acepta_datos' => '1',
            'captcha' => '7',
        ]);
    }

    public function test_presenta_sin_cuenta_y_crea_al_remitente(): void
    {
        $this->enviar()->assertRedirect();

        $exp = Expediente::firstOrFail();
        $this->assertSame(Expediente::PRESENTADO, $exp->estado);
        $this->assertNull($exp->tipo_documento_id);
        $this->assertNull($exp->folios);
        $this->assertSame(Area::MESA_DE_PARTES, $exp->areaDestino->sigla);
        $this->assertSame(1, $exp->documentos()->count());
        $this->assertNull($exp->movimientos->first()->usuario_id);
        $this->assertSame('Pariona', $exp->administrado->apellido_paterno);
        $this->assertSame('Quispe', $exp->administrado->apellido_materno);
        $this->assertSame('Pariona Quispe', $exp->administrado->apellidos);
        $this->assertNull($exp->administrado->usuario_id);
        $this->assertGuest();

        // La misma sesión ve su constancia y el seguimiento
        $this->get("/consulta/{$exp->numero}/constancia")->assertOk()->assertSee($exp->numero);
        $this->get("/consulta/{$exp->numero}")->assertOk()->assertSee('Recorrido de tu trámite');
    }

    public function test_valida_captcha_documento_celular_y_declaracion(): void
    {
        $this->enviar(['captcha' => '8'])->assertSessionHasErrors('captcha');
        $this->enviar(['numero_documento' => '1234'])->assertSessionHasErrors('numero_documento');
        // El RUC ya no es un documento de persona natural: se elige "Jurídica"
        $this->enviar(['tipo_doc_identidad' => 'RUC', 'numero_documento' => '20123456789'])->assertSessionHasErrors('tipo_doc_identidad');
        $this->enviar(['celular' => '12345'])->assertSessionHasErrors('celular');
        $this->enviar(['acepta_datos' => null])->assertSessionHasErrors('acepta_datos');
        $this->enviar(['sitio_web' => 'spam'])->assertStatus(422);
        $this->assertSame(0, Expediente::count());
    }

    public function test_limite_diario_por_documento(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->enviar()->assertSessionHasNoErrors();
        }
        $this->enviar()->assertSessionHasErrors('numero_documento');
        $this->assertSame(5, Expediente::count());
    }

    public function test_autocompletado_oculta_el_contacto_y_un_tercero_no_lo_cambia(): void
    {
        $this->getJson('/tramite/remitente?documento=99999999')->assertExactJson(['encontrado' => false]);

        $this->getJson('/tramite/remitente?documento=70451236')->assertJson([
            'encontrado' => true, 'nombres' => 'José Luis', 'correo' => 'es***@demo.test', 'celular' => '96*****56',
        ])->assertJsonMissing(['correo' => 'estudiante@demo.test']);

        $this->enviar(['numero_documento' => '70451236', 'nombres' => 'Otro', 'correo' => 'intruso@example.com'])->assertSessionHasNoErrors();
        $alumno = Administrado::where('numero_documento', '70451236')->first();
        $this->assertSame('José Luis', $alumno->nombres);
        $this->assertSame('estudiante@demo.test', $alumno->correo);
        $this->assertSame($alumno->id, Expediente::first()->administrado_id);
    }

    public function test_consulta_exige_numero_y_documento_correctos(): void
    {
        $this->enviar();
        $exp = Expediente::first();
        $this->flushSession();

        $this->get("/consulta/{$exp->numero}")->assertRedirect('/consulta');
        $this->post('/consulta', ['numero' => $exp->numero, 'numero_documento' => '11111111'])->assertSessionHasErrors('numero');
        $this->post('/consulta', ['numero' => strtolower($exp->numero), 'numero_documento' => '41234567'])
            ->assertRedirect("/consulta/{$exp->numero}");
        $this->get("/consulta/{$exp->numero}")->assertOk()->assertSee($exp->asunto);

        $documento = $exp->documentos->first();
        $this->get("/consulta/{$exp->numero}/documentos/{$documento->id}")->assertOk();
    }

    public function test_subsana_observacion_sin_cuenta(): void
    {
        $this->enviar();
        $exp = Expediente::first();
        app(FlujoExpediente::class)->observar($exp, Usuario::where('correo', 'mesadepartes@demo.test')->first(), 'Falta el voucher', null);

        $this->get("/consulta/{$exp->numero}")->assertSee('Enviar subsanación');
        $this->post("/consulta/{$exp->numero}/subsanar", [
            'descripcion' => 'Adjunto el voucher',
            'archivos' => [UploadedFile::fake()->image('voucher.jpg')],
        ])->assertRedirect("/consulta/{$exp->numero}");

        $this->assertSame(Expediente::SUBSANADO, $exp->fresh()->estado);
        $this->assertSame('subsanado', $exp->movimientos()->reorder()->latest('id')->first()->accion);
    }

    /** CU07 A1: una burbuja numerada por cada movimiento real, con observado y subsanado cuando ocurren. */
    public function test_burbujas_siguen_los_movimientos_reales(): void
    {
        $this->enviar();
        $exp = Expediente::first();
        $flujo = app(FlujoExpediente::class);
        $mesa = Usuario::where('correo', 'mesadepartes@demo.test')->first();
        $secretaria = Usuario::where('correo', 'secretaria@demo.test')->first();

        // Recién presentado: la burbuja actual y la de respuesta pendiente; carga su propio CSS
        $this->get("/consulta/{$exp->numero}")->assertOk()
            ->assertSee('css/burbujas.css', false)
            ->assertSee('class="burbujas"', false)
            ->assertSeeInOrder(['burbuja burbuja-presentado actual', 'aria-current="step"', 'burbuja burbuja-pendiente'], false)
            ->assertSee('Paso 1 de 2, paso actual:')
            ->assertSee('Paso 2 de 2, pendiente:')
            ->assertDontSee('burbuja-observado', false);

        // Observado y aún sin subsanar: la burbuja roja es el paso actual
        $flujo->observar($exp, $mesa, 'Falta el voucher', null);
        $burbujas = $exp->fresh()->burbujas();
        $this->assertSame(['completado', 'observado', 'pendiente'], $burbujas->pluck('estado')->all());
        $this->assertTrue($burbujas[1]['actual']);

        $flujo->subsanar($exp->fresh(), null, 'Adjunto el voucher');
        $flujo->recepcionar($exp->fresh(), $mesa);
        $flujo->derivar($exp->fresh(), $mesa, Area::where('sigla', 'SA')->first(), null, 'Atender');
        $flujo->confirmarRecepcion($exp->fresh(), $secretaria);

        $burbujas = $exp->fresh()->burbujas();
        $this->assertSame(['presentado', 'observado', 'subsanado', 'recibido', 'derivado', 'confirmado', 'pendiente'],
            $burbujas->pluck('accion')->all());
        $this->assertSame(range(1, 7), $burbujas->pluck('numero')->all());
        $this->assertSame(['completado', 'observado', 'completado', 'completado', 'completado', 'actual', 'pendiente'],
            $burbujas->pluck('estado')->all());
        $this->assertNull($burbujas->last()['fecha']);

        $this->get("/consulta/{$exp->numero}")->assertOk()
            ->assertSeeInOrder(['Presentado', 'Observado', 'Subsanado', 'Recibido', 'Derivado', 'En atención', 'Respuesta pendiente'])
            ->assertSee('burbuja burbuja-confirmado actual', false)
            ->assertDontSee('Historial detallado'); // paso 2 de la interfaz: un solo recorrido
    }

    /** CU07 A1: un expediente finalizado no muestra etapa en curso, y el personal no ve las burbujas. */
    public function test_burbujas_finalizado_y_solo_en_consulta_publica(): void
    {
        $this->enviar();
        $exp = Expediente::first();
        $flujo = app(FlujoExpediente::class);
        $mesa = Usuario::where('correo', 'mesadepartes@demo.test')->first();
        $secretaria = Usuario::where('correo', 'secretaria@demo.test')->first();

        $flujo->recepcionar($exp, $mesa);
        $flujo->derivar($exp->fresh(), $mesa, Area::where('sigla', 'SA')->first(), null, null);
        $flujo->confirmarRecepcion($exp->fresh(), $secretaria);
        $flujo->responder($exp->fresh(), $secretaria, 'Atendido', UploadedFile::fake()->create('r.pdf', 50, 'application/pdf'));

        // Finalizado: todo completado, sin paso actual ni respuesta pendiente
        $burbujas = $exp->fresh()->burbujas();
        $this->assertSame('finalizado', $burbujas->last()['accion']);
        $this->assertFalse($burbujas->contains('actual', true));
        $this->assertSame(['completado'], $burbujas->pluck('estado')->unique()->values()->all());

        $this->get("/consulta/{$exp->numero}")->assertOk()
            ->assertSee('burbuja-finalizado', false)
            ->assertDontSee('burbuja-pendiente', false)
            ->assertDontSee('aria-current="step"', false);

        // Vista del personal sin cambios: ni burbujas ni su CSS
        $this->actingAs($mesa)->get("/mesa-de-partes/expedientes/{$exp->id}")->assertOk()
            ->assertDontSee('class="burbujas"', false)
            ->assertDontSee('css/burbujas.css', false);
    }

    /** CU07 A2 y paso 2 de la interfaz: un solo recorrido (las burbujas) arriba, sin historial repetido al costado. */
    public function test_recorrido_en_tarjeta_superior_sin_historial_repetido(): void
    {
        $this->enviar();
        $exp = Expediente::first();

        $html = $this->get("/consulta/{$exp->numero}")->assertOk()
            ->assertSeeInOrder(['class="card resumen-tramite', 'class="card recorrido"', 'Recorrido de tu trámite', 'class="burbujas"', 'Datos del trámite'], false)
            ->assertDontSee('historial-detallado', false)
            ->getContent();

        $this->assertSame(1, substr_count($html, 'class="burbujas"'), 'Las burbujas no deben duplicarse');
        $this->assertSame(0, substr_count($html, 'class="tl"'), 'El recorrido no debe repetirse como historial');
    }

    /** CU07 A2: la observación solo se ve mientras espera subsanación, y solo la vigente. */
    public function test_observacion_visible_solo_mientras_esta_pendiente(): void
    {
        $this->enviar();
        $exp = Expediente::first();
        $flujo = app(FlujoExpediente::class);
        $mesa = Usuario::where('correo', 'mesadepartes@demo.test')->first();

        $this->get("/consulta/{$exp->numero}")->assertDontSee('Observación de Mesa de Partes');

        $flujo->observar($exp, $mesa, 'Falta el voucher', 'Adjunte el voucher de pago');
        $this->get("/consulta/{$exp->numero}")
            ->assertSeeInOrder(['Observación de Mesa de Partes', 'Falta el voucher', 'Adjunte el voucher de pago', 'Enviar subsanación']);

        // Subsanada: desaparece la observación y el formulario
        $flujo->subsanar($exp->fresh(), null, 'Adjunto el voucher');
        $this->get("/consulta/{$exp->numero}")->assertOk()
            ->assertDontSee('Observación de Mesa de Partes')
            ->assertDontSee('Enviar subsanación');

        // Nueva observación: solo se muestra la vigente
        $flujo->observar($exp->fresh(), $mesa, 'Falta la firma', null);
        $html = $this->get("/consulta/{$exp->numero}")
            ->assertSeeInOrder(['Observación de Mesa de Partes', 'Falta la firma', 'Subsanar'])
            ->getContent();
        $this->assertSame(1, substr_count($html, 'Observación de Mesa de Partes'));
    }

    /** CU07 A2: las subsanaciones enviadas se ven en su propia sección, separadas de la observación pendiente. */
    public function test_subsanaciones_realizadas_en_su_seccion(): void
    {
        $this->enviar();
        $exp = Expediente::first();
        $flujo = app(FlujoExpediente::class);
        $mesa = Usuario::where('correo', 'mesadepartes@demo.test')->first();

        // Sin subsanaciones no aparece la sección
        $this->get("/consulta/{$exp->numero}")->assertDontSee('Subsanaciones realizadas');

        $flujo->observar($exp, $mesa, 'Falta el voucher', null);
        $this->travel(5)->minutes();
        $this->post("/consulta/{$exp->numero}/subsanar", [
            'descripcion' => 'Adjunto el voucher de pago del banco',
            'archivos' => [UploadedFile::fake()->image('voucher.jpg')],
        ])->assertRedirect("/consulta/{$exp->numero}");

        $subsanacion = $exp->observaciones()->first()->subsanacion;
        $archivo = $subsanacion->documentos->first();

        $this->get("/consulta/{$exp->numero}")->assertOk()
            ->assertDontSee('Observación de Mesa de Partes')
            ->assertSeeInOrder(['Subsanaciones realizadas', 'Subsanación enviada',
                $subsanacion->fecha->format('d/m/Y H:i'), 'Adjunto el voucher de pago del banco',
                'Atendió la observación', 'Falta el voucher', 'voucher.jpg'], false);
        $this->get(route('externo.documento', [$exp->numero, $archivo]))->assertOk();

        // Nueva observación: la pendiente va arriba y la subsanación anterior sigue listada abajo
        $flujo->observar($exp->fresh(), $mesa, 'Falta la firma', null);
        $html = $this->get("/consulta/{$exp->numero}")
            ->assertSeeInOrder(['Observación de Mesa de Partes', 'Falta la firma', 'Enviar subsanación',
                'Subsanaciones realizadas', 'Adjunto el voucher de pago del banco'])
            ->getContent();
        $this->assertSame(1, substr_count($html, 'Subsanación enviada'));
        $this->assertSame(1, substr_count($html, 'Observación de Mesa de Partes'));
    }

    /** CU07 A3: aviso destacado arriba, con días transcurridos y enlace directo a la observación. */
    public function test_aviso_de_observacion_pendiente_con_dias_y_enlaces(): void
    {
        config(['plazos.habiles' => true]);
        $this->travelTo(Carbon::parse('2026-11-09 10:00')); // lunes
        $this->enviar();
        $exp = Expediente::first();
        $mesa = Usuario::where('correo', 'mesadepartes@demo.test')->first();

        $this->get("/consulta/{$exp->numero}")
            ->assertDontSee('Tu trámite tiene una observación pendiente')
            ->assertDontSee('Subsanar ahora');

        app(FlujoExpediente::class)->observar($exp, $mesa, 'Falta el voucher', null);

        // El mismo día
        $this->get("/consulta/{$exp->numero}")
            ->assertSeeInOrder(['Tu trámite tiene una observación pendiente', '09/11/2026 (hoy)', 'Recorrido de tu trámite'])
            ->assertSee('09/11/2026 10:00 · hoy');

        // Jueves: 3 días hábiles
        $this->travelTo(Carbon::parse('2026-11-12 10:00'));
        $this->get("/consulta/{$exp->numero}")->assertOk()
            ->assertSeeInOrder([
                'class="alert aviso-observacion"', '(hace 3 días hábiles)', 'href="#observacion"', // aviso arriba
                'class="card recorrido"', 'burbuja burbuja-observado actual', 'href="#observacion">Subsanar ahora', // burbuja roja
                'id="observacion"', 'Falta el voucher', 'hace 3 días hábiles', 'id="subsanar"', 'Enviar subsanación', // destino
            ], false);

        // Lunes siguiente: el fin de semana no cuenta
        $this->travelTo(Carbon::parse('2026-11-16 10:00'));
        $this->get("/consulta/{$exp->numero}")->assertSee('(hace 5 días hábiles)');

        config(['plazos.habiles' => false]);
        $this->get("/consulta/{$exp->numero}")->assertSee('(hace 7 días)');

        // Subsanada: desaparecen el aviso y el enlace de la burbuja
        app(FlujoExpediente::class)->subsanar($exp->fresh(), null, 'Adjunto el voucher');
        $this->get("/consulta/{$exp->numero}")->assertOk()
            ->assertDontSee('Tu trámite tiene una observación pendiente')
            ->assertDontSee('Subsanar ahora')
            ->assertDontSee('id="observacion"', false);
    }

    /** CU07 A2: respuestas, documentos y constancia se conservan. */
    public function test_conserva_respuestas_documentos_y_constancia(): void
    {
        $this->enviar();
        $exp = Expediente::first();
        $flujo = app(FlujoExpediente::class);
        $mesa = Usuario::where('correo', 'mesadepartes@demo.test')->first();
        $secretaria = Usuario::where('correo', 'secretaria@demo.test')->first();

        $flujo->recepcionar($exp, $mesa);
        $flujo->derivar($exp->fresh(), $mesa, Area::where('sigla', 'SA')->first(), null, null);
        $flujo->confirmarRecepcion($exp->fresh(), $secretaria);
        $flujo->responder($exp->fresh(), $secretaria, 'Su solicitud fue atendida', UploadedFile::fake()->create('respuesta.pdf', 50, 'application/pdf'));

        $respuesta = $exp->fresh()->respuestas->first()->documentos->first();
        $this->get("/consulta/{$exp->numero}")->assertOk()
            ->assertSeeInOrder(['Documentos', 'solicitud.pdf', 'Respuesta', 'Su solicitud fue atendida', 'respuesta.pdf', 'Ver constancia de presentación'])
            ->assertSee(route('externo.documento', [$exp->numero, $respuesta]), false);
        $this->get(route('externo.documento', [$exp->numero, $respuesta]))->assertOk();
    }

    /** Mensaje genérico de CU07 3a: no revela si falló el número/código o el documento. */
    private const NO_ENCONTRADO = 'No encontramos un expediente con ese número o código y ese documento de identidad.';

    /** CU07 B1: consulta con el código propio que el ciudadano eligió, sin distinguir mayúsculas. */
    public function test_b1_consulta_por_codigo_propio_virtual(): void
    {
        $exp = $this->enviarConCodigo('ROSA2026');
        $this->flushSession();

        foreach (['ROSA2026', 'rosa2026', '  RoSa2026  '] as $escrito) {
            $this->flushSession();
            $this->post('/consulta', ['numero' => $escrito, 'numero_documento' => '41234567'])
                ->assertSessionHasNoErrors()
                ->assertRedirect("/consulta/{$exp->numero}"); // la URL usa el número, no el código
            $this->get("/consulta/{$exp->numero}")->assertOk()->assertSee($exp->asunto)->assertSee('Recorrido de tu trámite');
        }
    }

    /**
     * CU07 B1: un expediente presencial antiguo se sigue consultando con su código del sello y el DNI.
     * El registro presencial ya no escribe el código: se pone como en los expedientes históricos.
     */
    public function test_b1_consulta_por_codigo_del_sello_presencial(): void
    {
        $mesa = Usuario::where('correo', 'mesadepartes@demo.test')->first();
        $this->actingAs($mesa)->post('/mesa-de-partes/presencial', $this->datosPresencial([
            'folios' => 1, 'asunto' => 'Solicita constancia presencial',
        ]))->assertSessionHasNoErrors();
        $exp = Expediente::where('modalidad', 'Presencial')->firstOrFail();
        $exp->update(['codigo_propio' => 'MP001']);

        auth()->logout();
        $this->flushSession();

        $this->post('/consulta', ['numero' => 'mp001', 'numero_documento' => '28301456'])
            ->assertSessionHasNoErrors()
            ->assertRedirect("/consulta/{$exp->numero}");
        $this->get("/consulta/{$exp->numero}")->assertOk()->assertSee('Solicita constancia presencial');
    }

    /** CU07 B1: código correcto con el DNI de otra persona, o código inexistente: mismo mensaje y sin acceso. */
    public function test_b1_codigo_con_dni_ajeno_o_inexistente_da_mensaje_generico(): void
    {
        $this->enviarConCodigo('ROSA2026');
        $deRosa = Expediente::firstOrFail();
        // Otra persona con su propio expediente (el DNI existe, pero no es el remitente de ROSA2026)
        $this->enviarConCodigo('JOSE2026', ['numero_documento' => '70451236']);
        $this->flushSession();

        $intentos = [
            ['numero' => 'ROSA2026', 'numero_documento' => '70451236'], // código bien, DNI de otro remitente
            ['numero' => 'ROSA2026', 'numero_documento' => '11111111'], // código bien, DNI inexistente
            ['numero' => 'NOEXISTE1', 'numero_documento' => '41234567'], // DNI bien, código inexistente
            ['numero' => $deRosa->numero, 'numero_documento' => '70451236'], // número bien, DNI de otro
        ];
        foreach ($intentos as $datos) {
            $this->post('/consulta', $datos)->assertSessionHasErrors(['numero' => self::NO_ENCONTRADO])
                ->assertSessionDoesntHaveErrors('numero_documento');
        }

        // Ningún intento fallido abre el expediente de Rosa
        $this->get("/consulta/{$deRosa->numero}")->assertRedirect('/consulta');
        $this->assertNotContains($deRosa->id, session('expedientes_consultados', []));
    }

    /** CU07 B1: la consulta por N.° de expediente sigue funcionando, también en minúsculas. */
    public function test_b1_consulta_por_numero_sigue_funcionando(): void
    {
        $this->enviarConCodigo('ROSA2026');
        $exp = Expediente::firstOrFail();
        $this->flushSession();

        $this->post('/consulta', ['numero' => strtolower($exp->numero), 'numero_documento' => '41234567'])
            ->assertSessionHasNoErrors()
            ->assertRedirect("/consulta/{$exp->numero}");
        $this->get("/consulta/{$exp->numero}")->assertOk()->assertSee($exp->asunto);
    }

    /** CU07 B1: sin consultar antes con código y DNI, ni el detalle ni la constancia ni los archivos se ven. */
    public function test_b1_acceso_directo_sin_autorizacion(): void
    {
        $this->enviarConCodigo('ROSA2026');
        $exp = Expediente::firstOrFail();
        $documento = $exp->documentos->first();
        $this->flushSession();

        $this->get("/consulta/{$exp->numero}")->assertRedirect('/consulta');
        $this->get("/consulta/{$exp->numero}/constancia")->assertRedirect('/consulta');
        $this->get("/consulta/{$exp->numero}/documentos/{$documento->id}")->assertRedirect('/consulta');
        $this->post("/consulta/{$exp->numero}/subsanar", ['descripcion' => 'x'])->assertRedirect('/consulta');
        // El código propio no sirve como dirección: la URL solo acepta el número
        $this->get('/consulta/ROSA2026')->assertNotFound();
    }

    /** CU07 B2: tras consultar con el código en minúsculas y el DNI correcto, el detalle muestra el código propio. */
    public function test_b2_detalle_muestra_codigo_propio(): void
    {
        $this->enviarConCodigo('ROSA2026');
        $exp = Expediente::firstOrFail();
        $this->flushSession();

        $this->post('/consulta', ['numero' => 'rosa2026', 'numero_documento' => '41234567'])
            ->assertSessionHasNoErrors()
            ->assertRedirect("/consulta/{$exp->numero}");

        $this->get("/consulta/{$exp->numero}")->assertOk()
            ->assertSee('Tu código propio: <b>ROSA2026</b>', false)
            ->assertSeeInOrder([$exp->numero, 'Tu código propio', 'ROSA2026', $exp->asunto]);
    }

    /** CU07 B2: con un DNI incorrecto no se accede, y el código no aparece en ninguna página protegida. */
    public function test_b2_dni_incorrecto_no_expone_el_codigo_propio(): void
    {
        $this->enviarConCodigo('ROSA2026');
        $exp = Expediente::firstOrFail();
        $this->flushSession();

        $this->post('/consulta', ['numero' => 'rosa2026', 'numero_documento' => '70451236'])
            ->assertSessionHasErrors(['numero' => self::NO_ENCONTRADO]);

        $this->get("/consulta/{$exp->numero}")->assertRedirect('/consulta');
        $this->get("/consulta/{$exp->numero}/constancia")->assertRedirect('/consulta');
        $this->followingRedirects()->get("/consulta/{$exp->numero}")->assertOk()
            ->assertSee('Consultar trámite')
            ->assertDontSee('Tu código propio')
            ->assertDontSee($exp->asunto);
    }

    /** CU07 B2: sin código no se muestra la línea; en presencial se llama "Código del sello", como en la constancia. */
    public function test_b2_codigo_solo_si_existe_y_sello_en_presencial(): void
    {
        $this->enviar();
        $sinCodigo = Expediente::firstOrFail();
        $this->get("/consulta/{$sinCodigo->numero}")->assertOk()->assertDontSee('Tu código propio')->assertDontSee('class="codigo-propio"', false);

        $mesa = Usuario::where('correo', 'mesadepartes@demo.test')->first();
        $this->actingAs($mesa)->post('/mesa-de-partes/presencial', $this->datosPresencial([
            'folios' => 1, 'asunto' => 'Solicita constancia presencial',
        ]))->assertSessionHasNoErrors();
        $presencial = Expediente::where('modalidad', 'Presencial')->firstOrFail();
        $presencial->update(['codigo_propio' => 'MP001']); // expediente histórico con código del sello
        auth()->logout();
        $this->flushSession();

        $this->post('/consulta', ['numero' => 'mp001', 'numero_documento' => '28301456'])->assertSessionHasNoErrors();
        $this->get("/consulta/{$presencial->numero}")->assertOk()
            ->assertSee('Código del sello: <b>MP001</b>', false)
            ->assertDontSee('Tu código propio');
    }

    public function test_portal_publico_para_invitados(): void
    {
        $this->get('/')->assertOk()->assertSee('Presentar trámite')->assertSee('Consultar trámite');
        $this->get('/tramite/nuevo')->assertOk()->assertSee('Datos del remitente')->assertSee('Tipo de persona')
            ->assertDontSee('Código propio')->assertDontSee('Folios');
    }

    public function test_formulario_minimo_exige_correo_y_un_pdf_o_zip(): void
    {
        $this->enviar(['correo' => null])->assertSessionHasErrors('correo');
        $this->enviar(['archivo' => null])->assertSessionHasErrors('archivo');
        $this->enviar(['archivo' => UploadedFile::fake()->image('foto.jpg')])->assertSessionHasErrors('archivo');
        $this->assertSame(0, Expediente::count());

        $this->enviar(['archivo' => UploadedFile::fake()->create('documentos.zip', 300, 'application/zip')])->assertSessionHasNoErrors();
        $this->assertSame(1, Expediente::count());
    }

    /** El formulario público ya no pide código propio: si alguien lo envía igual, se ignora (la columna sigue en la base). */
    public function test_formulario_publico_ignora_el_codigo_propio(): void
    {
        $this->get('/tramite/nuevo')->assertOk()
            ->assertDontSee('Opciones adicionales')->assertDontSee('name="codigo_propio"', false);

        $this->enviar(['codigo_propio' => 'CONSTANCIA2026'])->assertSessionHasNoErrors();
        $this->enviar(['codigo_propio' => 'A1'])->assertSessionHasNoErrors(); // ni se valida
        $this->assertSame(2, Expediente::count());
        $this->assertSame(0, Expediente::whereNotNull('codigo_propio')->count());
    }

    /** Persona natural: DNI de exactamente 8 dígitos o carné de extranjería; el pasaporte ya no se acepta. */
    public function test_persona_natural_valida_documento_y_celular(): void
    {
        $this->get('/tramite/nuevo')->assertOk()
            // Tipo de persona: dos botones de opción, Natural marcado por defecto, antes de los demás datos
            ->assertSeeInOrder(['Tipo de persona', 'type="radio" name="tipo_persona" value="natural" required checked', 'Natural',
                'type="radio" name="tipo_persona" value="juridica" required', 'Jurídica', 'Datos de la entidad', 'Tus datos', 'N.° de documento'], false)
            ->assertDontSee('<select id="tipo_persona"', false)
            // Un solo campo de documento: en jurídica es el DNI del representante
            ->assertDontSee('name="representante_dni"', false)
            ->assertSee('placeholder="nombre@correo.com"', false)
            ->assertSee('<option selected>DNI</option>', false)
            ->assertSee('Carné de extranjería')->assertDontSee('Pasaporte')->assertDontSee('id="tipo_administrado"', false);

        $this->enviar(['tipo_persona' => null])->assertSessionHasErrors('tipo_persona');
        $this->enviar(['tipo_persona' => 'otra'])->assertSessionHasErrors('tipo_persona');
        $this->enviar(['numero_documento' => '4123456'])->assertSessionHasErrors(['numero_documento' => 'El DNI debe tener exactamente 8 dígitos.']);
        $this->enviar(['numero_documento' => '412345678'])->assertSessionHasErrors('numero_documento');
        $this->enviar(['numero_documento' => '4123456A'])->assertSessionHasErrors('numero_documento');
        $this->enviar(['tipo_doc_identidad' => 'Pasaporte', 'numero_documento' => 'AB123456'])->assertSessionHasErrors('tipo_doc_identidad');
        $this->enviar(['celular' => '866111222'])->assertSessionHasErrors('celular');
        $this->enviar(['celular' => '96611122'])->assertSessionHasErrors('celular');
        $this->enviar(['celular' => '9661112223'])->assertSessionHasErrors('celular');
        $this->assertSame(0, Expediente::count());

        // El formulario ya no pregunta "Soy…": el remitente nuevo queda como ciudadano
        $this->enviar()->assertSessionHasNoErrors();
        $rosa = Administrado::where('numero_documento', '41234567')->firstOrFail();
        $this->assertSame('DNI', $rosa->tipo_doc_identidad);
        $this->assertSame('Ciudadano', $rosa->tipo_administrado);
        $this->assertNull(Expediente::first()->representante_dni);
    }

    /** Persona jurídica: la entidad va en administrados y el representante en cada expediente. */
    public function test_persona_juridica_guarda_entidad_y_representante_en_el_expediente(): void
    {
        $this->enviarJuridica()->assertSessionHasNoErrors()->assertRedirect();

        $entidad = Administrado::where('numero_documento', '20123456789')->firstOrFail();
        $this->assertSame('RUC', $entidad->tipo_doc_identidad);
        $this->assertSame('Constructora Wari S.A.C.', $entidad->razon_social);
        $this->assertTrue($entidad->esJuridica());
        // Los datos del representante no se copian a la entidad
        $this->assertNull($entidad->nombres);
        $this->assertNull($entidad->correo);
        $this->assertNull($entidad->telefono);
        $this->assertSame('Constructora Wari S.A.C.', $entidad->nombre_completo);
        $this->assertNull(Administrado::where('numero_documento', '45678912')->first());

        $exp = Expediente::firstOrFail();
        $this->assertSame($entidad->id, $exp->administrado_id);
        $this->assertSame('45678912', $exp->representante_dni);
        $this->assertSame('Carlos Alberto', $exp->representante_nombres);
        $this->assertSame('Gutiérrez', $exp->representante_apellido_paterno);
        $this->assertSame('Palomino', $exp->representante_apellido_materno);
        $this->assertSame('987654321', $exp->representante_celular);
        $this->assertSame('carlos@wari.pe', $exp->representante_correo);

        // Otro representante presenta por la misma entidad: cada expediente conserva al suyo,
        // y la razón social registrada no cambia
        $this->enviarJuridica(['numero_documento' => '40102030', 'nombres' => 'Ana', 'apellido_paterno' => 'Rojas',
            'apellido_materno' => 'Sulca', 'correo' => 'ana@wari.pe', 'razon_social' => 'Otro nombre'])->assertSessionHasNoErrors();
        $this->assertSame(1, Administrado::where('numero_documento', '20123456789')->count());
        $this->assertSame('Constructora Wari S.A.C.', $entidad->fresh()->razon_social);
        $this->assertSame(['45678912', '40102030'], Expediente::orderBy('id')->pluck('representante_dni')->all());
        $this->assertSame('carlos@wari.pe', $exp->fresh()->representante_correo);
    }

    /**
     * Persona jurídica: RUC de 11 dígitos con prefijo válido y razón social.
     * (El representante se prueba aparte: la ruta acepta 10 envíos por minuto.)
     */
    public function test_persona_juridica_valida_ruc_razon_social_y_representante(): void
    {
        $this->enviarJuridica(['ruc' => null])->assertSessionHasErrors('ruc');
        $this->enviarJuridica(['ruc' => '2012345678'])->assertSessionHasErrors('ruc'); // 10 dígitos
        $this->enviarJuridica(['ruc' => '201234567890'])->assertSessionHasErrors('ruc'); // 12 dígitos
        $this->enviarJuridica(['ruc' => '30123456789'])->assertSessionHasErrors(['ruc' => 'El RUC debe tener 11 dígitos y empezar con 10, 15, 17 o 20.']);
        $this->enviarJuridica(['razon_social' => null])->assertSessionHasErrors('razon_social');
        $this->assertSame(0, Expediente::count());
        $this->assertSame(0, Administrado::where('tipo_doc_identidad', 'RUC')->count());

        // En jurídica el tipo de documento no se usa (el representante siempre va con DNI), y en natural se ignoran RUC y razón social
        $this->enviarJuridica(['tipo_doc_identidad' => 'Pasaporte'])->assertSessionHasNoErrors();
        $this->assertNull(Administrado::where('numero_documento', '45678912')->first()); // el representante no se registra como administrado
        $this->enviar(['ruc' => 'x', 'razon_social' => 'Ignorada'])->assertSessionHasNoErrors();
        $this->assertNull(Administrado::where('numero_documento', '41234567')->value('razon_social'));
        $this->assertNull(Expediente::latest('id')->first()->representante_dni);
    }

    /** Persona jurídica: el representante necesita DNI de 8 dígitos, apellidos, nombres, celular y correo. */
    public function test_persona_juridica_valida_datos_del_representante(): void
    {
        $this->enviarJuridica(['numero_documento' => null])->assertSessionHasErrors('numero_documento');
        $this->enviarJuridica(['numero_documento' => '4567891'])->assertSessionHasErrors(['numero_documento' => 'El DNI del representante debe tener exactamente 8 dígitos.']);
        $this->enviarJuridica(['numero_documento' => '456789123'])->assertSessionHasErrors('numero_documento');
        // Aunque llegue "carné de extranjería", el representante debe usar DNI
        $this->enviarJuridica(['tipo_doc_identidad' => 'Carné de extranjería', 'numero_documento' => 'CE123456'])->assertSessionHasErrors('numero_documento');
        $this->enviarJuridica(['nombres' => null])->assertSessionHasErrors('nombres');
        $this->enviarJuridica(['apellido_paterno' => null])->assertSessionHasErrors('apellido_paterno');
        $this->enviarJuridica(['apellido_materno' => null])->assertSessionHasErrors('apellido_materno');
        $this->enviarJuridica(['celular' => '887654321'])->assertSessionHasErrors('celular');
        $this->enviarJuridica(['correo' => null])->assertSessionHasErrors('correo');
        $this->enviarJuridica(['correo' => 'carlos@wari'])->assertSessionHasErrors('correo');
        $this->assertSame(0, Expediente::count());
        $this->assertSame(0, Administrado::where('tipo_doc_identidad', 'RUC')->count());
    }

    /** CU16: los avisos de un expediente de persona jurídica van al correo de su representante. */
    public function test_persona_juridica_notifica_al_representante_del_expediente(): void
    {
        Notification::fake();
        $this->enviarJuridica()->assertSessionHasNoErrors();
        $this->enviarJuridica(['numero_documento' => '40102030', 'nombres' => 'Ana', 'correo' => 'ana@wari.pe'])->assertSessionHasNoErrors();
        [$deCarlos, $deAna] = Expediente::orderBy('id')->get()->all();

        $this->assertSame('carlos@wari.pe', $deCarlos->correoDeAviso());
        $this->assertSame('ana@wari.pe', $deAna->correoDeAviso());
        Notification::assertSentOnDemand(AvisoExpediente::class,
            fn ($n, $canales, $ruta) => $ruta->routes['mail'] === 'carlos@wari.pe' && $n->toMail($ruta)->greeting === 'Hola, Carlos Alberto');
        Notification::assertSentOnDemand(AvisoExpediente::class, fn ($n, $c, $ruta) => $ruta->routes['mail'] === 'ana@wari.pe');
        $this->assertTrue($deCarlos->notificaciones()->where('evento', 'presentación')->value('enviada'));

        // Los avisos posteriores (recepción, observación…) también siguen al representante del expediente
        app(Notificador::class)->notificar($deAna, 'recepción');
        Notification::assertSentOnDemandTimes(AvisoExpediente::class, 3);
        Notification::assertSentOnDemand(AvisoExpediente::class,
            fn ($n, $c, $ruta) => $ruta->routes['mail'] === 'ana@wari.pe' && str_contains($n->toMail($ruta)->introLines[0], 'recepcionado'));
    }

    /** CU07: la persona jurídica consulta con el N.° de expediente y el RUC; el DNI del representante no sirve. */
    public function test_persona_juridica_consulta_solo_con_ruc(): void
    {
        $this->enviarJuridica()->assertSessionHasNoErrors();
        $exp = Expediente::firstOrFail();
        $this->flushSession();

        $this->post('/consulta', ['numero' => $exp->numero, 'numero_documento' => '45678912'])
            ->assertSessionHasErrors(['numero' => self::NO_ENCONTRADO]);
        $this->get("/consulta/{$exp->numero}")->assertRedirect('/consulta');

        $this->post('/consulta', ['numero' => $exp->numero, 'numero_documento' => '20123456789'])
            ->assertSessionHasNoErrors()->assertRedirect("/consulta/{$exp->numero}");
        $this->get("/consulta/{$exp->numero}")->assertOk()->assertSee($exp->asunto);
    }

    /** La constancia (web y PDF) y el panel de Mesa de Partes muestran la entidad y el representante. */
    public function test_persona_juridica_en_constancia_y_panel(): void
    {
        $this->enviarJuridica()->assertSessionHasNoErrors();
        $exp = Expediente::firstOrFail();

        $this->get("/consulta/{$exp->numero}/constancia")->assertOk()
            ->assertSeeInOrder(['Datos del remitente', 'RUC', '20123456789', 'Razón social', 'Constructora Wari S.A.C.',
                'Datos del representante', 'DNI', '45678912', 'Carlos Alberto', 'Gutiérrez', 'Palomino', 'Datos del trámite'])
            ->assertSee('ca***@wari.pe');
        $this->get("/consulta/{$exp->numero}/constancia/pdf")->assertOk();

        auth()->logout();
        $mesa = Usuario::where('correo', 'mesadepartes@demo.test')->first();
        $this->actingAs($mesa)->get("/mesa-de-partes/expedientes/{$exp->id}")->assertOk()
            ->assertSee('Constructora Wari S.A.C. · RUC 20123456789 · Persona jurídica')
            ->assertSee('Carlos Alberto Gutiérrez Palomino · DNI 45678912')
            ->assertSee('carlos@wari.pe · 987654321');
        $this->get('/mesa-de-partes')->assertOk()->assertSee('Constructora Wari S.A.C.');
    }

    /** Persona natural con DNI: la constancia sigue mostrando sus datos, sin bloque de representante. */
    public function test_persona_natural_constancia_sin_representante(): void
    {
        $this->enviar()->assertSessionHasNoErrors();
        $exp = Expediente::firstOrFail();

        $this->get("/consulta/{$exp->numero}/constancia")->assertOk()
            ->assertSeeInOrder(['DNI', '41234567', 'Nombres', 'Rosa', 'Apellido paterno', 'Pariona'])
            ->assertDontSee('Datos del representante');
    }

    public function test_envia_el_codigo_por_correo_al_presentar(): void
    {
        Notification::fake();
        $this->enviar()->assertSessionHasNoErrors();

        $exp = Expediente::first();
        $aviso = $exp->notificaciones()->where('evento', 'presentación')->first();
        $this->assertNotNull($aviso);
        $this->assertStringContainsString($exp->numero, $aviso->mensaje);
        Notification::assertSentOnDemand(AvisoExpediente::class);
    }

    public function test_apellidos_separados_materno_obligatorio_solo_con_dni(): void
    {
        // Con DNI el apellido materno es obligatorio
        $this->enviar(['apellido_materno' => ''])->assertSessionHasErrors('apellido_materno');

        // Con carné de extranjería puede haber un solo apellido
        $this->enviar(['tipo_doc_identidad' => 'Carné de extranjería', 'numero_documento' => 'CE123456',
            'nombres' => 'Lucía', 'apellido_paterno' => 'Moreno', 'apellido_materno' => ''])
            ->assertSessionHasNoErrors();
        $extranjera = Administrado::where('numero_documento', 'CE123456')->firstOrFail();
        $this->assertSame('Moreno', $extranjera->apellido_paterno);
        $this->assertNull($extranjera->apellido_materno);
        $this->assertSame('Lucía Moreno', $extranjera->nombre_completo);

        // Apellidos compuestos se guardan tal cual, cada uno en su columna
        $this->enviar(['numero_documento' => '40506070', 'apellido_paterno' => 'De la Cruz', 'apellido_materno' => 'Vargas'])
            ->assertSessionHasNoErrors();
        $this->assertSame('De la Cruz', Administrado::where('numero_documento', '40506070')->value('apellido_paterno'));

        // La búsqueda del personal encuentra por nombre y apellidos, aunque estén en columnas distintas
        $numero = $extranjera->expedientes()->value('numero');
        $this->actingAs(Usuario::where('correo', 'admin@demo.test')->first())
            ->get('/busqueda?administrado='.urlencode('Lucía Moreno'))->assertOk()->assertSee($numero);
        auth()->logout();

        // El formulario muestra los dos campos
        $this->get('/tramite/nuevo')->assertOk()
            ->assertSee('id="apellido_paterno"', false)->assertSee('id="apellido_materno"', false);
    }


    public function test_correo_sin_terminacion_se_rechaza(): void
    {
        // "nelson@test" cumple el formato mínimo, pero no es un correo real: no le llegarían los avisos
        $this->enviar(['correo' => 'nelson@test'])->assertSessionHasErrors('correo');
        $this->enviar(['correo' => 'nelson@gmail.com'])->assertSessionHasNoErrors();
        $this->enviar(['numero_documento' => '41234568', 'correo' => 'nelson@tecnologicohierropozo.edu.pe'])->assertSessionHasNoErrors();
    }


    public function test_confirmacion_despues_de_enviar(): void
    {
        // Justo después de enviar: mensaje de éxito, número para copiar, correo oculto y próximos pasos
        $this->followingRedirects()->withSession(['captcha' => '7']);
        $this->enviar()->assertOk()
            ->assertSee('¡Tu trámite fue enviado!')
            ->assertSee('id="copiar-numero"', false)
            ->assertSee('ro***@example.com')
            ->assertSee('¿Qué pasa ahora?')
            ->assertSee('Descargar constancia (PDF)');

        // Si vuelve a abrir la constancia más tarde, ve solo la constancia (sin el mensaje de éxito)
        $exp = Expediente::firstOrFail();
        $this->get("/consulta/{$exp->numero}/constancia")->assertOk()
            ->assertSee($exp->numero)->assertDontSee('¡Tu trámite fue enviado!');
    }


    public function test_seguimiento_muestra_donde_esta_y_terminar_consulta(): void
    {
        $this->enviar();
        $exp = Expediente::first();
        $flujo = app(FlujoExpediente::class);
        $mesa = Usuario::where('correo', 'mesadepartes@demo.test')->first();
        $flujo->recepcionar($exp, $mesa);
        $flujo->derivar($exp->fresh(), $mesa, Area::where('sigla', 'SA')->first(), null, null);
        auth()->logout();

        // Dice dónde está ahora (no el área inicial) y qué significa su estado
        $this->get("/consulta/{$exp->numero}")->assertOk()
            ->assertSeeInOrder(['Está en', 'Secretaría Académica', 'Te avisaremos por correo cuando responda'])
            ->assertSee('Terminar consulta');

        // Terminar consulta quita el acceso en este navegador
        $this->post("/consulta/{$exp->numero}/salir")->assertRedirect('/consulta');
        $this->get("/consulta/{$exp->numero}")->assertRedirect('/consulta');
    }

}
