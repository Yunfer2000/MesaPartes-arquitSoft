{{--
    Zona para elegir o arrastrar un archivo PDF o ZIP de hasta 10 MB (CU05 y CU17). El input queda encima
    (invisible), así funciona con clic, teclado y arrastrar. El aviso inmediato está en public/js/formulario.js;
    el servidor vuelve a validar con App\Services\Archivos.
    Parámetros:
      $obligatorio  true en el formulario público; en el presencial el escaneo es opcional
      $etiqueta     texto de la etiqueta
      $ayuda        texto de ayuda debajo de la zona
--}}
@php($obligatorio = $obligatorio ?? true)
<label for="archivo">{{ $etiqueta }} <span class="op">· un PDF o ZIP, máximo 10 MB{{ $obligatorio ? '' : ' (opcional)' }}</span></label>
<div class="zona-archivo @error('archivo') con-error @enderror" id="zona-archivo">
    <input id="archivo" type="file" name="archivo" accept=".pdf,.zip,application/pdf,application/zip" @required($obligatorio) aria-describedby="archivo-ayuda archivo-estado">
    <div class="zona-vacia" id="zona-vacia">
        @include('partials.icono', ['i' => 'carpeta'])
        @if ($presencial ?? false)
            <span><b>Arrastra el archivo escaneado aquí</b> o <u>elígelo desde el equipo</u></span>
        @else
            <span><b>Arrastra tu archivo aquí</b> o <u>elígelo desde tu equipo</u></span>
        @endif
    </div>
    <div class="zona-elegido" id="zona-elegido" hidden>
        {{-- Ícono del archivo con su tipo (PDF en rojo, ZIP en dorado) --}}
        <svg class="ico-archivo tipo-pdf" id="archivo-icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><text x="12" y="18.5" text-anchor="middle" id="archivo-tipo">PDF</text></svg>
        <span class="zona-datos"><b id="archivo-nombre"></b><small id="archivo-detalle"></small></span>
        <button type="button" class="btn o btn-chico" id="archivo-quitar">✕ Quitar</button>
    </div>
</div>
<div class="ayuda" id="archivo-ayuda">{{ $ayuda }}</div>
<div class="error" id="archivo-estado" role="alert" hidden></div>
@error('archivo')<div class="error">{{ $message }}</div>@enderror
