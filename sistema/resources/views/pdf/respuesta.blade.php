{{-- Respuesta de un área en PDF, firmada por su responsable (RF26). --}}
@extends('pdf.base', ['codigo' => $respuesta->codigo_verificacion, 'firmante' => \App\Services\Verificacion::firmante($respuesta)])
@section('titulo', 'Respuesta '.$expediente->numero)

@section('cuerpo')
    <h1>Respuesta del área</h1>
    <div class="numero">{{ $expediente->numero }}</div>
    <table class="datos">
        <tr><td class="campo">Fecha de respuesta</td><td>{{ $respuesta->fecha->format('d/m/Y H:i') }}</td></tr>
        <tr><td class="campo">Área</td><td>{{ $respuesta->usuario?->area?->nombre ?? 'Personal del instituto' }}</td></tr>
        <tr><td class="campo">Administrado</td><td>{{ $expediente->administrado->nombre_completo }} · {{ $expediente->administrado->tipo_doc_identidad }} {{ $expediente->administrado->numero_documento }}</td></tr>
        <tr><td class="campo">Asunto del trámite</td><td>{{ $expediente->asunto }}</td></tr>
        @if ($respuesta->documentos->isNotEmpty())
            <tr><td class="campo">Archivos adjuntos</td><td>
                @foreach ($respuesta->documentos as $d)
                    {{ $d->nombre_archivo }}@if ($d->hash_sha256)<br><span class="huella">SHA-256: {{ $d->hash_sha256 }}</span>@endif<br>
                @endforeach
            </td></tr>
        @endif
    </table>
    <div class="contenido">{{ $respuesta->contenido }}</div>
@endsection
