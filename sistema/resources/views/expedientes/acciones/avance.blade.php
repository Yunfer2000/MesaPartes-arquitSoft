{{-- CU12 Registrar avance (área). --}}
<form method="POST" action="{{ route('area.avance', $expediente) }}">
    @csrf
    <p class="muted">Anota en qué va el trámite. El administrado lo verá en su seguimiento.</p>
    <label for="nota">Avance *</label>
    <input id="nota" name="nota" maxlength="255" placeholder="Ejemplo: Se solicitó el sílabo al programa de estudios" value="{{ old('nota') }}">
    @error('nota')<div class="error">{{ $message }}</div>@enderror
    <div style="margin-top:10px"><button class="btn o">Guardar avance</button></div>
</form>
