{{--
    Datos del solicitante, compartidos por el formulario público (CU05) y el registro presencial (CU17).
    Persona natural: su documento de identidad. Persona jurídica: RUC, razón social y su representante
    (el mismo campo "N.° de documento" es el DNI del representante).
    Parámetros:
      $documentos   documentos de identidad que acepta la persona natural
      $presencial   true en el registro presencial: textos para el operador y datos de contacto ya registrados
      $solicitante  (opcional) administrado ya registrado para rellenar el formulario
      $documentoInicial (opcional) número buscado que no está registrado
      $resultado    (opcional) ['clase' => 'ok'|'alert', 'texto' => '…'] mensaje de la búsqueda
    El comportamiento (mostrar u ocultar, buscar, formatos) está en public/js/formulario.js.
--}}
@php
    $presencial = $presencial ?? false;
    $s = $solicitante ?? null;
    $entidad = $s?->esJuridica() ? $s : null;
    $natural = $s && ! $entidad ? $s : null;
    $juridica = old('tipo_persona', $entidad ? 'juridica' : 'natural') === 'juridica';
    $docNatural = old('tipo_doc_identidad', in_array($natural?->tipo_doc_identidad, $documentos, true) ? $natural->tipo_doc_identidad : 'DNI');
    $maternoOpcional = ! $juridica && $docNatural !== 'DNI';
    $resultado = $resultado ?? null;
    $tituloNatural = $presencial ? 'Datos del solicitante' : 'Tus datos';
    // Un dato ya registrado se muestra pero no se cambia desde aquí (no se sobrescribe al guardar)
    $fijo = fn ($valor) => $natural && filled($valor) && ! old('numero_documento');
@endphp
{{-- Dos botones de opción lado a lado; la opción elegida se resalta (Natural por defecto) --}}
<fieldset class="tipo-persona">
    <legend class="leyenda">Tipo de persona</legend>
    <div class="tipos-persona">
        @foreach (\App\Models\Administrado::TIPOS_PERSONA as $valor => $etiqueta)
            <label class="tipo-doc"><input type="radio" name="tipo_persona" value="{{ $valor }}" required @checked(($juridica ? 'juridica' : 'natural') === $valor)>
                <span><b>{{ $etiqueta }}</b></span></label>
        @endforeach
    </div>
    @error('tipo_persona')<div class="error">{{ $message }}</div>@enderror
</fieldset>

{{-- Persona jurídica: la entidad (se guarda en administrados) --}}
<div id="bloque_entidad" data-persona="juridica" @unless ($juridica) hidden @endunless>
    <h3 class="subtitulo">Datos de la entidad</h3>
    <label for="ruc">RUC</label>
    <div style="display:flex;gap:8px">
        <input id="ruc" name="ruc" value="{{ old('ruc', $entidad?->numero_documento) }}" required minlength="11" maxlength="11" inputmode="numeric" pattern="(10|15|17|20)[0-9]{9}" placeholder="Ejemplo: 20123456789" class="@error('ruc') is-invalid @enderror" @disabled(! $juridica)>
        <button type="button" class="btn o" id="buscar_ruc">@include('partials.icono', ['i' => 'buscar']) Buscar</button>
    </div>
    @error('ruc')<div class="error">{{ $message }}</div>@enderror
    <div id="resultado_ruc" class="muted" style="margin-top:8px" aria-live="polite">@if ($resultado && $entidad)<div class="{{ $resultado['clase'] }}">{{ $resultado['texto'] }}</div>@endif</div>
    <label for="razon_social">Razón social</label>
    <input id="razon_social" name="razon_social" value="{{ old('razon_social', $entidad?->razon_social) }}" required maxlength="150" placeholder="Nombre de la empresa o institución" @readonly($entidad && ! old('ruc')) @disabled(! $juridica)>
    @error('razon_social')<div class="error">{{ $message }}</div>@enderror
</div>

