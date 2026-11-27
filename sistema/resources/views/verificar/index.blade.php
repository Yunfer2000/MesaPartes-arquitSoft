{{-- CU19 Verificar documento: la persona escribe el código que trae su constancia o respuesta. --}}
@extends('layouts.base')
@section('titulo', 'Verificar documento')

@section('cuerpo')
<main class="main publico" style="max-width:520px">
    <div class="crumb"><a href="{{ route('inicio') }}">Inicio</a> › Verificar documento</div>
    <h1>Verificar documento</h1>
    <form class="card" method="GET" action="{{ route('verificar') }}">
        <label for="codigo" style="margin-top:0">Código de verificación *</label>
        <input id="codigo" name="codigo" required maxlength="20" placeholder="Ejemplo: K7QM-3XWD-9PTA" autocomplete="off" style="text-transform:uppercase">
        <div style="margin-top:18px"><button class="btn block">@include('partials.icono', ['i' => 'buscar']) Verificar</button></div>
        <p class="muted" style="margin-top:14px">El código aparece en la constancia de presentación, en el cargo de recepción y junto a cada respuesta del instituto. También puedes escanear el código QR de la constancia con tu celular.</p>
    </form>
</main>
@endsection
