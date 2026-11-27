{{-- CU04 Gestionar áreas (Figura 18). Issue #23: botón «Crear nueva área» con formulario flotante. --}}
@extends('layouts.app')
@section('titulo', 'Áreas')

@section('contenido')
<div class="crumb">Administración › Áreas</div>
<div class="encabezado">
    <h1>Áreas</h1>
    @if ($editar)
        <a class="btn" href="{{ route('admin.areas.index') }}">+ Crear nueva área</a>
    @else
        <button type="button" class="btn" data-abrir="form-area">+ Crear nueva área</button>
    @endif
</div>
<div class="card" style="padding:0;overflow-x:auto">
    <table>
        <tr><th>Área</th><th>Sigla</th><th>Depende de</th><th>Responsable</th><th>Estado</th><th></th></tr>
        @foreach ($areas as $a)
            <tr>
                <td>{{ $a->nombre }}</td><td>{{ $a->sigla }}</td><td>{{ $a->padre?->sigla }}</td><td>{{ $a->responsable?->nombre_completo }}</td>
                <td><span class="tag {{ $a->activa ? 'tag-Recibido' : 'tag-Finalizado' }}">{{ $a->activa ? 'Activa' : 'Inactiva' }}</span></td>
                <td style="white-space:nowrap">
                    <a href="{{ route('admin.areas.index', ['editar' => $a->id]) }}">Editar</a> ·
                    <form method="POST" action="{{ route('admin.areas.estado', $a) }}" style="display:inline">@csrf @method('PATCH')
                        <button class="btn link">{{ $a->activa ? 'Desactivar' : 'Activar' }}</button></form>
                </td>
            </tr>
        @endforeach
    </table>
</div>

{{-- Se abre solo al editar o si al guardar hubo errores, para que el Administrador los vea. --}}
<dialog class="modal" id="form-area" aria-labelledby="form-area-titulo" @if ($editar || $errors->any()) data-abierto @endif>
    <h2 id="form-area-titulo">{{ $editar ? 'Editar área' : 'Nueva área' }}</h2>
    <form method="POST" action="{{ $editar ? route('admin.areas.update', $editar) : route('admin.areas.store') }}">
        @csrf @if ($editar) @method('PUT') @endif
        <label for="nombre">Nombre *</label><input id="nombre" name="nombre" value="{{ old('nombre', $editar?->nombre) }}" required maxlength="100">
        @error('nombre')<div class="error">{{ $message }}</div>@enderror
        <label for="sigla">Sigla *</label><input id="sigla" name="sigla" value="{{ old('sigla', $editar?->sigla) }}" required maxlength="15" style="text-transform:uppercase">
        @error('sigla')<div class="error">{{ $message }}</div>@enderror
        <label for="area_padre_id">Depende de (organigrama)</label>
        <select id="area_padre_id" name="area_padre_id"><option value="">Ninguna</option>
            @foreach ($areas->where('activa', true) as $a)@unless ($editar && $a->id === $editar->id)<option value="{{ $a->id }}" @selected(old('area_padre_id', $editar?->area_padre_id) == $a->id)>{{ $a->nombre }}</option>@endunless @endforeach</select>
        @error('area_padre_id')<div class="error">{{ $message }}</div>@enderror
        <label for="responsable_id">Responsable</label>
        <select id="responsable_id" name="responsable_id"><option value="">Sin asignar</option>
            @foreach ($usuarios as $u)<option value="{{ $u->id }}" @selected(old('responsable_id', $editar?->responsable_id) == $u->id)>{{ $u->nombre_completo }}</option>@endforeach</select>
        @error('responsable_id')<div class="error">{{ $message }}</div>@enderror
        <div class="botones">
            @if ($editar)
                <a class="btn o" href="{{ route('admin.areas.index') }}">Cancelar</a>
            @else
                <button type="button" class="btn o" data-cerrar>Cancelar</button>
            @endif
            <button class="btn">Guardar</button>
        </div>
    </form>
</dialog>
@endsection
