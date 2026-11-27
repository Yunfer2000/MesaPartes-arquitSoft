@extends('layouts.app')
@section('titulo', 'Auditoría')

@section('contenido')
<div class="crumb">Administración › Auditoría</div>
<h1>Registro de auditoría</h1>
<div class="card" style="padding:0;overflow-x:auto">
    <table>
        <tr><th>Fecha y hora</th><th>Usuario</th><th>Acción</th><th>Detalle</th><th>IP</th></tr>
        @forelse ($registros as $r)
            <tr><td>{{ $r->fecha_hora->format('d/m/Y H:i') }}</td><td>{{ $r->usuario?->nombre_completo }}</td><td>{{ $r->accion }}</td><td>{{ $r->detalle }}</td><td>{{ $r->ip }}</td></tr>
        @empty
            <tr><td colspan="5" class="muted">Sin registros.</td></tr>
        @endforelse
    </table>
</div>
{{ $registros->links('partials.paginacion') }}
@endsection
