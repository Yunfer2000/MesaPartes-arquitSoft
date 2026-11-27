{{-- Catálogo de tipos de documento (CU04, RN-CU04-05). Issue #26: botón «Agregar tipo de documento» con formulario flotante. --}}
@extends('layouts.app')
@section('titulo', 'Tipos de documento')

@section('contenido')
<div class="crumb">Administración › Tipos de documento</div>
<div class="encabezado">
    <h1>Tipos de documento</h1>
    @if ($editar)
        <a class="btn" href="{{ route('admin.tipos.index') }}">+ Agregar tipo de documento</a>
    @else
        <button type="button" class="btn" data-abrir="form-tipo">+ Agregar tipo de documento</button>
    @endif
</div>
<div class="card" style="padding:0;overflow-x:auto">
    <table>
        <tr><th>Nombre</th><th>Descripción</th><th>Estado</th><th></th></tr>
        @foreach ($tipos as $t)
            <tr>
                <td>{{ $t->nombre }}</td><td>{{ $t->descripcion }}</td>
                <td><span class="tag {{ $t->activo ? 'tag-Recibido' : 'tag-Finalizado' }}">{{ $t->activo ? 'Activo' : 'Inactivo' }}</span></td>
                <td style="white-space:nowrap">
                    <a href="{{ route('admin.tipos.index', ['editar' => $t->id]) }}">Editar</a> ·
                    <form method="POST" action="{{ route('admin.tipos.estado', $t) }}" style="display:inline">@csrf @method('PATCH')
                        <button class="btn link">{{ $t->activo ? 'Desactivar' : 'Activar' }}</button></form>
                </td>
            </tr>
        @endforeach
    </table>
</div>
<p class="muted" style="margin-top:10px">Los tipos activos aparecen en el formulario público y en el registro presencial. Desactivar un tipo no cambia los expedientes que ya lo usan.</p>

{{-- Se abre solo al editar o si al guardar hubo errores, para que el Administrador los vea. --}}
<dialog class="modal" id="form-tipo" aria-labelledby="form-tipo-titulo" @if ($editar || $errors->any()) data-abierto @endif>
    <h2 id="form-tipo-titulo">{{ $editar ? 'Editar tipo de documento' : 'Nuevo tipo de documento' }}</h2>
    <form method="POST" action="{{ $editar ? route('admin.tipos.update', $editar) : route('admin.tipos.store') }}">
        @csrf @if ($editar) @method('PUT') @endif
        <label for="nombre">Nombre *</label><input id="nombre" name="nombre" value="{{ old('nombre', $editar?->nombre) }}" required maxlength="100" placeholder="Por ejemplo: Constancia">
        @error('nombre')<div class="error">{{ $message }}</div>@enderror
        <label for="descripcion">Descripción</label><input id="descripcion" name="descripcion" value="{{ old('descripcion', $editar?->descripcion) }}" maxlength="200" placeholder="Para qué sirve, en una frase">
        @error('descripcion')<div class="error">{{ $message }}</div>@enderror
        <div class="botones">
            @if ($editar)
                <a class="btn o" href="{{ route('admin.tipos.index') }}">Cancelar</a>
            @else
                <button type="button" class="btn o" data-cerrar>Cancelar</button>
            @endif
            <button class="btn">Guardar</button>
        </div>
    </form>
</dialog>
@endsection
