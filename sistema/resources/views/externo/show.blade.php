{{-- CU07 Detalle del expediente y CU06 Subsanar observación, sin cuenta (Figura 10).
     Mejora de interfaz, paso 2: una sola columna y un solo recorrido (las burbujas); se quitó el
     historial detallado de la derecha porque repetía los mismos pasos. --}}
@extends('layouts.base')
@section('titulo', $expediente->numero)

@php
    $rutaDocumento = fn ($doc) => route('externo.documento', [$expediente->numero, $doc]);
    // Dónde está ahora (área del último movimiento) y qué significa su estado, en palabras simples
    $areaActual = $expediente->movimientos->last()?->areaDestino?->nombre ?? $expediente->areaDestino->nombre;
    $finalizado = $expediente->estado === \App\Models\Expediente::FINALIZADO;
    $explicacion = [
        'Presentado' => 'Mesa de Partes revisará tu trámite en su horario de atención.',
        'Recibido' => 'Mesa de Partes recibió tu trámite y lo enviará al área que corresponde.',
        'Observado' => 'Tu trámite tiene una observación. Revisa el aviso y envía la subsanación.',
        'Subsanado' => 'Enviaste la subsanación. Mesa de Partes la está revisando.',
        'Derivado' => 'Tu trámite fue enviado a esta área. Te avisaremos por correo cuando responda.',
        'En atención' => 'Esta área está atendiendo tu trámite. Te avisaremos por correo cuando responda.',
    ][$expediente->estado] ?? '';
    $ultimaRespuesta = $expediente->respuestas->last();
    $presentados = $expediente->documentos->whereNull('respuesta_id')->whereNull('subsanacion_id');
    // CU07 A3: tiempo que lleva la observación sin subsanar
    if ($observacionPendiente) {
        $dias = $observacionPendiente->diasTranscurridos();
        $unidad = config('plazos.habiles') ? ($dias === 1 ? 'día hábil' : 'días hábiles') : ($dias === 1 ? 'día' : 'días');
        $hace = $dias === 0 ? 'hoy' : "hace {$dias} {$unidad}";
    }
@endphp

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/burbujas.css') }}?v={{ filemtime(public_path('css/burbujas.css')) }}">
@endpush

@section('cuerpo')
<main class="main publico seguimiento">
<div class="seguimiento-arriba">
    <nav class="ruta" aria-label="Estás en">
        <a href="{{ route('inicio') }}">@include('partials.icono', ['i' => 'inicio']) Inicio</a>
        <span aria-hidden="true">›</span>
        <a href="{{ route('externo.consulta') }}">Consultar</a>
        <span aria-hidden="true">›</span>
        <b aria-current="page">{{ $expediente->numero }}</b>
    </nav>
    {{-- En una computadora compartida, cierra el acceso a este expediente en este navegador --}}
    <form method="POST" action="{{ route('externo.salir', $expediente->numero) }}">
        @csrf
        <button class="enlace-boton terminar">@include('partials.icono', ['i' => 'salir']) Terminar consulta</button>
    </form>
</div>
@include('partials.mensajes')
@if ($observacionPendiente)
    {{-- CU07 A3: aviso destacado mientras haya una observación sin subsanar --}}
    <section class="alert aviso-observacion" aria-labelledby="aviso-observacion-titulo">
        <div>
            <h2 id="aviso-observacion-titulo">Tu trámite tiene una observación pendiente</h2>
            <p>Mesa de Partes lo observó el {{ $observacionPendiente->fecha->format('d/m/Y') }} ({{ $hace }}). Tu trámite no avanzará hasta que envíes la subsanación.</p>
        </div>
        <a class="btn" href="#observacion">Ver observación y subsanar</a>
    </section>
@endif
@if ($finalizado && $ultimaRespuesta)
    {{-- Trámite atendido: la respuesta va arriba, a la vista --}}
    <section class="card atendido" aria-labelledby="atendido-titulo">
        <span class="atendido-ok" aria-hidden="true">✓</span>
        <div>
            <h2 id="atendido-titulo">Tu trámite fue atendido</h2>
            <p>{{ $areaActual }} respondió el {{ $ultimaRespuesta->fecha->format('d/m/Y') }}: «{{ \Illuminate\Support\Str::limit($ultimaRespuesta->contenido, 160) }}»</p>
            @if ($ultimaRespuesta->codigo_verificacion)
                <a class="btn verde" href="{{ route('externo.respuesta.pdf', [$expediente->numero, $ultimaRespuesta]) }}">@include('partials.icono', ['i' => 'constancia']) Descargar respuesta (PDF)</a>
            @endif
        </div>
    </section>
