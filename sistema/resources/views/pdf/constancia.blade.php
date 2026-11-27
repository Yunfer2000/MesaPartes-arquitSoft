{{-- Constancia de presentación o cargo de recepción en PDF (Figuras 11 y 13, RF26). --}}
@extends('pdf.base', ['codigo' => $expediente->codigo_verificacion, 'firmante' => 'Mesa de Partes Virtual del IESTP Manuel Antonio Hierro Pozo'])
@section('titulo', $titulo.' '.$expediente->numero)

@section('cuerpo')
    @php
        $a = $expediente->administrado;
        $presencial = $expediente->modalidad === 'Presencial';
    @endphp
    <h1>{{ mb_strtoupper($titulo) }}</h1>
    <div class="numero"><span class="recuadro">{{ $expediente->numero }}</span></div>
    <div class="fecha">{{ $presencial ? 'Recibido' : 'Presentado' }} el {{ $expediente->fecha_presentacion->format('d/m/Y') }} a las {{ $expediente->fecha_presentacion->format('H:i') }} · Modalidad {{ mb_strtolower($expediente->modalidad) }}</div>

    <div class="seccion">DATOS DEL REMITENTE</div>
    <table class="datos">
        <tr><td class="campo">{{ $a->tipo_doc_identidad }}</td><td>{{ $a->numero_documento }}</td></tr>
        @if ($a->razon_social)<tr><td class="campo">Razón social</td><td>{{ $a->razon_social }}</td></tr>@endif
        @unless ($expediente->tieneRepresentante())
            <tr><td class="campo">Nombres</td><td>{{ $a->nombres }}</td></tr>
            <tr><td class="campo">Apellido paterno</td><td>{{ $a->apellido_paterno }}</td></tr>
            @if ($a->apellido_materno)<tr><td class="campo">Apellido materno</td><td>{{ $a->apellido_materno }}</td></tr>@endif
        @endunless
    </table>
    @if ($expediente->tieneRepresentante())
        <div class="seccion">DATOS DEL REPRESENTANTE</div>
        <table class="datos">
            <tr><td class="campo">DNI</td><td>{{ $expediente->representante_dni }}</td></tr>
            <tr><td class="campo">Nombres</td><td>{{ $expediente->representante_nombres }}</td></tr>
            <tr><td class="campo">Apellido paterno</td><td>{{ $expediente->representante_apellido_paterno }}</td></tr>
            @if ($expediente->representante_apellido_materno)<tr><td class="campo">Apellido materno</td><td>{{ $expediente->representante_apellido_materno }}</td></tr>@endif
        </table>
    @endif

    <div class="seccion">DATOS DEL TRÁMITE</div>
    <table class="datos">
        <tr><td class="campo">Tipo de documento</td><td>{{ $expediente->tipoDocumento?->nombre ?? 'Por definir' }}</td></tr>
        <tr><td class="campo">Asunto</td><td>{{ $expediente->asunto }}</td></tr>
        @if ($expediente->folios)<tr><td class="campo">Folios</td><td>{{ $expediente->folios }}</td></tr>@endif
        @if ($expediente->codigo_propio)
            <tr><td class="campo">{{ $presencial ? 'Código del sello' : 'Código propio' }}</td><td><b>{{ $expediente->codigo_propio }}</b></td></tr>
        @endif
        <tr><td class="campo">Dirigido a</td><td>{{ $expediente->areaDestino->nombre }}</td></tr>
        <tr><td class="campo">Estado</td><td>{{ $expediente->estado }}</td></tr>
    </table>

    <div class="seccion">DOCUMENTOS ADJUNTOS</div>
    <table class="datos">
        <tr><td class="campo">{{ $documentos->count() > 1 ? 'Archivos' : 'Archivo' }}</td><td>
            @forelse ($documentos as $d)
                {{ $d->nombre_archivo }}@if ($d->hash_sha256)<br><span class="huella">SHA-256: {{ $d->hash_sha256 }}</span>@endif<br>
            @empty
                Documento físico (sin escaneo)
            @endforelse
        </td></tr>
    </table>
    <p class="muted" style="margin-top:12px;text-align:center">Consulte el estado de su trámite en {{ route('externo.consulta') }} con este número y su documento de identidad.</p>
@endsection
