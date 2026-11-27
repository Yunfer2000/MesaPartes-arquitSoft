{{-- Historial de movimientos (CU07 paso 4, CU14 paso 4). --}}
@php
    $acciones = ['presentado' => 'Presentado', 'recibido' => 'Recibido', 'observado' => 'Observado',
        'subsanado' => 'Subsanado', 'derivado' => 'Derivado', 'confirmado' => 'En atención', 'avance' => 'Avance',
        'finalizado' => 'Finalizado'];
@endphp
<div class="tl">
    @foreach ($expediente->movimientos->sortByDesc(fn ($m) => [$m->fecha_hora, $m->id]) as $m)
        <div>
            <b>{{ $acciones[$m->accion] ?? ucfirst($m->accion) }}</b>
            · @if ($m->accion === 'derivado'){{ $m->areaOrigen?->nombre }} → @endif{{ $m->areaDestino->nombre }}
            <small>{{ $m->fecha_hora->format('d/m/Y H:i') }}</small>
            @if ($m->proveido)<small>{{ $m->proveido }}</small>@endif
            @if ($m->accion === 'derivado' && $m->confirmado)<small>Recibido por el área el {{ $m->fecha_confirmacion->format('d/m/Y H:i') }}</small>@endif
        </div>
    @endforeach
</div>