@endif

{{-- Resumen: qué trámite es, en qué estado está y dónde está ahora --}}
<section class="card resumen-tramite resumen-{{ str_replace(' ', '-', $expediente->estado) }}">
    <div class="resumen-cabecera">
        <div>
            <span class="muted">Expediente</span>
            <h1>{{ $expediente->numero }}</h1>
            {{-- CU07 B2: el código con el que también puede consultar (mismo texto que la constancia) --}}
            @if ($expediente->codigo_propio)
                <div class="codigo-propio">{{ $expediente->modalidad === 'Presencial' ? 'Código del sello' : 'Tu código propio' }}: <b>{{ $expediente->codigo_propio }}</b></div>
            @endif
            <div class="resumen-asunto">{{ $expediente->asunto }}</div>
        </div>
        @include('partials.estado', ['estado' => $expediente->estado])
    </div>
    @unless ($finalizado)
        <div class="resumen-ubicacion">
            @include('partials.icono', ['i' => 'areas'])
            <div>
                <span>Está en</span>
                <b>{{ $areaActual }}</b>
                @if ($explicacion)<p>{{ $explicacion }}</p>@endif
            </div>
        </div>
    @endunless
</section>

<section class="card recorrido">
    @include('externo.burbujas')
</section>

<div class="card detalle-tramite">
        <h2>Datos del trámite</h2>
        <dl class="doc-datos">
            <dt>Tipo de documento</dt><dd>{{ $expediente->tipoDocumento?->nombre ?? 'Por definir' }}</dd>
            <dt>Presentado el</dt><dd>{{ $expediente->fecha_presentacion->format('d/m/Y') }} a las {{ $expediente->fecha_presentacion->format('H:i') }}</dd>
            <dt>Modalidad</dt><dd>{{ $expediente->modalidad }}</dd>
        </dl>
        @if ($expediente->detalle)<p style="margin-top:12px">{{ $expediente->detalle }}</p>@endif

        <h2 style="margin-top:18px">Documentos</h2>
        @include('expedientes.documentos', ['documentos' => $presentados, 'rutaDocumento' => $rutaDocumento, 'detallado' => true])

        {{-- La observación solo se muestra mientras espera subsanación; las ya subsanadas se listan más abajo --}}
        @if ($observacionPendiente)
            <h2 id="observacion" class="ancla" style="margin-top:18px">Observación de Mesa de Partes</h2>
            <div class="alert"><b>Motivo:</b> {{ $observacionPendiente->motivo }}@if ($observacionPendiente->detalle)<br><b>Debe corregir o adjuntar:</b> {{ $observacionPendiente->detalle }}@endif
                <br><span class="muted">{{ $observacionPendiente->fecha->format('d/m/Y H:i') }} · {{ $hace }}</span></div>

            <h2 id="subsanar" class="ancla" style="margin-top:18px">Subsanar</h2>
            <form method="POST" action="{{ route('externo.subsanar', $expediente->numero) }}" enctype="multipart/form-data">
                @csrf
                <label for="descripcion">Corrección realizada *</label>
                <textarea id="descripcion" name="descripcion" required maxlength="2000">{{ old('descripcion') }}</textarea>
                @error('descripcion')<div class="error">{{ $message }}</div>@enderror
                <label for="archivos">Archivos <span class="muted">· PDF o imagen, hasta 5 de 10 MB cada uno</span></label>
                <div class="zona-archivo" id="zona-subsanar">
                    <input id="archivos" type="file" name="archivos[]" accept="application/pdf,image/jpeg,image/png" multiple aria-describedby="archivos-estado">
                    <div class="zona-vacia" id="subsanar-vacia">
                        @include('partials.icono', ['i' => 'carpeta'])
                        <span><b>Arrastra tus archivos aquí</b> o <u>elígelos desde tu equipo</u></span>
                    </div>
                    <div class="zona-elegido" id="subsanar-elegido" hidden>
                        <ul class="zona-lista" id="subsanar-lista"></ul>
                        <button type="button" class="btn o btn-chico" id="subsanar-quitar">✕ Quitar</button>
                    </div>
                </div>
                <div class="error" id="archivos-estado" role="alert" hidden></div>
                @foreach ($errors->get('archivos.*') as $m)<div class="error">{{ $m[0] }}</div>@endforeach
                <div style="margin-top:12px"><button class="btn w">Enviar subsanación</button></div>
            </form>
        @endif

        @foreach ($expediente->respuestas as $r)
            <h2 style="margin-top:18px">Respuesta</h2>
            <div class="ok">{{ $r->contenido }}<br><span class="muted">{{ $r->fecha->format('d/m/Y H:i') }}</span></div>
            @include('expedientes.documentos', ['documentos' => $r->documentos, 'rutaDocumento' => $rutaDocumento])
            @if ($r->codigo_verificacion)
                @include('partials.verificacion', ['codigo' => $r->codigo_verificacion, 'firmante' => \App\Services\Verificacion::firmante($r)])
                <div style="margin-top:6px"><a class="btn o" href="{{ route('externo.respuesta.pdf', [$expediente->numero, $r]) }}">Descargar respuesta en PDF</a></div>
            @endif
        @endforeach

        {{-- Subsanaciones ya enviadas por el ciudadano (las observaciones pendientes se muestran arriba) --}}
        @php $subsanadas = $expediente->observaciones->filter(fn ($obs) => $obs->subsanacion); @endphp
        @if ($subsanadas->isNotEmpty())
            <h2 class="historial-subtitulo">Subsanaciones realizadas</h2>
            @foreach ($subsanadas as $obs)
                <div class="ok subsanacion">
                    <b>Subsanación enviada</b>
                    <small><time datetime="{{ $obs->subsanacion->fecha->toIso8601String() }}">{{ $obs->subsanacion->fecha->format('d/m/Y H:i') }}</time></small>
                    <p>{{ $obs->subsanacion->descripcion }}</p>
                    <small>Atendió la observación del {{ $obs->fecha->format('d/m/Y') }}: {{ $obs->motivo }}</small>
                    @include('expedientes.documentos', ['documentos' => $obs->subsanacion->documentos, 'rutaDocumento' => $rutaDocumento])
                </div>
            @endforeach
        @endif

        <div class="acciones-detalle">
            @if ($expediente->codigo_verificacion)
                <a class="btn o" href="{{ route('externo.constancia.pdf', $expediente->numero) }}">@include('partials.icono', ['i' => 'constancia']) Descargar constancia (PDF)</a>
            @endif
            <a href="{{ route('externo.constancia', $expediente->numero) }}">Ver constancia de presentación</a>
        </div>
