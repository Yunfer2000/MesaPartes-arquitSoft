{{-- CU09 Observar expediente (Mesa de Partes). --}}
<form method="POST" action="{{ route('mesa.observar', $expediente) }}" data-confirmar="¿Observar el expediente {{ $expediente->numero }}? Se avisará al administrado para que lo corrija.">
    @csrf
    <p class="muted">Úsalo si falta un requisito. El administrado recibirá el motivo y podrá subsanar.</p>
    <label for="motivo">Motivo *</label>
    <input id="motivo" name="motivo" value="{{ old('motivo') }}" maxlength="255" placeholder="Ejemplo: Falta el voucher de pago">
    @error('motivo')<div class="error">{{ $message }}</div>@enderror
    <label for="detalle">Qué debe corregir o adjuntar</label>
    <textarea id="detalle" name="detalle" maxlength="2000">{{ old('detalle') }}</textarea>
    <div style="margin-top:10px"><button class="btn w">Observar</button></div>
</form>
