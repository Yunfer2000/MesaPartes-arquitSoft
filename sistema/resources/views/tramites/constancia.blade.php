{{-- Constancia de presentación (Figura 11) y cargo del registro presencial (Figura 13). --}}
@extends('layouts.base')
@section('titulo', 'Constancia '.$expediente->numero)

@section('cuerpo')
<main class="main" style="max-width:640px;margin:0 auto">
    @php
        // El ciudadano acaba de enviar su trámite: se muestra la confirmación (solo en pantalla, no se imprime)
        $recienEnviado = ! auth()->check() && session('exito') && $expediente->modalidad === 'Virtual';
    @endphp
    @if ($recienEnviado)
        <section class="card confirmacion no-print" aria-labelledby="confirmacion-titulo">
            <span class="confirmacion-ok" aria-hidden="true">✓</span>
            <h1 id="confirmacion-titulo">¡Tu trámite fue enviado!</h1>
            <p class="muted">Guarda tu número de expediente para consultar el seguimiento.</p>
            <div class="confirmacion-numero">
                <span id="numero-expediente">{{ $expediente->numero }}</span>
                <button type="button" class="btn o btn-chico" id="copiar-numero">@include('partials.icono', ['i' => 'constancia']) Copiar</button>
            </div>
            <span class="confirmacion-copiado" id="copiado" role="status"></span>
            @if ($expediente->correoDeAviso())
                <p class="confirmacion-correo">@include('partials.icono', ['i' => 'correo']) Te enviamos un aviso a <b>{{ \App\Models\Administrado::ocultar($expediente->correoDeAviso()) }}</b></p>
            @endif
        </section>

        <section class="card pasos-siguientes no-print">
            <h2>¿Qué pasa ahora?</h2>
            <ol>
                <li class="hecho"><b>Enviado</b><span>{{ $expediente->fecha_presentacion->isToday() ? 'Hoy' : $expediente->fecha_presentacion->format('d/m/Y') }}, {{ $expediente->fecha_presentacion->format('H:i') }}</span></li>
                <li><b>Mesa de Partes lo revisa</b><span>En horario de atención. Si falta algo, te avisaremos para que lo subsanes.</span></li>
                <li><b>Se deriva al área que corresponde</b><span>Recibirás un correo cada vez que cambie su estado.</span></li>
            </ol>
        </section>
    @else
        <div class="no-print">@include('partials.mensajes')</div>
    @endif

    <div class="no-print acciones-constancia">
        @if ($expediente->codigo_verificacion)
            <a class="btn" href="{{ auth()->check() ? route('tramites.constancia.pdf', $expediente) : route('externo.constancia.pdf', $expediente->numero) }}">@include('partials.icono', ['i' => 'constancia']) Descargar constancia (PDF)</a>
        @endif
        @auth
            <a class="btn o" href="{{ route('inicio') }}">Volver</a>
        @else
            <a class="btn o" href="{{ route('externo.show', $expediente->numero) }}">Ver seguimiento</a>
        @endauth
        <button type="button" class="enlace-boton" onclick="window.print()">Imprimir</button>
    </div>

    {{-- Constancia formal. Tras enviar solo se imprime (en pantalla ya están los botones); si se abre después, también se ve. --}}
    @php
        $a = $expediente->administrado;
        $presencial = $expediente->modalidad === 'Presencial';
        $presentados = $expediente->documentos->whereNull('respuesta_id')->whereNull('subsanacion_id');
    @endphp
    <article class="card constancia-doc {{ $recienEnviado ? 'solo-impresion' : '' }}" aria-label="Constancia">
        <header class="doc-cabecera">
            <img src="{{ asset('img/escudo.png') }}" alt="Escudo del instituto">
            <div><b>IESTP MANUEL ANTONIO HIERRO POZO</b><span>Mesa de Partes Virtual · Huamanga, Ayacucho</span></div>
        </header>
        <h2 class="doc-titulo">{{ $presencial ? 'Cargo de recepción' : 'Constancia de presentación de trámite' }}</h2>
        <div class="doc-numero"><span>{{ $expediente->numero }}</span></div>
        <p class="doc-fecha">{{ $presencial ? 'Recibido' : 'Presentado' }} el {{ $expediente->fecha_presentacion->format('d/m/Y') }} a las {{ $expediente->fecha_presentacion->format('H:i') }} · Modalidad {{ mb_strtolower($expediente->modalidad) }}</p>

        <h3 class="doc-seccion">Datos del remitente</h3>
        <dl class="doc-datos">
            <dt>{{ $a->tipo_doc_identidad }}</dt><dd>{{ $a->numero_documento }}</dd>
            @if ($a->razon_social)<dt>Razón social</dt><dd>{{ $a->razon_social }}</dd>@endif
            {{-- Persona jurídica del formulario público: el representante está en el expediente --}}
            @unless ($expediente->tieneRepresentante())
                <dt>Nombres</dt><dd>{{ $a->nombres }}</dd>
                <dt>Apellido paterno</dt><dd>{{ $a->apellido_paterno }}</dd>
                @if ($a->apellido_materno)<dt>Apellido materno</dt><dd>{{ $a->apellido_materno }}</dd>@endif
            @endunless
        </dl>
        @if ($expediente->tieneRepresentante())
            <h3 class="doc-seccion">Datos del representante</h3>
            <dl class="doc-datos">
                <dt>DNI</dt><dd>{{ $expediente->representante_dni }}</dd>
                <dt>Nombres</dt><dd>{{ $expediente->representante_nombres }}</dd>
                <dt>Apellido paterno</dt><dd>{{ $expediente->representante_apellido_paterno }}</dd>
                @if ($expediente->representante_apellido_materno)<dt>Apellido materno</dt><dd>{{ $expediente->representante_apellido_materno }}</dd>@endif
            </dl>
        @endif

        <h3 class="doc-seccion">Datos del trámite</h3>
        <dl class="doc-datos">
            <dt>Tipo de documento</dt><dd>{{ $expediente->tipoDocumento?->nombre ?? 'Por definir' }}</dd>
            <dt>Asunto</dt><dd>{{ $expediente->asunto }}</dd>
            @if ($expediente->folios)<dt>Folios</dt><dd>{{ $expediente->folios }}</dd>@endif
            @if ($expediente->codigo_propio)<dt>{{ $presencial ? 'Código del sello' : 'Tu código propio' }}</dt><dd><b>{{ $expediente->codigo_propio }}</b></dd>@endif
            <dt>Dirigido a</dt><dd>{{ $expediente->areaDestino->nombre }}</dd>
            <dt>Estado</dt><dd>@include('partials.estado', ['estado' => $expediente->estado])</dd>
        </dl>

        <h3 class="doc-seccion">Documentos adjuntos</h3>
        <dl class="doc-datos">
            <dt>{{ $presentados->count() > 1 ? 'Archivos' : 'Archivo' }}</dt>
            <dd>@forelse ($presentados as $d){{ $d->nombre_archivo }}@if (! $loop->last)<br>@endif @empty Documento físico (sin escaneo) @endforelse</dd>
        </dl>

        @if ($expediente->codigo_verificacion)
            @include('partials.verificacion', ['codigo' => $expediente->codigo_verificacion, 'qr' => true])
        @endif
        <p class="doc-nota">Consulte el estado de su trámite en {{ route('externo.consulta') }} con este número y su documento de identidad.</p>
    </article>
</main>
@if ($recienEnviado)
<script>
// Copia el número de expediente con un clic
document.getElementById('copiar-numero').addEventListener('click', async () => {
    const numero = document.getElementById('numero-expediente').textContent.trim(), aviso = document.getElementById('copiado');
    try { await navigator.clipboard.writeText(numero); aviso.textContent = 'Número copiado'; }
    catch (e) { aviso.textContent = 'Selecciona el número y cópialo con Ctrl+C'; }
    setTimeout(() => { aviso.textContent = ''; }, 3000);
});
</script>
@endif
@endsection
