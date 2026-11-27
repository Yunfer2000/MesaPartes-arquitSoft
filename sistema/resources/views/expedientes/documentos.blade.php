@if (! empty($detallado))
    {{-- Seguimiento público: etiqueta del tipo (PDF, JPG…), nombre, tamaño y "Ver" --}}
    <ul class="lista-documentos">
        @foreach ($documentos as $doc)
            @php $ext = mb_strtoupper(mb_substr(pathinfo($doc->nombre_archivo, PATHINFO_EXTENSION), 0, 3)); @endphp
            <li>
                <span class="doc-ext doc-ext-{{ $ext }}">{{ $ext }}</span>
                <span class="doc-nombre">{{ $doc->nombre_archivo }} <small>· {{ $doc->tamano_kb >= 1024 ? number_format($doc->tamano_kb / 1024, 1, ',', '').' MB' : max(1, $doc->tamano_kb).' KB' }}</small></span>
                <a href="{{ isset($rutaDocumento) ? $rutaDocumento($doc) : route('documentos.show', $doc) }}" target="_blank">Ver</a>
            </li>
        @endforeach
    </ul>
@else
@foreach ($documentos as $doc)
    <a class="chip" href="{{ isset($rutaDocumento) ? $rutaDocumento($doc) : route('documentos.show', $doc) }}" target="_blank">{{ $doc->es_principal ? '📄' : '📎' }} {{ $doc->nombre_archivo }}</a>
@endforeach
@endif
