{{-- CU07 Parte A1: seguimiento con burbujas numeradas, una por movimiento real del expediente (solo consulta pública).
     Estilos en public/css/burbujas.css. El color nunca es la única pista: cada burbuja dice su estado en texto. --}}
@php
    $burbujas = $expediente->burbujas();
    $textoEstado = ['completado' => 'completado', 'actual' => 'paso actual', 'observado' => 'observado', 'pendiente' => 'pendiente'];
@endphp
<h2 id="recorrido-titulo">Recorrido de tu trámite</h2>
<ol class="burbujas" aria-labelledby="recorrido-titulo" tabindex="0">
    @foreach ($burbujas as $b)
        <li class="burbuja burbuja-{{ $b['accion'] }}{{ $b['actual'] ? ' actual' : '' }}" @if ($b['actual']) aria-current="step" @endif>
            <span class="burbuja-num" aria-hidden="true">{{ $b['numero'] }}</span>
            <span class="burbuja-txt">
                <span class="sr">Paso {{ $b['numero'] }} de {{ $burbujas->count() }}, {{ $textoEstado[$b['estado']] }}:</span>
                <b>{{ $b['titulo'] }}</b>
                @if ($b['area'])<small>{{ $b['area'] }}</small>@endif
                @if ($b['fecha'])
                    <small><time datetime="{{ $b['fecha']->toIso8601String() }}">{{ $b['fecha']->format('d/m/Y H:i') }}</time></small>
                    {{-- CU07 A3: desde la burbuja roja actual se llega directo a la observación --}}
                    @if ($b['estado'] === 'observado' && $b['actual'] && ! empty($observacionPendiente))
                        <a class="burbuja-enlace" href="#observacion">Subsanar ahora</a>
                    @endif
                @else
                    <small>Te avisaremos cuando el área responda</small>
                @endif
            </span>
        </li>
    @endforeach
</ol>
