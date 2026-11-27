{{-- CU08 Recepcionar (Figura 12): Mesa de Partes completa los datos que el público ya no llena. --}}
<form method="POST" action="{{ route('mesa.recepcionar', $expediente) }}" >
    @csrf
    <p class="muted">Completa los datos del documento. Al recepcionar se registra la fecha y se notifica al administrado.</p>
    <div class="row">
        <div class="col">
            <label for="tipo_documento_id">Tipo de documento *</label>
            <select id="tipo_documento_id" name="tipo_documento_id" required>
                <option value="">Elige el tipo</option>
                @foreach ($tipos as $t)
                    <option value="{{ $t->id }}" @selected(old('tipo_documento_id', $expediente->tipo_documento_id) == $t->id)>{{ $t->nombre }}</option>
                @endforeach
            </select>
            @error('tipo_documento_id')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="col">
            <label for="folios">Folios *</label>
            <input id="folios" type="number" name="folios" min="1" max="9999" required value="{{ old('folios', $expediente->folios) }}">
            @error('folios')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="col">
            <label for="nro_documento">N.° de documento</label>
            <input id="nro_documento" name="nro_documento" maxlength="50" placeholder="Ejemplo: Solicitud N.° 015-2026" value="{{ old('nro_documento', $expediente->nro_documento) }}">
            @error('nro_documento')<div class="error">{{ $message }}</div>@enderror
        </div>
    </div>
    <div style="margin-top:10px"><button class="btn">Recepcionar</button></div>
</form>
