{{-- CU08: Bandeja de Mesa de Partes (Figura 12). --}}
@extends('layouts.app')
@section('titulo', 'Bandeja de Mesa de Partes')

@section('contenido')
<div class="crumb">Mesa de Partes › Bandeja de entrada</div>
<h1>Bandeja de Mesa de Partes</h1>
<form class="row" style="margin-bottom:12px;align-items:center" method="GET">
    <div class="tabs">
        <a class="{{ $vista === 'pendientes' ? 'on' : '' }}" href="{{ route('mesa.bandeja') }}">@include('partials.icono', ['i' => 'bandeja']) Por recepcionar</a>
        <a class="{{ $vista === 'recibidos' ? 'on' : '' }}" href="{{ route('mesa.bandeja', ['ver' => 'recibidos']) }}">@include('partials.icono', ['i' => 'constancia']) Recibidos por derivar</a>
    </div>
    <input type="hidden" name="ver" value="{{ $vista }}">
    <div class="buscador">@include('partials.icono', ['i' => 'buscar'])<input name="q" value="{{ request('q') }}" placeholder="Buscar por número, código o asunto"></div>
    <button class="btn o">Buscar</button>
</form>
<div class="card" style="padding:0;overflow-x:auto">
    <table class="responsiva">
        <tr><th>Plazo</th><th>Expediente</th><th>Asunto</th><th>Administrado</th><th>Dirigido a</th><th>Estado</th><th>Presentado</th></tr>
        @forelse ($expedientes as $e)
            <tr>
                <td data-label="Plazo"><x-semaforo :expediente="$e" /></td>
                <td data-label="Expediente"><a href="{{ route('mesa.show', $e) }}"><b>{{ $e->numero }}</b></a><br><span class="muted">{{ $e->modalidad }}</span></td>
                <td data-label="Asunto">{{ $e->asunto }}<br><span class="muted">{{ $e->tipoDocumento?->nombre ?? 'Por definir' }}</span></td>
                <td data-label="Administrado">{{ $e->administrado->nombre_completo }}</td>
                <td data-label="Dirigido a">{{ $e->areaDestino->nombre }}</td>
                <td data-label="Estado">@include('partials.estado', ['estado' => $e->estado])</td>
                <td data-label="Presentado">{{ $e->fecha_presentacion->format('d/m/Y H:i') }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="vacio">@include('partials.icono', ['i' => 'vacio'])No hay expedientes en esta bandeja.</td></tr>
        @endforelse
    </table>
</div>
<p class="muted" style="margin-top:8px">Ordenados del más antiguo al más reciente · {{ $expedientes->total() }} expediente(s)</p>
{{ $expedientes->links('partials.paginacion') }}
@endsection
