{{-- CU17: Registro presencial (Figura 13). Usa los mismos datos del solicitante y la misma carga de archivo
     que el formulario público (CU05); conserva lo propio del presencial: tipo de solicitante, área de destino,
     folios y N.° de documento. El escaneo es opcional: se puede registrar solo el documento físico.
     El formulario se muestra completo desde el inicio; "Buscar" solo rellena los datos de un solicitante ya registrado. --}}
@extends('layouts.app')
@section('titulo', 'Registro presencial')

@section('contenido')
<div class="crumb">Mesa de Partes › Registro presencial</div>
<h1>Registro presencial</h1>

@php
    // ?documento= : resultado de la búsqueda hecha desde la dirección (DNI, carné, pasaporte o RUC)
    $resultado = match (true) {
        $documento === '' => null,
        $administrado?->esJuridica() => ['clase' => 'ok', 'texto' => "Entidad registrada: {$administrado->razon_social}"],
        $administrado !== null => ['clase' => 'ok', 'texto' => "{$administrado->nombre_completo} · {$administrado->tipo_administrado}"],
        default => ['clase' => 'alert', 'texto' => 'El solicitante no está registrado. Ingresa sus datos; se registrará sin cuenta de acceso.'],
    };
@endphp

<div class="formulario">
    @if ($errors->any())
        <div class="alert">Revisa los campos marcados en rojo.</div>
    @endif

    <form method="POST" action="{{ route('mesa.presencial.store') }}" enctype="multipart/form-data" id="formulario">
        @csrf
        <section class="card paso">
            <h2><span class="num">1</span> Solicitante</h2>
            <p class="sub">Todos los campos son obligatorios, salvo los marcados como opcionales. Busca el documento o el RUC para rellenar los datos de un solicitante ya registrado.</p>
            @include('partials.formulario.solicitante', [
                'documentos' => \App\Models\Administrado::DOCUMENTOS_PRESENCIAL,
                'presencial' => true,
                'solicitante' => $administrado,
                'documentoInicial' => $documento,
                'resultado' => $resultado,
            ])
        </section>

        <section class="card paso">
            <h2><span class="num">2</span> Documento</h2>
            <div class="row">
                <div class="col">
                    <label for="tipo_documento_id">Tipo de documento</label>
                    <select id="tipo_documento_id" name="tipo_documento_id" required>
                        @foreach ($tipos as $t)<option value="{{ $t->id }}" @selected(old('tipo_documento_id') == $t->id)>{{ $t->nombre }}</option>@endforeach
                    </select>
                    @error('tipo_documento_id')<div class="error">{{ $message }}</div>@enderror
                </div>
                <div class="col">
                    <label for="area_destino_id">Dirigido a</label>
                    <select id="area_destino_id" name="area_destino_id" required>
                        @foreach ($areas as $a)<option value="{{ $a->id }}" @selected(old('area_destino_id') == $a->id)>{{ $a->nombre }}</option>@endforeach
                    </select>
                    @error('area_destino_id')<div class="error">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <label for="folios">Folios</label>
                    <input id="folios" type="number" name="folios" min="1" max="9999" required value="{{ old('folios') }}">
                    @error('folios')<div class="error">{{ $message }}</div>@enderror
                </div>
                <div class="col">
                    {{-- Mejora: el número que Mesa anotó o selló a mano en el papel, para seguir el documento físico --}}
                    <label for="codigo_propio">N.° de expediente
                        <span class="info" tabindex="0" role="note" aria-label="Ingrese un número de expediente que puso al documento" data-ayuda="Ingrese un número de expediente que puso al documento">i</span></label>
                    <input id="codigo_propio" name="codigo_propio" maxlength="20" placeholder="Ejemplo: EXP-2026-000005" value="{{ old('codigo_propio') }}">
                    @error('codigo_propio')<div class="error">{{ $message }}</div>@enderror
                </div>
            </div>
            <label for="nro_documento">N.° de documento <span class="op">(opcional)</span></label>
            <input id="nro_documento" name="nro_documento" maxlength="50" placeholder="Ejemplo: Oficio N.° 012-2026" value="{{ old('nro_documento') }}">
            @error('nro_documento')<div class="error">{{ $message }}</div>@enderror
            <label for="asunto">Asunto</label>
            <input id="asunto" name="asunto" value="{{ old('asunto') }}" required maxlength="255" aria-describedby="asunto-contador">
            <div class="contador" id="asunto-contador">{{ mb_strlen(old('asunto', '')) }} / 255</div>
            @error('asunto')<div class="error">{{ $message }}</div>@enderror

            @include('partials.formulario.archivo', [
                'etiqueta' => 'Documento escaneado',
                'obligatorio' => false,
                'presencial' => true,
                'ayuda' => 'Si no lo escaneas, queda registrado solo el documento físico. Si son varios documentos, júntalos en un PDF o ZIP.',
            ])

            <div class="acciones">
                <button class="btn" id="enviar">Registrar</button>
                <span class="muted">Al registrar se abre el cargo para imprimir y se avisa al correo del solicitante.</span>
            </div>
        </section>
    </form>
