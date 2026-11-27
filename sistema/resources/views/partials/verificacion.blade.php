{{-- RF26 Firma electrónica del sistema. Recibe $codigo, y opcionales $firmante y $qr (true en constancias impresas). --}}
@php $urlVerificacion = \App\Services\Verificacion::url($codigo); @endphp
<div class="verificacion" style="display:flex;gap:14px;align-items:center;border:1px dashed #b8b8cc;border-radius:8px;padding:10px 12px;margin-top:12px">
    @if ($qr ?? false)
        <div style="width:110px;height:110px;flex:none" aria-label="Código QR para verificar">{!! \App\Services\Verificacion::qrSvg($urlVerificacion, 110) !!}</div>
    @endif
    <div style="font-size:13px">
        <b>Firma electrónica del sistema</b>
        @isset($firmante)<br>Firmado por: {{ $firmante }}@endisset
        <br>Código de verificación: <b>{{ $codigo }}</b>
        <br><span class="muted">Verifícalo en <a href="{{ $urlVerificacion }}">{{ $urlVerificacion }}</a></span>
    </div>
</div>
