{{-- CU05 Presentar trámite sin cuenta (Figura 11), con los campos de la Mesa de Partes Virtual de la UNSCH. --}}
@extends('layouts.base')
@section('titulo', 'Presentar trámite')

@section('cuerpo')
<main class="main publico formulario">
    {{-- Encabezado suave: ruta visible, título con ícono y ayuda con línea dorada --}}
    <nav class="ruta" aria-label="Estás en">
        <a href="{{ route('inicio') }}">@include('partials.icono', ['i' => 'inicio']) Inicio</a>
        <span aria-hidden="true">›</span>
        <b aria-current="page">Presentar trámite</b>
    </nav>
    <h1 class="titulo-tramite"><span class="titulo-ico">@include('partials.icono', ['i' => 'tipos'])</span> Presentar trámite</h1>
    <p class="intro">No necesitas crear una cuenta. Al enviar recibirás tu número de expediente para hacer el seguimiento.</p>

    @if ($errors->any())
        <div class="alert">Revisa los campos marcados en rojo.</div>
    @endif

    <form method="POST" action="{{ route('externo.store') }}" enctype="multipart/form-data" id="formulario">
        @csrf
        {{-- Campo trampa contra robots --}}
        <div style="position:absolute;left:-9999px" aria-hidden="true"><input name="sitio_web" tabindex="-1" autocomplete="off"></div>

        <section class="card paso">
            <h2><span class="num">1</span> Datos del remitente</h2>
            <p class="sub">Todos los campos son obligatorios, salvo los marcados como opcionales.</p>
            @include('partials.formulario.solicitante', ['documentos' => \App\Models\Administrado::DOCUMENTOS_PERSONA_NATURAL])
        </section>

        <section class="card paso">
            <h2><span class="num">2</span> Tu trámite</h2>
            {{-- Mejora de interfaz, paso 5: el ciudadano indica qué presenta, así Mesa de Partes no tiene que adivinarlo --}}
            <fieldset style="border:0;margin:0;padding:0">
                <legend class="leyenda">¿Qué vas a presentar? <span class="op">(opcional)</span></legend>
                <div class="muted">Si no estás seguro, déjalo sin marcar: Mesa de Partes lo completará.</div>
                <div class="tipos-doc">
                    @foreach ($tipos as $t)
                        <label class="tipo-doc" title="{{ $t->descripcion }}"><input type="radio" name="tipo_documento_id" value="{{ $t->id }}" @checked(old('tipo_documento_id') == $t->id)>
                            <span><b>{{ $t->nombre }}</b><small>{{ $t->descripcion }}</small></span></label>
                    @endforeach
                </div>
                @error('tipo_documento_id')<div class="error">{{ $message }}</div>@enderror
            </fieldset>
            <label for="asunto">Asunto</label>
            <input id="asunto" name="asunto" value="{{ old('asunto') }}" required maxlength="255" placeholder="Describa el asunto de su documento" aria-describedby="asunto-contador">
            <div class="contador" id="asunto-contador">{{ mb_strlen(old('asunto', '')) }} / 255</div>
            @error('asunto')<div class="error">{{ $message }}</div>@enderror

            @include('partials.formulario.archivo', [
                'etiqueta' => 'Archivo',
                'ayuda' => 'Arrastra tu archivo aquí o haz clic para elegirlo. Si son varios documentos, júntalos en un PDF o ZIP.',
            ])

        </section>

        <section class="card paso">
            <h2><span class="num">3</span> Confirmar y enviar</h2>
            {{-- Issue #20: los términos se aceptan en la ventana del inicio; aquí solo se recuerdan --}}
            <p class="terminos-aceptados" id="terminos-estado" @unless (old('acepta_datos')) hidden @endunless>
                <span class="terminos-ok" aria-hidden="true">✓</span>
                <span>Aceptaste los términos y condiciones. <button type="button" class="enlace-boton" id="terminos-ver">Ver términos y condiciones</button></span>
            </p>
            @error('acepta_datos')<div class="error">{{ $message }}</div>@enderror
            <div class="row" style="align-items:flex-end;margin-top:6px">
                <div class="col" style="max-width:260px">
                    @include('externo.partials.captcha')
                </div>
                <div class="col ayuda">Puedes enviar las 24 horas. Lo enviado fuera de horario se recepciona el siguiente día hábil.</div>
            </div>
            <div class="acciones">
                <button class="btn" id="enviar">Enviar trámite</button>
                <a class="enlace-cancelar" href="{{ route('inicio') }}">Cancelar</a>
            </div>
        </section>
    </form>
    {{-- Issue #20: términos y condiciones en ventana emergente al abrir el formulario.
         La casilla pertenece al formulario (form="formulario") aunque esté dentro de la ventana.
         data-al-inicio la abre sola; si el formulario volvió con errores y ya aceptó, no se repite. --}}
    <dialog class="revisar terminos" id="terminos" aria-labelledby="terminos-titulo" @unless (old('acepta_datos')) data-al-inicio @endunless>
        <div class="revisar-cabecera">
            <span class="revisar-ico">@include('partials.icono', ['i' => 'constancia'])</span>
            <div>
                <h2 id="terminos-titulo">Términos y condiciones</h2>
                <p>Léelos antes de presentar tu trámite.</p>
            </div>
        </div>
        <div class="revisar-cuerpo terminos-texto">
            <ol>
                <li><b>Declaración de veracidad.</b> La información y los documentos que presentes deben ser verdaderos. Se presume que lo son y el instituto puede verificarlos.</li>
                <li><b>Datos personales.</b> Tus datos se usan solo para atender este trámite y comunicarnos contigo, según la Ley N.° 29733, Ley de Protección de Datos Personales.</li>
                <li><b>Notificaciones.</b> Los avisos sobre tu trámite llegarán al correo que escribas. Revisa que esté bien escrito.</li>
                <li><b>Archivo.</b> Un solo archivo PDF o ZIP de hasta 10 MB. Si son varios documentos, júntalos en un PDF o ZIP.</li>
                <li><b>Horario.</b> Puedes enviar las 24 horas. Lo enviado fuera del horario de atención se recepciona el siguiente día hábil.</li>
                <li><b>Límite.</b> Se aceptan hasta 5 trámites por documento de identidad al día.</li>
            </ol>
            <label class="terminos-acepto">
                <input type="checkbox" name="acepta_datos" value="1" form="formulario" id="acepta_datos" @checked(old('acepta_datos'))>
                <span>He leído y acepto los términos y condiciones.</span>
            </label>
        </div>
        <div class="botones">
            <a class="btn o" href="{{ route('inicio') }}">No acepto</a>
            <button type="button" class="btn" id="terminos-aceptar" @unless (old('acepta_datos')) disabled @endunless>Aceptar y continuar</button>
        </div>
    </dialog>
    {{-- Mejora de interfaz, paso 5: "Revisa tus datos" antes de enviar, para evitar envíos con errores --}}
    <dialog class="revisar" id="revisar" aria-labelledby="revisar-titulo" aria-describedby="revisar-ayuda">
        <div class="revisar-cabecera">
            <span class="revisar-ico">@include('partials.icono', ['i' => 'constancia'])</span>
            <div>
                <h2 id="revisar-titulo">Revisa tus datos antes de enviar</h2>
                <p id="revisar-ayuda">Una vez enviado no podrás modificarlo. Si algo está mal, presiona Corregir.</p>
            </div>
            <button type="button" class="revisar-cerrar" id="revisar-cerrar" aria-label="Cerrar y corregir">✕</button>
        </div>
        <div class="revisar-cuerpo">
            <h3>@include('partials.icono', ['i' => 'usuarios']) Tus datos</h3>
            <dl id="resumen-datos"></dl>
            <h3>@include('partials.icono', ['i' => 'tipos']) Tu trámite</h3>
            <dl id="resumen-tramite"></dl>
        </div>
        <div class="botones">
            <button type="button" class="btn o" id="corregir">Corregir</button>
            <button type="button" class="btn" id="confirmar">Confirmar y enviar →</button>
        </div>
    </dialog>
</main>

<script src="{{ asset('js/formulario.js') }}?v={{ filemtime(public_path('js/formulario.js')) }}"></script>
<script>
(() => {
    const $ = (id) => document.getElementById(id);
    const {esJuridica} = Formulario.solicitante({buscarUrl: '{{ route('externo.buscar') }}'});
    const tipo = $('tipo_doc_identidad'), numero = $('numero_documento'), ruc = $('ruc'), razon = $('razon_social');
    const tamano = Formulario.tamano;

    // Issue #20: términos y condiciones al inicio. No se cierra con Esc ni con clic fuera:
    // solo con «Aceptar y continuar» (después de marcar la casilla) o «No acepto» (vuelve a la portada).
    const terminos = $('terminos'), acepto = $('acepta_datos'), aceptar = $('terminos-aceptar');
    acepto.addEventListener('change', () => { aceptar.disabled = !acepto.checked; });
    terminos.addEventListener('cancel', (e) => { if (!acepto.checked) e.preventDefault(); });
    aceptar.addEventListener('click', () => {
        if (!acepto.checked) return;
        terminos.close();
        $('terminos-estado').hidden = false;
    });
    $('terminos-ver').addEventListener('click', () => terminos.showModal());
    if ('alInicio' in terminos.dataset && typeof terminos.showModal === 'function') terminos.showModal();

    // Resumen para revisar antes de enviar (si el navegador no tiene <dialog>, se envía directo)
    const form = $('formulario'), dialogo = $('revisar');
    let confirmado = false;
    form.addEventListener('submit', (e) => {
        if (confirmado || typeof dialogo.showModal !== 'function') return;
        e.preventDefault();
        const tipoDoc = form.querySelector('input[name="tipo_documento_id"]:checked');
        const archivo = $('archivo').files[0];
        // Tus datos: el documento lleva su tipo como etiqueta y solo el número como valor; cada nombre en su fila
        const juridica = esJuridica();
        llenar('resumen-datos', [
            ['Tipo de persona', juridica ? 'Jurídica' : 'Natural'],
            ['RUC', juridica ? ruc.value : '', 'revisar-doc'],
            ['Razón social', juridica ? razon.value : ''],
            [juridica ? 'DNI del representante' : tipo.value, numero.value, 'revisar-doc'],
            ['Apellido paterno', $('apellido_paterno').value],
            ['Apellido materno', $('apellido_materno').value],
            ['Nombres', $('nombres').value],
            ['Celular', $('celular').value.replace(/(\d{3})(?=\d)/g, '$1 ')],
            ['Correo', $('correo').value],
        ]);
        // Tu trámite
        llenar('resumen-tramite', [
            ['Tipo de documento', tipoDoc ? tipoDoc.closest('label').querySelector('b').textContent : 'Sin indicar (lo completará Mesa de Partes)'],
            ['Asunto', $('asunto').value],
            ['Archivo', archivo ? archivo.name + ' · ' + tamano(archivo.size) : '', 'archivo'],
        ]);
        dialogo.showModal();
    });
    // Arma una lista de "etiqueta: valor"; las filas vacías no se muestran
    function llenar(id, filas) {
        const dl = $(id);
        dl.replaceChildren();
        filas.filter(([, valor]) => valor.trim() !== '').forEach(([etiqueta, valor, clase]) => {
            const dt = document.createElement('dt'), dd = document.createElement('dd');
            dt.textContent = etiqueta;
            if (clase === 'archivo') {
                const ext = document.createElement('span');
                ext.className = 'revisar-ext';
                ext.textContent = valor.split(' · ')[0].split('.').pop().toUpperCase().slice(0, 3);
                dd.append(ext, valor);
            } else {
                dd.textContent = valor;
                if (clase) dd.className = clase;
            }
            dl.append(dt, dd);
        });
    }
    $('corregir').addEventListener('click', () => dialogo.close());
    $('revisar-cerrar').addEventListener('click', () => dialogo.close());
    $('confirmar').addEventListener('click', () => { confirmado = true; dialogo.close(); form.requestSubmit(); });

    // Evita el doble envío: al enviar de verdad, el botón se bloquea y dice "Enviando…"
    const enviar = $('enviar');
    form.addEventListener('submit', () => {
        if (!confirmado && typeof dialogo.showModal === 'function') return; // todavía se está revisando el resumen
        enviar.disabled = true; enviar.textContent = 'Enviando…';
    });
    // Si el ciudadano vuelve con el botón "Atrás", el botón se habilita otra vez
    window.addEventListener('pageshow', () => { confirmado = false; enviar.disabled = false; enviar.textContent = 'Enviar trámite'; });

    Formulario.avisos($('formulario').querySelectorAll('section:first-of-type input:not([type=hidden])'), esJuridica);

    // Contador del asunto
    const asunto = $('asunto'), contador = $('asunto-contador');
    asunto.addEventListener('input', () => { contador.textContent = asunto.value.length + ' / 255'; });

    Formulario.archivo();
})();
</script>
@endsection
