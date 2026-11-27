{{-- CU11: Bandeja del área (Figura 15). Mejora de interfaz, paso 3: pestañas "Por atender" y
     "Para conocimiento" con su número, y filas que se vuelven tarjetas en el celular. --}}
@extends('layouts.app')
@section('titulo', 'Bandeja del área')
@section('contenido')
<div class="crumb">{{ auth()->user()->area->nombre }} › Bandeja</div>
<h1>Bandeja del área</h1>
<div class="tabs" style="margin-bottom:14px">
    <a class="{{ $vista === 'atender' ? 'on' : '' }}" href="{{ route('area.bandeja') }}">@include('partials.icono', ['i' => 'bandeja']) Por atender <span class="cuenta">{{ $expedientes->total() }}</span></a>
    <a class="{{ $vista === 'conocimiento' ? 'on' : '' }}" href="{{ route('area.bandeja', ['ver' => 'conocimiento']) }}">@include('partials.icono', ['i' => 'ojo']) Para conocimiento <span class="cuenta">{{ $copias->count() }}</span></a>
</div>

<section @if ($vista !== 'atender') hidden @endif>
    <div class="card" style="padding:0;overflow-x:auto">
        <table class="responsiva">
            <tr><th>Plazo</th><th>Expediente</th><th>Asunto</th><th>Administrado</th><th>Estado</th><th>Llegó</th><th></th></tr>
            @forelse ($expedientes as $e)
                @php $mov = $e->ultimoMovimiento(); @endphp
                <tr>
                    <td data-label="Plazo"><x-semaforo :expediente="$e" /></td>
                    <td data-label="Expediente"><a href="{{ route('area.show', $e) }}"><b>{{ $e->numero }}</b></a></td>
                    <td data-label="Asunto">{{ $e->asunto }}<br><span class="muted">{{ $e->tipoDocumento?->nombre ?? 'Por definir' }}</span></td>
                    <td data-label="Administrado">{{ $e->administrado->nombre_completo }}</td>
                    <td data-label="Estado">@include('partials.estado', ['estado' => $e->estado])</td>
                    <td data-label="Llegó">{{ $mov?->fecha_hora->format('d/m H:i') }}</td>
                    <td>
                        @if ($e->estado === \App\Models\Expediente::DERIVADO)
                            <form method="POST" action="{{ route('area.confirmar', $e) }}">@csrf<button class="btn">Confirmar recepción</button></form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="vacio">@include('partials.icono', ['i' => 'vacio'])No hay expedientes pendientes en tu área.</td></tr>
            @endforelse
        </table>
    </div>
    {{ $expedientes->links('partials.paginacion') }}
</section>

{{-- Derivación de uno a muchos: copias que otras áreas enviaron para conocimiento --}}
<section @if ($vista !== 'conocimiento') hidden @endif>
    <p class="muted" style="margin-bottom:10px">Copias que otras áreas enviaron a la tuya. No tienes que atenderlas: solo léelas y márcalas como leídas.</p>
    <div class="card" style="padding:0;overflow-x:auto">
        <table class="responsiva">
            <tr><th>Expediente</th><th>Asunto</th><th>Enviado por</th><th>Proveído</th><th>Llegó</th><th></th></tr>
            @forelse ($copias as $c)
                <tr>
                    <td data-label="Expediente"><a href="{{ route('area.show', $c->expediente) }}"><b>{{ $c->expediente->numero }}</b></a></td>
                    <td data-label="Asunto">{{ $c->expediente->asunto }}</td>
                    <td data-label="Enviado por">{{ $c->movimiento->areaOrigen?->nombre }}<br><span class="muted">{{ $c->usuario->nombre_completo }}</span></td>
                    <td data-label="Proveído">{{ $c->proveido }}</td>
                    <td data-label="Llegó">{{ $c->fecha_hora->format('d/m H:i') }}</td>
                    <td><form method="POST" action="{{ route('area.copias.leer', $c) }}">@csrf<button class="btn o">Marcar como leído</button></form></td>
                </tr>
            @empty
                <tr><td colspan="6" class="vacio">No tienes copias pendientes de leer.</td></tr>
            @endforelse
        </table>
    </div>
</section>
@endsection
