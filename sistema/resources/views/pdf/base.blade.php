{{-- Plantilla de los PDF (RF26). DomPDF solo entiende CSS sencillo: tablas, sin flex ni variables. --}}
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>@yield('titulo')</title>
<style>
    @page { margin: 28mm 20mm 22mm 20mm; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
    .cabecera { position: fixed; top: -20mm; left: 0; right: 0; text-align: center; border-bottom: 2px solid #0f766e; padding-bottom: 6px; }
    .cabecera .inst { font-size: 12px; font-weight: bold; }
    .cabecera .sub { font-size: 10px; color: #555; }
    .pie { position: fixed; bottom: -14mm; left: 0; right: 0; text-align: center; font-size: 9px; color: #777; }
    h1 { font-size: 16px; text-align: center; margin: 0 0 4px; }
    .numero { font-size: 22px; font-weight: bold; color: #0f766e; text-align: center; margin: 4px 0 14px; }
    table.datos { width: 100%; border-collapse: collapse; }
    table.datos td { padding: 6px 8px; border-bottom: 1px solid #e3e3e8; vertical-align: top; }
    table.datos td.campo { width: 32%; color: #444; font-weight: bold; }
    .numero .recuadro { border: 2px solid #1b1b38; padding: 4px 14px; color: #1b1b38; }
    .fecha { text-align: center; font-size: 10px; color: #666; margin: -6px 0 6px; }
    .seccion { font-size: 9.5px; font-weight: bold; letter-spacing: 1px; color: #1b1b38; background: #eef0f6; border-left: 3px solid #1b1b38; padding: 4px 8px; margin: 14px 0 0; }
    .contenido { border: 1px solid #e3e3e8; padding: 10px; margin-top: 12px; white-space: pre-line; }
    .huella { font-family: DejaVu Sans Mono, monospace; font-size: 8px; color: #555; word-wrap: break-word; }
    table.firma { width: 100%; margin-top: 18px; border: 1px dashed #9a9ab0; }
    table.firma td { padding: 8px; vertical-align: middle; }
    .muted { color: #666; }
</style>
</head>
<body>
    <div class="cabecera">
        <div class="inst">IESTP MANUEL ANTONIO HIERRO POZO</div>
        <div class="sub">Mesa de Partes Virtual · Huamanga, Ayacucho</div>
    </div>
    <div class="pie">Documento generado el {{ now()->format('d/m/Y H:i') }} · Verifique su autenticidad en {{ rtrim(config('app.url'), '/') }}/verificar</div>

    @yield('cuerpo')

    @php $urlVerificacion = \App\Services\Verificacion::url($codigo); @endphp
    <table class="firma">
        <tr>
            <td style="width:120px"><img src="{{ \App\Services\Verificacion::qrDataUri($urlVerificacion, 220) }}" width="110" height="110" alt="QR"></td>
            <td>
                <b>Firma electrónica del sistema</b><br>
                Firmado por: {{ $firmante }}<br>
                Código de verificación: <b>{{ $codigo }}</b><br>
                <span class="muted">Escanee el QR o ingrese a {{ $urlVerificacion }}</span><br>
                <span class="muted" style="font-size:9px">Esta firma no reemplaza a la firma digital con certificado (Ley 27269).</span>
            </td>
        </tr>
    </table>
</body>
</html>
