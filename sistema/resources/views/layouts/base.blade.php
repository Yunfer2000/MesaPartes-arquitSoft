<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#1B1B38">
    <title>@yield('titulo', 'Inicio') · Mesa de Partes Virtual</title>
    <link rel="icon" type="image/png" href="{{ asset('img/escudo.png') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    @stack('estilos')
</head>
<body>
<header class="top">
    <a href="{{ route('inicio') }}" class="logo" aria-label="Inicio"><img src="{{ asset('img/escudo.png') }}" alt="Escudo del IESTP Manuel Antonio Hierro Pozo"></a>
    <a href="{{ route('inicio') }}" class="inst">Mesa de Partes Virtual<small>IESTP Manuel Antonio Hierro Pozo · Huamanga</small></a>
    @yield('nav')
    <div class="user">
        @auth
            <div class="av">{{ mb_substr(auth()->user()->nombres, 0, 1) }}{{ mb_substr(auth()->user()->apellidos, 0, 1) }}</div>
            <div class="quien">{{ auth()->user()->nombres }}<small>{{ auth()->user()->rol->nombre }}</small></div>
            <form method="POST" action="{{ route('logout') }}">@csrf <button class="salir" title="Cerrar sesión">@include('partials.icono', ['i' => 'salir']) Salir</button></form>
        @else
            @unless (request()->routeIs('login') || config('app.staff_domain'))
                <a class="btn o" href="{{ route('login') }}">Iniciar sesión</a>
            @endunless
        @endauth
    </div>
</header>
@yield('cuerpo')
<footer class="pie">
    <span>© {{ date('Y') }} IESTP Manuel Antonio Hierro Pozo · Av. Universitaria N.° 120, Huamanga, Ayacucho</span>
    <span>Mesa de Partes Virtual · <a href="{{ route('verificar') }}">Verificar documento</a></span>
</footer>
</body>
</html>