</div>
</main>
@if ($observacionPendiente)
<script>
// Zona de archivos de la subsanación: lista nombres y tamaños, y avisa al instante si hay más de 5, si pesan más de 10 MB o no son PDF/imagen
(() => {
    const entrada = document.getElementById('archivos'), zona = document.getElementById('zona-subsanar'), estado = document.getElementById('archivos-estado');
    const lista = document.getElementById('subsanar-lista'), MAXIMO = 10 * 1024 * 1024;
    const tamano = (b) => b < 1024 * 1024 ? Math.max(1, Math.round(b / 1024)) + ' KB' : (b / 1024 / 1024).toFixed(1).replace('.', ',') + ' MB';
    function revisar() {
        const archivos = [...entrada.files];
        let error = '';
        if (archivos.length > 5) error = 'Puedes adjuntar hasta 5 archivos. Elegiste ' + archivos.length + '.';
        archivos.forEach((f) => {
            const ext = f.name.split('.').pop().toLowerCase();
            if (!error && !['pdf', 'jpg', 'jpeg', 'png'].includes(ext)) error = '«' + f.name + '» no es PDF ni imagen (JPG o PNG).';
            if (!error && f.size > MAXIMO) error = '«' + f.name + '» pesa ' + tamano(f.size) + '. El máximo es 10 MB por archivo.';
        });
        entrada.setCustomValidity(error);
        estado.textContent = error; estado.hidden = !error;
        zona.classList.toggle('con-archivo', archivos.length > 0); zona.classList.toggle('con-error', !!error);
        document.getElementById('subsanar-vacia').hidden = archivos.length > 0;
        document.getElementById('subsanar-elegido').hidden = archivos.length === 0;
        lista.replaceChildren(...archivos.map((f) => { const li = document.createElement('li'); li.textContent = f.name + ' · ' + tamano(f.size); return li; }));
    }
    entrada.addEventListener('change', revisar);
    document.getElementById('subsanar-quitar').addEventListener('click', () => { entrada.value = ''; revisar(); entrada.focus(); });
    ['dragenter', 'dragover'].forEach((ev) => zona.addEventListener(ev, () => zona.classList.add('arrastrando')));
    ['dragleave', 'drop'].forEach((ev) => zona.addEventListener(ev, () => zona.classList.remove('arrastrando')));
})();
</script>
@endif
@endsection
