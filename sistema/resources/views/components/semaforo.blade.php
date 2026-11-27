{{-- RF24: círculo de color con los días que el expediente lleva en el área. --}}
@props(['expediente'])
@php $color = $expediente->semaforo(); @endphp
@if ($color)
    @php $dias = $expediente->diasEnArea(); @endphp
    <span class="semaforo semaforo-{{ $color }}" title="{{ $dias }} {{ config('plazos.habiles') ? 'día(s) hábil(es)' : 'día(s)' }} en el área">{{ $dias }}</span>
@endif
