{{-- CU03 Gestionar usuarios y roles (Figura 18). Issue #25: botón «Crear nuevo usuario» con formulario flotante. --}}
@extends('layouts.app')
@section('titulo', 'Usuarios')

@section('contenido')
<div class="crumb">Administración › Usuarios</div>
<div class="encabezado">
    <h1>Usuarios internos</h1>
    @if ($editar)
        <a class="btn" href="{{ route('admin.usuarios.index') }}">+ Crear nuevo usuario</a>
    @else
        <button type="button" class="btn" data-abrir="form-usuario">+ Crear nuevo usuario</button>
    @endif
</div>
<div class="card" style="padding:0;overflow-x:auto">
    <table>
        <tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Área</th><th>Estado</th><th></th></tr>
        @foreach ($usuarios as $u)
            <tr>
                <td>{{ $u->nombre_completo }}</td><td>{{ $u->correo }}</td><td>{{ $u->rol->nombre }}</td><td>{{ $u->area?->nombre }}</td>
                <td><span class="tag {{ $u->activo ? 'tag-Recibido' : 'tag-Finalizado' }}">{{ $u->activo ? 'Activo' : 'Inactivo' }}</span></td>
                <td style="white-space:nowrap">
                    <a href="{{ route('admin.usuarios.index', ['editar' => $u->id]) }}">Editar</a> ·
                    <form method="POST" action="{{ route('admin.usuarios.estado', $u) }}" style="display:inline">@csrf @method('PATCH')
                        <button class="btn link">{{ $u->activo ? 'Desactivar' : 'Activar' }}</button></form>
                </td>
            </tr>
        @endforeach
    </table>
</div>

{{-- Se abre solo al editar o si al guardar hubo errores, para que el Administrador los vea. --}}
<dialog class="modal" id="form-usuario" aria-labelledby="form-usuario-titulo" @if ($editar || $errors->any()) data-abierto @endif>
    <h2 id="form-usuario-titulo">{{ $editar ? 'Editar usuario' : 'Nuevo usuario' }}</h2>
    <form method="POST" action="{{ $editar ? route('admin.usuarios.update', $editar) : route('admin.usuarios.store') }}">
        @csrf @if ($editar) @method('PUT') @endif
        <div class="row">
            <div class="col"><label for="nombres">Nombres *</label><input id="nombres" name="nombres" value="{{ old('nombres', $editar?->nombres) }}" required maxlength="100">
                @error('nombres')<div class="error">{{ $message }}</div>@enderror</div>
            <div class="col"><label for="apellidos">Apellidos *</label><input id="apellidos" name="apellidos" value="{{ old('apellidos', $editar?->apellidos) }}" required maxlength="100">
                @error('apellidos')<div class="error">{{ $message }}</div>@enderror</div>
        </div>
        <label for="correo">Correo *</label><input id="correo" type="email" name="correo" value="{{ old('correo', $editar?->correo) }}" required maxlength="120">
        @error('correo')<div class="error">{{ $message }}</div>@enderror
        <div class="row">
            <div class="col"><label for="rol_id">Rol *</label>
                <select id="rol_id" name="rol_id">@foreach ($roles as $r)<option value="{{ $r->id }}" @selected(old('rol_id', $editar?->rol_id) == $r->id)>{{ $r->nombre }}</option>@endforeach</select></div>
            <div class="col"><label for="area_id">Área</label>
                <select id="area_id" name="area_id"><option value="">Ninguna</option>@foreach ($areas as $a)<option value="{{ $a->id }}" @selected(old('area_id', $editar?->area_id) == $a->id)>{{ $a->nombre }}</option>@endforeach</select></div>
        </div>
        @error('rol_id')<div class="error">{{ $message }}</div>@enderror
        @error('area_id')<div class="error">{{ $message }}</div>@enderror
        <label for="contrasena">Contraseña {{ $editar ? '(déjala vacía para no cambiarla)' : '* (mínimo 8 caracteres)' }}</label>
        <input id="contrasena" type="password" name="contrasena" autocomplete="new-password" @unless ($editar) required minlength="8" @endunless>
        @error('contrasena')<div class="error">{{ $message }}</div>@enderror
        <p class="muted" style="margin-top:10px">Mesa de Partes y Área necesitan un área. Cada cambio queda en la auditoría.</p>
        <div class="botones">
            @if ($editar)
                <a class="btn o" href="{{ route('admin.usuarios.index') }}">Cancelar</a>
            @else
                <button type="button" class="btn o" data-cerrar>Cancelar</button>
            @endif
            <button class="btn">Guardar</button>
        </div>
    </form>
</dialog>
@endsection
