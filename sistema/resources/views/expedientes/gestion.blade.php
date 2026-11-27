{{-- Revisión del expediente (Figura 12), derivación (Figura 14) y atención en el área (Figura 15). --}}
@extends('layouts.app')
@section('titulo', $expediente->numero)

@php
    use App\Models\Expediente as E;
    $u = auth()->user();
    $puedeOperar = $u->can('operar', $expediente);
    $esMesa = $u->tieneRol(\App\Models\Rol::MESA_DE_PARTES);
    $estado = $expediente->estado;
    $copias = $expediente->copias()->with('area')->get();
    $miCopia = $copias->first(fn ($c) => $c->area_id === $u->area_id && ! $c->leido);
@endphp

@section('contenido')
<div class="crumb">
    <a href="{{ $esMesa ? route('mesa.bandeja') : (Route::has('area.bandeja') && $u->tieneRol(\App\Models\Rol::AREA) ? route('area.bandeja') : route('inicio')) }}">Bandeja</a> › {{ $expediente->numero }}
</div>
<div class="row">
    <div style="flex:2;min-width:320px">
        <div class="card">
            <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap">
                <h1 style="margin:0">{{ $expediente->numero }}</h1>
                <div>@include('partials.estado', ['estado' => $estado])</div>
            </div>
            <table style="margin-top:10px">
                @if ($expediente->tieneRepresentante())
                    {{-- Persona jurídica: la entidad y el representante que presentó este expediente --}}
                    <tr><td class="muted" style="width:150px">Administrado</td><td>{{ $expediente->administrado->razon_social }} · RUC {{ $expediente->administrado->numero_documento }} · Persona jurídica</td></tr>
                    <tr><td class="muted">Representante</td><td>{{ $expediente->representante_nombre }} · DNI {{ $expediente->representante_dni }}</td></tr>
                    <tr><td class="muted">Contacto</td><td>{{ $expediente->representante_correo ?? 'Sin correo' }} · {{ $expediente->representante_celular ?? 'Sin teléfono' }}</td></tr>
                @else
                    <tr><td class="muted" style="width:150px">Administrado</td><td>{{ $expediente->administrado->nombre_completo }} · {{ $expediente->administrado->tipo_doc_identidad }} {{ $expediente->administrado->numero_documento }} · {{ $expediente->administrado->tipo_administrado }}</td></tr>
                    <tr><td class="muted">Contacto</td><td>{{ $expediente->administrado->correo ?? 'Sin correo' }} · {{ $expediente->administrado->telefono ?? 'Sin teléfono' }}</td></tr>
                @endif
                <tr><td class="muted">Tipo / Dirigido a</td><td>{{ $expediente->tipoDocumento?->nombre ?? 'Por definir' }} · {{ $expediente->areaDestino->nombre }}</td></tr>
                <tr><td class="muted">Asunto</td><td>{{ $expediente->asunto }}@if ($expediente->detalle)<br><span class="muted">{{ $expediente->detalle }}</span>@endif</td></tr>
                <tr><td class="muted">Presentado</td><td>{{ $expediente->fecha_presentacion->format('d/m/Y H:i') }} ({{ mb_strtolower($expediente->modalidad) }})</td></tr>
                <tr><td class="muted">Recibido</td><td>{{ $expediente->fecha_recepcion?->format('d/m/Y H:i') ?? 'Pendiente' }}</td></tr>
                <tr><td class="muted">Folios / N.° doc.</td><td>{{ $expediente->folios ?? 'Por definir' }} · {{ $expediente->nro_documento ?? 'Sin número' }}</td></tr>
                <tr><td class="muted">Ubicación</td><td>{{ $areaActual?->nombre }}</td></tr>
                @if ($copias->isNotEmpty())
                    <tr><td class="muted">Con copia a</td><td>
                        @foreach ($copias as $c){{ $c->area->nombre }} <span class="muted">({{ $c->leido ? 'leído' : 'sin leer' }})</span>@if (! $loop->last) · @endif @endforeach
                    </td></tr>
                @endif
            </table>
            <h2 style="margin-top:16px">Documentos</h2>
            @include('expedientes.documentos', ['documentos' => $expediente->documentos])
            @foreach ($expediente->observaciones as $obs)
                <div class="alert" style="margin-top:12px"><b>Observación:</b> {{ $obs->motivo }} @if ($obs->detalle)· {{ $obs->detalle }}@endif
                    <span class="muted">({{ $obs->fecha->format('d/m/Y H:i') }})</span>
                    @if ($obs->subsanacion)<br><b>Subsanado:</b> {{ $obs->subsanacion->descripcion }} <span class="muted">({{ $obs->subsanacion->fecha->format('d/m/Y H:i') }})</span>@endif
                </div>
            @endforeach
            @foreach ($expediente->respuestas as $r)
                <div class="ok" style="margin-top:12px"><b>Respuesta:</b> {{ $r->contenido }} <span class="muted">({{ $r->fecha->format('d/m/Y H:i') }})</span></div>
                @include('expedientes.documentos', ['documentos' => $r->documentos])
                @if ($r->codigo_verificacion)
                    @include('partials.verificacion', ['codigo' => $r->codigo_verificacion, 'firmante' => \App\Services\Verificacion::firmante($r)])
                <div style="margin-top:6px"><a class="btn o" href="{{ route('respuestas.pdf', $r) }}">Descargar respuesta en PDF</a></div>
                @endif
            @endforeach
        </div>

        @if ($miCopia)
            <div class="card" style="margin-top:16px">
                <form method="POST" action="{{ route('area.copias.leer', $miCopia) }}" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">@csrf <button class="btn">Marcar como leído</button>
                    <span class="muted">Tu área recibió este expediente con copia, para conocimiento. No necesitas atenderlo.</span></form>
            </div>
        @endif

        @if ($puedeOperar)
            {{-- CU12: confirmar recepción es un solo botón, va aparte --}}
            @if (! $esMesa && $estado === E::DERIVADO)
                <div class="card" style="margin-top:16px">
                    <form method="POST" action="{{ route('area.confirmar', $expediente) }}" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">@csrf <button class="btn">Confirmar recepción</button>
                        <span class="muted">Confirma que el documento llegó a tu área.</span></form>
                </div>
            @endif
            @php
                // Mejora de interfaz, paso 1: un solo formulario abierto a la vez
                $acciones = array_filter([
                    'recepcionar' => $esMesa && in_array($estado, [E::PRESENTADO, E::SUBSANADO]) ? 'Recepcionar' : null,
                    'derivar' => in_array($estado, [E::RECIBIDO, E::EN_ATENCION]) ? 'Derivar' : null,
                    'observar' => $esMesa && in_array($estado, [E::PRESENTADO, E::SUBSANADO, E::RECIBIDO]) ? 'Observar' : null,
                    'avance' => ! $esMesa && $estado === E::EN_ATENCION ? 'Registrar avance' : null,
                    'responder' => ! $esMesa && $estado === E::EN_ATENCION ? 'Responder y finalizar' : null,
                ]);
                // Si un formulario volvió con errores, se abre ese
                $camposDe = ['recepcionar' => ['tipo_documento_id', 'folios', 'nro_documento'], 'derivar' => ['area_id', 'subarea_id', 'proveido', 'copias', 'copias.*'],
                    'observar' => ['motivo', 'detalle'], 'avance' => ['nota'], 'responder' => ['contenido', 'documento']];
                $abierta = collect(array_keys($acciones))->first(fn ($a) => $errors->hasAny($camposDe[$a])) ?? array_key_first($acciones);
            @endphp
            @if ($acciones)
                <div class="card acciones-exp" style="margin-top:16px">
                    <h2>¿Qué deseas hacer?</h2>
                    <div class="acciones" role="group" aria-label="Acciones del expediente">
                        @foreach ($acciones as $clave => $texto)
                            <button type="button" class="accion {{ $clave === $abierta ? 'on' : '' }}" data-panel="panel-{{ $clave }}" aria-controls="panel-{{ $clave }}" aria-expanded="{{ $clave === $abierta ? 'true' : 'false' }}">{{ $texto }}</button>
                        @endforeach
                    </div>
                    @foreach ($acciones as $clave => $texto)
                        <section id="panel-{{ $clave }}" class="panel-accion" @unless ($clave === $abierta) data-oculto @endunless>
                            @if ($clave === 'recepcionar')
                                @include('mesa.recepcionar')
                            @else
                                @include('expedientes.acciones.'.$clave)
                            @endif
                        </section>
                    @endforeach
                </div>
                <script>
                    (function () {
                        // Sin JavaScript se ven todos los formularios; con JavaScript, solo el elegido
                        const botones = document.querySelectorAll('.acciones .accion');
                        const paneles = document.querySelectorAll('.panel-accion');
                        const mostrar = (id) => {
                            paneles.forEach(p => { p.hidden = p.id !== id; });
                            botones.forEach(b => { const on = b.dataset.panel === id; b.classList.toggle('on', on); b.setAttribute('aria-expanded', on); });
                        };
                        paneles.forEach(p => { p.hidden = p.hasAttribute('data-oculto'); });
                        botones.forEach(b => b.addEventListener('click', () => mostrar(b.dataset.panel)));
                        // Observar y Finalizar no se pueden deshacer: se pide confirmación
                        document.querySelectorAll('form[data-confirmar]').forEach(f => f.addEventListener('submit', (e) => {
                            if (!confirm(f.dataset.confirmar)) e.preventDefault();
                        }));
                    })();
                </script>
            @endif
        @endif
    </div>
    <div class="card" style="flex:1;min-width:260px">
        <h2>Historial</h2>
        @include('expedientes.historial')
    </div>
</div>
@endsection
