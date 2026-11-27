{{-- Pantallas del personal (Mesa de Partes, áreas y administración) con menú lateral. --}}
@extends('layouts.base')

@php
    $u = auth()->user();
    $menu = \App\Support\Menu::para($u);
@endphp

@section('cuerpo')
    <div class="wrap">
        <nav class="side">
            <div class="h">{{ mb_strtoupper($u->area->nombre ?? 'Administración') }}</div>
            @foreach ($menu as $item)
                <a href="{{ $item['url'] }}" class="{{ $item['activo'] ? 'on' : '' }}">@include('partials.icono', ['i' => $item['icono']]) {{ $item['texto'] }}@if ($item['contador'])<span class="contador" title="Expedientes por atender">{{ $item['contador'] }}</span>@endif</a>
            @endforeach
            <div class="pie-side">IESTP Manuel Antonio Hierro Pozo<br>Huamanga, Ayacucho</div>
        </nav>
        <main class="main">@include('partials.mensajes')@yield('contenido')</main>
    </div>
    {{-- Formularios flotantes (issues #23, #25, #26): data-abrir="id" abre el <dialog>, data-cerrar lo cierra
         y data-abierto lo muestra al cargar (al editar o si hubo errores al guardar). --}}
    <script>
    document.querySelectorAll('[data-abrir]').forEach((b) => b.addEventListener('click', () => document.getElementById(b.dataset.abrir).showModal()));
    document.querySelectorAll('dialog [data-cerrar]').forEach((b) => b.addEventListener('click', () => b.closest('dialog').close()));
    document.querySelectorAll('dialog[data-abierto]').forEach((d) => d.showModal());
    </script>
@endsection