<h3 class="subtitulo" id="titulo_persona" data-natural="{{ $tituloNatural }}">{{ $juridica ? 'Datos del representante' : $tituloNatural }}</h3>
{{-- Persona natural: DNI (por defecto), carné de extranjería o (solo presencial) pasaporte. Persona jurídica: el mismo campo es el DNI del representante. --}}
<div class="row">
    <div class="col" style="max-width:220px" id="bloque_tipo_doc" data-persona="natural" @if ($juridica) hidden @endif>
        <label for="tipo_doc_identidad">Tipo de documento</label>
        <select id="tipo_doc_identidad" name="tipo_doc_identidad" @disabled($juridica)>
            @foreach ($documentos as $t)
                <option @selected($docNatural === $t)>{{ $t }}</option>
            @endforeach
        </select>
    </div>
    <div class="col">
        <label for="numero_documento">N.° de documento</label>
        <div style="display:flex;gap:8px">
            <input id="numero_documento" name="numero_documento" value="{{ old('numero_documento', $natural?->numero_documento ?? ($entidad ? null : ($documentoInicial ?? null))) }}" required
                @if ($juridica || $docNatural === 'DNI') minlength="8" maxlength="8" inputmode="numeric" pattern="[0-9]{8}" @else minlength="6" maxlength="12" pattern="[A-Za-z0-9]{6,12}" @endif
                placeholder="Ejemplo: 70451236" class="@error('numero_documento') is-invalid @enderror" aria-describedby="documento-ayuda">
            <button type="button" class="btn o" id="buscar" data-persona="natural" @if ($juridica) hidden @endif>@include('partials.icono', ['i' => 'buscar']) Buscar</button>
        </div>
        <div class="ayuda" id="documento-ayuda" @unless ($juridica) hidden @endunless>DNI del representante: 8 dígitos.</div>
        @error('numero_documento')<div class="error">{{ $message }}</div>@enderror
    </div>
</div>
<div id="resultado" class="muted" style="margin-top:8px" aria-live="polite" @if ($juridica) hidden @endif>@if ($resultado && ! $entidad)<div class="{{ $resultado['clase'] }}">{{ $resultado['texto'] }}</div>@endif</div>
<div class="row">
    <div class="col">
        <label for="apellido_paterno">Apellido paterno</label>
        <input id="apellido_paterno" name="apellido_paterno" value="{{ old('apellido_paterno', $natural?->apellido_paterno) }}" required maxlength="60" @readonly($fijo($natural?->apellido_paterno))>
        @error('apellido_paterno')<div class="error">{{ $message }}</div>@enderror
    </div>
    <div class="col">
        <label for="apellido_materno">Apellido materno <span class="op" id="materno-opcional" @unless ($maternoOpcional) hidden @endunless>(opcional)</span></label>
        <input id="apellido_materno" name="apellido_materno" value="{{ old('apellido_materno', $natural?->apellido_materno) }}" maxlength="60" @required(! $maternoOpcional) @readonly($fijo($natural?->apellido_materno)) aria-describedby="materno-ayuda">
        <div class="ayuda" id="materno-ayuda" @unless ($maternoOpcional) hidden @endunless>{{ $presencial ? 'Si tiene un solo apellido, deja este campo vacío.' : 'Si tienes un solo apellido, déjalo vacío.' }}</div>
        @error('apellido_materno')<div class="error">{{ $message }}</div>@enderror
    </div>
</div>
<label for="nombres">Nombres</label>
<input id="nombres" name="nombres" value="{{ old('nombres', $natural?->nombres) }}" required maxlength="100" @readonly($fijo($natural?->nombres))>
@error('nombres')<div class="error">{{ $message }}</div>@enderror
<div class="row">
    <div class="col">
        <label for="celular">Celular</label>
        <input id="celular" name="celular" value="{{ old('celular', $natural?->telefono) }}" required minlength="9" maxlength="9" inputmode="numeric" pattern="9[0-9]{8}" placeholder="9XXXXXXXX" @readonly($fijo($natural?->telefono))>
        @error('celular')<div class="error">{{ $message }}</div>@enderror
    </div>
    <div class="col">
        <label for="correo">Correo electrónico</label>
        <input id="correo" type="email" name="correo" value="{{ old('correo', $natural?->correo) }}" required maxlength="120" pattern="[^@\s]+@[^@\s]+\.[A-Za-z]{2,}" placeholder="nombre@correo.com" @readonly($fijo($natural?->correo))>
        @error('correo')<div class="error">{{ $message }}</div>@enderror
    </div>
</div>
@if ($presencial)
    <div class="ayuda">Los datos de un solicitante ya registrado no se cambian desde aquí. El aviso del registro se envía a su correo registrado o, en persona jurídica, al del representante.</div>
@endif
