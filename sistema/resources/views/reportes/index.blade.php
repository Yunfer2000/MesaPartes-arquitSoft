{{-- CU15 Generar reportes (Figura 17). --}}
@extends('layouts.app')
@section('titulo', 'Reportes')

@section('contenido')
<div class="crumb no-print">Reportes</div>
<h1>Reporte de Mesa de Partes</h1>
<form class="card no-print" method="GET">
    <div class="row" style="align-items:flex-end">
        <div class="col"><label>Desde</label><input type="date" name="desde" value="{{ $desde->toDateString() }}"></div>
        <div class="col"><label>Hasta</label><input type="date" name="hasta" value="{{ $hasta->toDateString() }}"></div>
        @unless (auth()->user()->tieneRol(\App\Models\Rol::AREA))
            <div class="col"><label>Área</label><select name="area_id"><option value="">Todas</option>@foreach ($areas as $a)<option value="{{ $a->id }}" @selected($areaId == $a->id)>{{ $a->nombre }}</option>@endforeach</select></div>
        @endunless
        <div><button class="btn">Generar</button></div>
    </div>
    @foreach ($errors->all() as $e)<div class="error">{{ $e }}</div>@endforeach
</form>
<p class="muted" style="margin:12px 0">Periodo: {{ $desde->format('d/m/Y') }} al {{ $hasta->format('d/m/Y') }}@if ($areaId) · {{ $areas->firstWhere('id', $areaId)?->nombre }}@endif</p>

@php
    // #28: cada indicador lleva el color de su estado.
    $colorEstado = ['Presentados' => 'Presentado', 'Recibidos' => 'Recibido', 'Derivados' => 'Derivado',
        'Observados' => 'Observado', 'Finalizados' => 'Finalizado', 'Pendientes' => 'Pendientes'];
@endphp
<div class="row">
    @foreach ($totales as $nombre => $valor)
        <div class="card stat stat-{{ $colorEstado[$nombre] ?? 'otro' }}">
            <span class="stat-circulo">{{ $valor }}</span>
            <span class="stat-nombre">{{ $nombre }}</span>
        </div>
    @endforeach
</div>

<div class="row" style="margin-top:16px">
    <div class="card" style="flex:1;min-width:300px">
        <h2>Derivaciones por área destino</h2>
        @php $max = max(1, $porArea->max('total') ?? 1); @endphp
        @forelse ($porArea as $fila)
            <div style="display:flex;align-items:center;margin:8px 0;gap:8px">
                <span style="width:170px;flex:none">{{ $fila['area'] }}</span>
                <div class="barra" style="width:{{ round($fila['total'] / $max * 100) }}%;max-width:60%"></div>
                <span>{{ $fila['total'] }}</span>
            </div>
        @empty
            <p class="muted">Sin derivaciones en el periodo.</p>
        @endforelse
    </div>
    <div class="card" style="flex:1;min-width:280px">
        {{-- Mejora: se quitó «Estado actual de los expedientes», que repetía lo de los contadores de arriba --}}
        <h2>Tiempo promedio de atención</h2>
        <table><tr><th>Área</th><th>Días</th></tr>
            @forelse ($tiempos as $area => $dias)<tr><td>{{ $area }}</td><td>{{ number_format($dias, 1, ',', '') }}</td></tr>@empty<tr><td colspan="2" class="muted">Sin expedientes finalizados.</td></tr>@endforelse
        </table>
    </div>
</div>
<div class="no-print" style="margin-top:16px">
    <button class="btn o" onclick="window.print()">Imprimir o guardar en PDF</button>
    <a class="btn o" href="{{ request()->fullUrlWithQuery(['formato' => 'excel']) }}">Descargar en Excel</a>
</div>
@endsection
