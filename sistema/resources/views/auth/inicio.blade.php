{{-- Figura 9: Inicio / Inicio de sesión (CU02). --}}
@extends('layouts.base')
@section('titulo', 'Iniciar sesión')

@php
    // Foto de fondo opcional: basta con copiar una foto del instituto a public/img/fondo.jpg
    $foto = file_exists(public_path('img/fondo.jpg'))
        ? "background-image:linear-gradient(160deg,rgba(44,44,92,.92),rgba(27,27,56,.88)),url('".asset('img/fondo.jpg')."')"
        : null;
@endphp

@section('cuerpo')
<div class="hero">
    <section class="hero-izq {{ $foto ? 'foto' : '' }}" @if ($foto) style="{{ $foto }}" @endif>
        <div class="sello"><img src="{{ asset('img/escudo.png') }}" alt="Escudo del instituto"></div>
        <span class="eyebrow">Mesa de Partes Virtual</span>
        <h1>Acceso del personal<br>del instituto</h1>
        <p>Recepciona, deriva y atiende los expedientes de tu área, con el control de plazos y el historial de cada trámite.</p>
        <div class="beneficios">
            <div>@include('partials.icono', ['i' => 'constancia']) Bandeja de expedientes de tu área</div>
            <div>@include('partials.icono', ['i' => 'reloj']) Semáforo de plazos por días hábiles</div>
            <div>@include('partials.icono', ['i' => 'historial']) Historial completo de cada expediente</div>
        </div>
    </section>
    <section class="hero-der">
        <div class="card acceso">
            <img class="logo-completo" src="{{ asset('img/logo-instituto.png') }}" alt="IESTP Manuel Antonio Hierro Pozo">
            <h2>Acceso al sistema</h2>
            <p class="sub">Solo para el personal del instituto</p>
            @include('partials.mensajes')
            <form method="POST" action="{{ route('login') }}">
                @csrf
                <label for="correo">Correo electrónico</label>
                <input id="correo" type="email" name="correo" value="{{ old('correo') }}" required autofocus placeholder="tucorreo@ejemplo.com" class="@error('correo') is-invalid @enderror">
                @error('correo')<div class="error">{{ $message }}</div>@enderror
                <label for="contrasena">Contraseña</label>
                <div class="clave">
                    <input id="contrasena" type="password" name="contrasena" required placeholder="••••••••">
                    <button type="button" title="Mostrar u ocultar la contraseña" aria-label="Mostrar u ocultar la contraseña"
                        onclick="const c=document.getElementById('contrasena');c.type=c.type==='password'?'text':'password'">@include('partials.icono', ['i' => 'ojo'])</button>
                </div>
                <label style="display:flex;align-items:center;font-weight:400"><input type="checkbox" name="recordar" value="1"> Mantener la sesión iniciada</label>
                <div style="margin-top:18px"><button class="btn block">Ingresar</button></div>
            </form>
            <p class="muted" style="margin-top:14px;text-align:center">¿Olvidaste tu contraseña? Acércate a Mesa de Partes para restablecerla.</p>
        </div>
    </section>
</div>
@endsection
