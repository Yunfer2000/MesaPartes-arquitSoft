{{-- CU19 Verificar documento: resultado y comprobación del archivo con su huella SHA-256. --}}
@extends('layouts.base')
@section('titulo', 'Verificar '.$codigo)

@section('cuerpo')
<main class="main publico" style="max-width:640px">
    <div class="crumb"><a href="{{ route('inicio') }}">Inicio</a> › <a href="{{ route('verificar') }}">Verificar documento</a> › {{ $codigo }}</div>
    <h1>Verificar documento</h1>

    @if (! $datos)
        <div class="alert">No encontramos ningún documento con el código <b>{{ $codigo }}</b>. Revisa que lo hayas escrito igual que en tu constancia.</div>
        <p style="margin-top:14px"><a class="btn o" href="{{ route('verificar') }}">Probar con otro código</a></p>
    @else
        <div class="ok"><b>Documento auténtico.</b> Fue emitido por la Mesa de Partes Virtual del IESTP Manuel Antonio Hierro Pozo.</div>
        <div class="card" style="margin-top:14px">
            <table>
                <tr><td class="muted" style="width:170px">Documento</td><td>{{ $datos['tipo'] }}</td></tr>
                <tr><td class="muted">Expediente</td><td><b>{{ $datos['expediente']->numero }}</b></td></tr>
                <tr><td class="muted">Fecha y hora</td><td>{{ $datos['fecha']->format('d/m/Y H:i') }}</td></tr>
                <tr><td class="muted">Firmado por</td><td>{{ $datos['firmante'] }}</td></tr>
                <tr><td class="muted">Código de verificación</td><td>{{ $codigo }}</td></tr>
            </table>

            <h2 style="margin-top:16px">Archivos y su huella (SHA-256)</h2>
            @forelse ($datos['documentos'] as $d)
                <p style="margin:6px 0">{{ $d->nombre_archivo }}<br><code class="muted" style="font-size:12px;word-break:break-all">{{ $d->hash_sha256 ?? 'Sin huella registrada' }}</code></p>
            @empty
                <p class="muted">Documento físico, sin archivo escaneado.</p>
            @endforelse
        </div>

        @if ($datos['documentos']->isNotEmpty())
            <div class="card" style="margin-top:14px">
                <h2>¿Tu archivo es el original?</h2>
                @isset($comprobado)
                    @if ($coincide)
                        <div class="ok">Coincide con <b>{{ $coincide->nombre_archivo }}</b>: el archivo no fue modificado.</div>
                    @else
                        <div class="alert">No coincide: el archivo fue modificado o no pertenece a este documento.</div>
                    @endif
                @endisset
                <form method="POST" action="{{ route('verificar.comprobar', $codigo) }}" enctype="multipart/form-data" style="margin-top:10px">
                    @csrf
                    <label for="archivo" style="margin-top:0">Sube el PDF que recibiste. Solo se compara su huella; no se guarda.</label>
                    <input id="archivo" type="file" name="archivo" required>
                    @error('archivo')<div class="error">{{ $message }}</div>@enderror
                    <div style="margin-top:10px"><button class="btn o">Comprobar archivo</button></div>
                </form>
            </div>
        @endif
    @endif
    <p class="muted" style="margin-top:16px">Firma electrónica del sistema: prueba que el documento salió de esta Mesa de Partes y que el archivo no cambió. No reemplaza a la firma digital con certificado (Ley 27269).</p>
</main>
@endsection
