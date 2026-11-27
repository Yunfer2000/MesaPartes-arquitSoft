{{-- Inicio público de la Mesa de Partes Virtual. --}}
@extends('layouts.base')
@section('titulo', 'Inicio')

@php
    // Foto de fondo opcional: basta con copiar una foto del instituto a public/img/fondo.jpg
    $foto = file_exists(public_path('img/fondo.jpg'))
        ? "background-image:linear-gradient(160deg,rgba(44,44,92,.92),rgba(27,27,56,.88)),url('".asset('img/fondo.jpg')."')"
        : null;
@endphp

@section('cuerpo')
<section class="portada {{ $foto ? 'foto' : '' }}" @if ($foto) style="{{ $foto }}" @endif>
    <div class="portada-in">
        <div class="sello"><img src="{{ asset('img/escudo.png') }}" alt="Escudo del instituto"></div>
        <span class="eyebrow">IESTP Manuel Antonio Hierro Pozo</span>
        <h1>Mesa de Partes Virtual</h1>
        <p>Presenta tus solicitudes y documentos las 24 horas, sin crear una cuenta y sin ir al instituto. Recibe tu número de expediente al instante y sigue tu trámite.</p>
    </div>
</section>
<main class="main publico" style="margin-top:-56px">
    <div class="opciones">
        <a class="card opcion" href="{{ route('externo.create') }}">
            @include('partials.icono', ['i' => 'nuevo'])
            <b>Presentar trámite</b>
            <span>Llena el formulario con tu DNI, adjunta tu documento en PDF y envíalo.</span>
            <em>Empezar →</em>
        </a>
        <a class="card opcion" href="{{ route('externo.consulta') }}">
            @include('partials.icono', ['i' => 'buscar'])
            <b>Consultar trámite</b>
            <span>Revisa en qué área está tu expediente, sus observaciones y la respuesta.</span>
            <em>Consultar →</em>
        </a>
    </div>
    <div class="pasos-info">
        <div><span class="num">1</span> Ten listo tu DNI y tu documento en PDF.</div>
        <div><span class="num">2</span> Envía el formulario y guarda tu número de expediente.</div>
        <div><span class="num">3</span> Consulta el avance con ese número y tu DNI.</div>
    </div>
    <p class="muted" style="text-align:center;margin-top:22px">Lo enviado fuera del horario de atención se recepciona el siguiente día hábil.
        @unless (config('app.staff_domain'))· ¿Trabajas en el instituto? <a href="{{ route('login') }}">Acceso del personal</a>@endunless</p>
</main>
@endsection
