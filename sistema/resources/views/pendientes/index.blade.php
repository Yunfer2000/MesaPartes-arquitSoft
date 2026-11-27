{{-- CU18 Monitorear plazos y pendientes: panel del Administrador y de la Dirección General. --}}
@extends('layouts.app')
@section('titulo', 'Pendientes')

@section('contenido')
<div class="crumb">Pendientes</div>
<h1>Expedientes pendientes</h1>

{{-- #32: un aviso por cada área con más expedientes en rojo que el límite de config/plazos.php --}}
@foreach ($alertas as $alerta)
    <div class="alert alert-rojo" role="alert">
        <b>{{ $alerta['area']->nombre }}</b> tiene <b>{{ $alerta['total'] }}</b> expedientes en rojo (más de {{ config('plazos.alerta_area') }}).
        <a href="{{ route('pendientes', ['area_id' => $alerta['area']->id]) }}">Ver expedientes</a>
    </div>
@endforeach

{{-- #32: tarjetas de plazos con nombre por significado (no solo por color), ícono y proporción del total. --}}
@php
    $totalPendientes = $contadores->sum();
    $tarjetas = [
        ['clave' => 'rojo', 'titulo' => 'Atrasados', 'icono' => 'alerta', 'plazo' => config('plazos.rojo').' días o más'],
        ['clave' => 'ambar', 'titulo' => 'Por vencer', 'icono' => 'reloj', 'plazo' => config('plazos.ambar').' a '.(config('plazos.rojo') - 1).' días'],
        ['clave' => 'verde', 'titulo' => 'A tiempo', 'icono' => 'check', 'plazo' => 'Menos de '.config('plazos.ambar').' días'],
    ];
@endphp
<div class="contadores">
    @foreach ($tarjetas as $t)
        @php $n = $contadores[$t['clave']] ?? 0; @endphp
        <div class="card plazo plazo-{{ $t['clave'] }}">
            <div class="plazo-titulo">@include('partials.icono', ['i' => $t['icono']]) {{ $t['titulo'] }}</div>
            <div class="plazo-num">
                <b>{{ $n }}</b>
                @if ($totalPendientes)<span class="muted">de {{ $totalPendientes }} · {{ round($n / $totalPendientes * 100) }} %</span>@endif
            </div>
            <div class="muted">{{ $t['plazo'] }}</div>
            @if ($t['clave'] === 'rojo' && $n > 0)<span class="plazo-etiqueta">Requieren atención</span>@endif
        </div>
    @endforeach
</div>

@php
    $areaActual = $areas->firstWhere('id', request('area_id'));
    $hayFiltros = $areaActual || $estadoActual;
@endphp

<form class="card row" method="GET" style="margin-bottom:16px;align-items:flex-end">
    <div class="col">
        <label for="filtro-area">Área donde está</label>
        <select id="filtro-area" name="area_id">
            <option value="">Todas</option>
            @foreach ($areas as $a)
                <option value="{{ $a->id }}" @selected(request('area_id') == $a->id)>{{ $a->nombre }}</option>
            @endforeach
        </select>
    </div>
    <div class="col">
        <label for="filtro-estado">Estado</label>
        <select id="filtro-estado" name="estado">
            <option value="">Todos</option>
            @foreach ($estados as $estado)
                <option value="{{ $estado }}" @selected($estadoActual === $estado)>{{ $estado }}</option>
            @endforeach
        </select>
    </div>
    <div><button class="btn">Filtrar</button> <a class="btn o" href="{{ route('pendientes') }}">Limpiar</a></div>
</form>

<div class="card" style="padding:0;overflow-x:auto">
    <table>
        <tr><th>Plazo</th><th>Expediente</th><th>Asunto</th><th>Administrado</th><th>Está en</th><th>Estado</th><th>Desde</th></tr>
        @forelse ($pendientes as $e)
            @php $mov = $e->movimientos->last(); @endphp
            <tr>
                <td><x-semaforo :expediente="$e" /></td>
                <td>@can('view', $e)<a href="{{ route('expedientes.show', $e) }}"><b>{{ $e->numero }}</b></a>@else<b>{{ $e->numero }}</b>@endcan</td>
                <td>{{ $e->asunto }}</td>
                <td>{{ $e->administrado->nombre_completo }}</td>
                <td>{{ $mov?->areaDestino?->nombre }}</td>
                <td>@include('partials.estado', ['estado' => $e->estado])</td>
                <td>{{ ($mov?->fecha_hora ?? $e->fecha_presentacion)->format('d/m/Y') }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="vacio">@include('partials.icono', ['i' => 'vacio'])
                @if ($hayFiltros)
                    No hay expedientes pendientes{{ $estadoActual ? ' en estado ' : '' }}@if ($estadoActual)<b>{{ $estadoActual }}</b>@endif{{ $areaActual ? ' en ' : '' }}@if ($areaActual)<b>{{ $areaActual->nombre }}</b>@endif.
                @else
                    No hay expedientes pendientes.
                @endif
            </td></tr>
        @endforelse
    </table>
</div>
<p class="muted" style="margin-top:8px">Ordenados del que más días lleva al que menos · {{ $pendientes->count() }} expediente(s)
    @if ($hayFiltros) · Estado: <b>{{ $estadoActual ?? 'Todos' }}</b> · Área: <b>{{ $areaActual?->nombre ?? 'Todas' }}</b>@endif</p>
@endsection