</div>

<h2 style="margin-top:24px">Registros presenciales</h2>
<div class="card" style="padding:0;overflow-x:auto">
    <table class="responsiva">
        <tr><th>Expediente</th><th>Solicitante</th><th>Fecha</th><th>Estado</th></tr>
        @forelse ($registros as $e)
            <tr>
                <td data-label="Expediente"><a href="{{ route('tramites.constancia', $e) }}"><b>{{ $e->numero }}</b></a></td>
                <td data-label="Solicitante">{{ $e->administrado->nombre_completo }}</td>
                <td data-label="Fecha">{{ $e->fecha_presentacion->format('d/m/Y H:i') }}</td>
                <td data-label="Estado">@include('partials.estado', ['estado' => $e->estado])</td>
            </tr>
        @empty
            <tr><td colspan="4" class="vacio">@include('partials.icono', ['i' => 'vacio'])Todavía no hay registros presenciales.</td></tr>
        @endforelse
    </table>
</div>
<p class="muted" style="margin-top:8px">Del más reciente al más antiguo · {{ $registros->total() }} registro(s)</p>
{{ $registros->links('partials.paginacion') }}

<script src="{{ asset('js/formulario.js') }}?v={{ filemtime(public_path('js/formulario.js')) }}"></script>
<script>
    (() => {
        const $ = (id) => document.getElementById(id);
        // Búsqueda del personal: devuelve también celular y correo para completar el registro
        const {esJuridica} = Formulario.solicitante({
            buscarUrl: '{{ route('mesa.presencial.buscar') }}',
            contacto: true,
            textos: {
                escribeDocumento: 'Escribe el número de documento y presiona Buscar.',
                noEncontrado: 'El solicitante no está registrado. <b>Ingresa sus datos</b>; se registrará sin cuenta de acceso.',
                encontrado: 'Solicitante registrado: se usan sus datos.',
                errorDocumento: 'No se pudo buscar ahora. Puedes escribir los datos a mano.',
                rucNoEncontrado: 'La entidad no está registrada. <b>Escribe la razón social</b>.',
                rucEncontrado: 'Entidad registrada',
            },
        });
        Formulario.avisos($('formulario').querySelectorAll('section:first-of-type input:not([type=hidden])'), esJuridica);
        Formulario.archivo({listo: 'listo para registrar'});

        const asunto = $('asunto'), contador = $('asunto-contador');
        asunto.addEventListener('input', () => { contador.textContent = asunto.value.length + ' / 255'; });

        // Evita el doble registro: al enviar, el botón se bloquea; si se vuelve con "Atrás", se habilita otra vez
        const enviar = $('enviar');
        $('formulario').addEventListener('submit', () => { enviar.disabled = true; enviar.textContent = 'Registrando…'; });
        window.addEventListener('pageshow', () => { enviar.disabled = false; enviar.textContent = 'Registrar'; });
    })();
</script>
@endsection
