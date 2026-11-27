{{-- CU14 Buscar expedientes (Figura 16). Mejora: «Historial de expedientes» con los 15 últimos al entrar. --}}
@extends('layouts.app')
@section('titulo', 'Historial de expedientes')

@section('contenido')
<div class="crumb">Historial de expedientes</div>
<h1>Historial de expedientes</h1>
{{-- Issue #30: búsqueda básica (N.° de expediente o DNI) y los demás filtros detrás del botón «Búsqueda por filtros» --}}
<form class="card busqueda" method="GET">
    <label for="q">N.° de expediente o DNI</label>
    <div class="busqueda-basica">
        <input id="q" name="q" value="{{ request('q') }}" maxlength="20" placeholder="Ejemplo: 000012 o 70451236" autofocus>
        <button class="btn">@include('partials.icono', ['i' => 'buscar']) Buscar</button>
    </div>
    <div class="ayuda">También acepta carné de extranjería, RUC o el DNI del representante.</div>

    <details class="filtros" id="filtros"{{ $conFiltros ? ' open' : '' }}>
        <summary class="btn o">@include('partials.icono', ['i' => 'buscar']) Búsqueda por filtros</summary>
        <div class="row">
            <div class="col"><label for="administrado">Administrado (DNI o nombre)</label><input id="administrado" name="administrado" value="{{ request('administrado') }}"></div>
            <div class="col"><label for="asunto">Palabra en el asunto</label><input id="asunto" name="asunto" value="{{ request('asunto') }}"></div>
            <div class="col"><label for="desde">Desde</label><input id="desde" type="date" name="desde" value="{{ request('desde') }}"></div>
            <div class="col"><label for="hasta">Hasta</label><input id="hasta" type="date" name="hasta" value="{{ request('hasta') }}"></div>
        </div>
        <div class="row">
            <div class="col"><label for="area_id">Ubicación (área)</label><select id="area_id" name="area_id"><option value="">Todas</option>@foreach ($areas as $a)<option value="{{ $a->id }}" @selected(request('area_id') == $a->id)>{{ $a->nombre }}</option>@endforeach</select></div>
            <div class="col"><label for="tipo_documento_id">Tipo de documento</label><select id="tipo_documento_id" name="tipo_documento_id"><option value="">Todos</option>@foreach ($tipos as $t)<option value="{{ $t->id }}" @selected(request('tipo_documento_id') == $t->id)>{{ $t->nombre }}</option>@endforeach</select></div>
            <div class="col"><label for="estado">Estado</label><select id="estado" name="estado"><option value="">Todos</option>@foreach (\App\Models\Expediente::ESTADOS as $e)<option @selected(request('estado') === $e)>{{ $e }}</option>@endforeach</select></div>
        </div>
        <div style="margin-top:14px"><button class="btn">@include('partials.icono', ['i' => 'buscar']) Buscar con filtros</button> <a class="btn o" href="{{ route('busqueda') }}">Limpiar filtros</a></div>
    </details>
    @foreach ($errors->all() as $e)<div class="error">{{ $e }}</div>@endforeach
</form>

@php $lista = $resultados ?? $ultimos; @endphp
<div class="card" style="margin-top:16px;padding:0;overflow-x:auto">
    @unless ($resultados)<h2 style="margin:16px 16px 4px">Últimos {{ \App\Http\Controllers\BusquedaController::ULTIMOS }} expedientes</h2>@endunless
    <table class="responsiva">
        <tr><th>Expediente</th><th>Asunto</th><th>Administrado</th><th>Ubicación</th><th>Estado</th><th>Presentado</th></tr>
        @forelse ($lista as $e)
            <tr>
                <td data-label="Expediente"><a href="{{ route('expedientes.show', $e) }}"><b>{{ $e->numero }}</b></a></td>
                <td data-label="Asunto">{{ $e->asunto }}<br><span class="muted">{{ $e->tipoDocumento?->nombre ?? 'Por definir' }}</span></td>
                <td data-label="Administrado">{{ $e->administrado->nombre_completo }}</td>
                <td data-label="Ubicación">{{ $e->areaActual()?->nombre }}</td>
                <td data-label="Estado">@include('partials.estado', ['estado' => $e->estado])</td>
                <td data-label="Presentado">{{ $e->fecha_presentacion->format('d/m/Y') }}</td>
            </tr>
        @empty
            {{-- 3a. Sin resultados --}}
            <tr><td colspan="6" class="vacio">@include('partials.icono', ['i' => 'buscar']){{ $resultados ? 'No se encontraron expedientes con esos filtros.' : 'Todavía no hay expedientes.' }}</td></tr>
        @endforelse
    </table>
</div>
@if ($resultados)
    <p class="muted" style="margin-top:8px">{{ $resultados->total() }} resultado(s) · solo se muestran los expedientes que tu rol puede ver.</p>
    {{ $resultados->links('partials.paginacion') }}
@else
    <p class="muted" style="margin-top:8px">Del más reciente al más antiguo · solo se muestran los expedientes que tu rol puede ver.</p>
@endif
@endsection
