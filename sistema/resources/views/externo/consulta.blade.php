{{-- CU07 Consultar seguimiento sin cuenta: N.° de expediente o código propio + documento de identidad. --}}
@extends('layouts.base')
@section('titulo', 'Consultar trámite')

@section('cuerpo')
<main class="main publico formulario consulta">
    <nav class="ruta" aria-label="Estás en">
        <a href="{{ route('inicio') }}">@include('partials.icono', ['i' => 'inicio']) Inicio</a>
        <span aria-hidden="true">›</span>
        <b aria-current="page">Consultar trámite</b>
    </nav>
    <h1 class="titulo-tramite"><span class="titulo-ico">@include('partials.icono', ['i' => 'buscar'])</span> Consultar trámite</h1>
    <p class="intro">Revisa en qué área está tu trámite, sus observaciones y la respuesta.</p>
    @include('partials.mensajes')
    <form class="card" method="POST" action="{{ route('externo.consultar') }}">
        @csrf
        <label for="numero" style="margin-top:0">N.° de expediente</label>
        <input id="numero" name="numero" value="{{ old('numero') }}" required maxlength="20" placeholder="EXP-2026-000001" autocomplete="off" aria-describedby="ayuda-numero" style="text-transform:uppercase">
        <div class="ayuda ayuda-icono" id="ayuda-numero">@include('partials.icono', ['i' => 'info']) Está en tu constancia y en el correo que te enviamos al presentar. También puedes usar tu código propio o el código del sello.</div>
        @error('numero')<div class="error">{{ $message }}</div>@enderror

        <label for="numero_documento">N.° de documento del remitente</label>
        {{-- Sin teclado solo numérico: el carné de extranjería y el pasaporte llevan letras --}}
        <input id="numero_documento" name="numero_documento" value="{{ old('numero_documento') }}" required maxlength="12" autocomplete="off" placeholder="DNI, carné de extranjería, pasaporte o RUC" aria-describedby="ayuda-documento">
        <div class="ayuda" id="ayuda-documento">El mismo documento con el que presentaste el trámite.</div>
        @error('numero_documento')<div class="error">{{ $message }}</div>@enderror

        <div style="margin-top:20px"><button class="btn block">@include('partials.icono', ['i' => 'buscar']) Consultar</button></div>
        <p class="ayuda" style="text-align:center;margin-top:14px">¿Aún no presentaste tu trámite? <a href="{{ route('externo.create') }}">Preséntalo aquí</a></p>
    </form>
</main>
@endsection
