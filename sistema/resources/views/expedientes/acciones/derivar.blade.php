{{-- CU10 Derivar expediente (Figura 14). Mejoras: sin cuadro de búsqueda; primero el área principal y luego,
     si se quiere, una de sus subáreas según el organigrama (ya no se elige una persona);
     las copias quedan plegadas porque son opcionales. --}}
@php
    $principales = $nivelesDerivar['principales'];
    $subareas = $nivelesDerivar['subareas']->reject(fn ($a) => $a->id === $areaActual?->id);
    // Se propone el área a la que se dirigió el trámite; si es una subárea, se elige su área y la subárea
    $dirigido = $expediente->area_destino_id !== $areaActual?->id ? $expediente->areaDestino : null;
    $dirigidoEsSub = $dirigido && $subareas->contains('id', $dirigido->id);
    $elegida = old('area_id', $dirigidoEsSub ? $dirigido->area_padre_id : $dirigido?->id);
    $subElegida = old('subarea_id', $dirigidoEsSub ? $dirigido->id : null);
    $copiasMarcadas = old('copias', []);
@endphp
<form method="POST" action="{{ route('expedientes.derivar', $expediente) }}" id="form-derivar">
    @csrf
    <label for="area_id">¿A qué área lo envías? *</label>
    <select id="area_id" name="area_id" required>
        <option value="">Elige el área</option>
        @foreach ($principales as $a)
            <option value="{{ $a->id }}" @selected($elegida == $a->id)>{{ $a->nombre }}{{ $a->id === $areaActual?->id ? ' (tu área)' : '' }}</option>
        @endforeach
    </select>
    @error('area_id')<div class="error">{{ $message }}</div>@enderror

    <label for="subarea_id">¿A qué subárea en especial? (opcional)</label>
    <select id="subarea_id" name="subarea_id">
        <option value="">Al área completa</option>
        @foreach ($subareas as $s)
            <option value="{{ $s->id }}" data-padre="{{ $s->area_padre_id }}" @selected($subElegida == $s->id)>{{ $s->nombre }}</option>
        @endforeach
    </select>
    <div class="muted" id="ayuda_subarea">Solo aparecen las subáreas del área que elegiste.</div>
    @error('subarea_id')<div class="error">{{ $message }}</div>@enderror

    <label for="proveido">Proveído (indicación para el área)</label>
    <input id="proveido" name="proveido" maxlength="255" value="{{ old('proveido', 'Para su atención y trámite correspondiente.') }}">
    @error('proveido')<div class="error">{{ $message }}</div>@enderror

    {{-- Derivación de uno a muchos: plegada, porque casi siempre se deriva a una sola área --}}
    <details class="copias" @if ($copiasMarcadas || $errors->hasAny(['copias', 'copias.*'])) open @endif>
        <summary>Enviar copia a otras áreas (opcional)</summary>
        <p class="muted">Para comunicaciones o reuniones dirigidas a varias áreas. Solo el área elegida atiende el expediente; las demás reciben una copia para leer.</p>
        <label class="check"><input type="checkbox" id="copias_todas">Marcar todas</label>
        <div class="lista-copias">
            @foreach ($areasDerivar['grupos'] as $unidad => $grupo)
                @php $grupo = $grupo->where('sigla', '!=', \App\Models\Area::MESA_DE_PARTES); @endphp
                @if ($grupo->isNotEmpty())
                    <fieldset>
                        <legend>{{ $unidad }}</legend>
                        @foreach ($grupo as $a)
                            <label class="check"><input type="checkbox" name="copias[]" value="{{ $a->id }}" @checked(in_array($a->id, $copiasMarcadas))>{{ $a->nombre }}</label>
                        @endforeach
                    </fieldset>
                @endif
            @endforeach
        </div>
        @error('copias')<div class="error">{{ $message }}</div>@enderror
        @error('copias.*')<div class="error">{{ $message }}</div>@enderror
    </details>

    <p class="muted" style="margin-top:12px">Se registrará el movimiento (origen, destino, usuario, fecha y hora) y se notificará al administrado.</p>
    <div style="margin-top:10px"><button class="btn">Derivar</button></div>
</form>
<script>
    (function () {
        const form = document.getElementById('form-derivar');
        const destino = document.getElementById('area_id');
        const subarea = document.getElementById('subarea_id');
        const ayuda = document.getElementById('ayuda_subarea');
        const casillas = () => form.querySelectorAll('input[name="copias[]"]');

        // Al elegir el área: solo sus subáreas, y el destino final no puede recibir copia
        function actualizar() {
            let hay = 0;
            subarea.querySelectorAll('option[data-padre]').forEach(o => { const ok = o.dataset.padre === destino.value; o.hidden = !ok; o.disabled = !ok; if (ok) hay++; });
            if (subarea.selectedOptions[0]?.disabled) subarea.value = '';
            subarea.disabled = hay === 0;
            ayuda.textContent = !destino.value ? 'Primero elige el área.'
                : hay === 0 ? 'Esta área no tiene subáreas; el expediente llegará al área.'
                : 'Si no eliges una subárea, el expediente llegará al área.';
            const final = subarea.value || destino.value;
            casillas().forEach(c => { c.disabled = c.value === final; if (c.disabled) c.checked = false; });
        }
        destino.addEventListener('change', actualizar);
        subarea.addEventListener('change', actualizar);
        actualizar();

        document.getElementById('copias_todas').addEventListener('change', function () {
            casillas().forEach(c => { if (! c.disabled) c.checked = this.checked; });
        });

        // Confirmación con resumen antes de enviar
        form.addEventListener('submit', (e) => {
            const area = (subarea.value ? subarea : destino).selectedOptions[0]?.textContent.replace(' (tu área)', '') ?? '';
            const copias = [...casillas()].filter(c => c.checked).length;
            if (!confirm('¿Derivar el expediente {{ $expediente->numero }} a ' + area + '?' + (copias ? '\nCon copia a ' + copias + ' área(s).' : ''))) e.preventDefault();
        });
    })();
</script>
