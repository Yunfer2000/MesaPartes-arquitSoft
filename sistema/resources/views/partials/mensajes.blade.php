@if (session('exito'))
    <div class="ok se-va" role="status">{{ session('exito') }}</div>
    {{-- Mejora de interfaz, paso 4: el mensaje de éxito se oculta solo a los 8 segundos (los avisos se quedan) --}}
    <script>setTimeout(() => document.querySelectorAll('.ok.se-va').forEach(m => { m.classList.add('oculto'); setTimeout(() => m.remove(), 700); }), 8000);</script>
@endif
@if (session('aviso'))
    <div class="alert" role="alert">{{ session('aviso') }}</div>
@endif
