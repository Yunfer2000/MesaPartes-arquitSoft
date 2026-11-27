<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Expediente;
use App\Models\Movimiento;
use App\Models\Rol;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** CU15 Generar reportes (Figura 17). */
class ReporteController extends Controller
{
    public function index(Request $request): View|BinaryFileResponse
    {
        $f = $request->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            'area_id' => ['nullable', 'exists:areas,id'],
        ]);
        $desde = CarbonImmutable::parse($f['desde'] ?? now()->startOfMonth())->startOfDay();
        $hasta = CarbonImmutable::parse($f['hasta'] ?? now())->endOfDay();
        // Un responsable de área solo ve los datos de su área
        $areaId = $request->user()->tieneRol(Rol::AREA) ? $request->user()->area_id : ($f['area_id'] ?? null);

        $datos = $this->calcular($desde, $hasta, $areaId ? (int) $areaId : null);

        if ($request->query('formato') === 'excel') {
            return $this->excel($datos, $desde, $hasta);
        }

        return view('reportes.index', $datos + [
            'desde' => $desde, 'hasta' => $hasta, 'areaId' => $areaId,
            'areas' => Area::orderBy('nombre')->get(),
        ]);
    }

    private function calcular(CarbonImmutable $desde, CarbonImmutable $hasta, ?int $areaId): array
    {
        // 3. Totales del periodo calculados desde Expediente y Movimiento
        $movs = fn (string $accion) => Movimiento::where('accion', $accion)
            ->whereBetween('fecha_hora', [$desde, $hasta])
            ->when($areaId, fn ($q) => $q->where($accion === 'derivado' ? 'area_destino_id' : 'area_origen_id', $areaId));

        $expedientes = Expediente::whereBetween('fecha_presentacion', [$desde, $hasta])
            ->when($areaId, fn ($q) => $q->whereHas('movimientos', fn ($q) => $q->where('area_destino_id', $areaId)));

        $totales = [
            'Presentados' => (clone $expedientes)->count(),
            'Recibidos' => $movs('recibido')->distinct('expediente_id')->count('expediente_id'),
            'Derivados' => $movs('derivado')->count(),
            'Observados' => $movs('observado')->distinct('expediente_id')->count('expediente_id'),
            'Finalizados' => $movs('finalizado')->count(),
            'Pendientes' => (clone $expedientes)->where('estado', '!=', Expediente::FINALIZADO)->count(),
        ];

        $porArea = Movimiento::where('accion', 'derivado')->whereBetween('fecha_hora', [$desde, $hasta])
            ->when($areaId, fn ($q) => $q->where('area_destino_id', $areaId))
            ->selectRaw('area_destino_id, count(*) as total')->groupBy('area_destino_id')
            ->with('areaDestino')->get()
            ->map(fn ($m) => ['area' => $m->areaDestino->nombre, 'total' => (int) $m->total])
            ->sortByDesc('total')->values();

        // Tiempo promedio de atención: desde la presentación hasta la respuesta, por área que respondió
        $tiempos = Movimiento::with('expediente:id,fecha_presentacion', 'areaDestino:id,nombre')
            ->where('accion', 'finalizado')->whereBetween('fecha_hora', [$desde, $hasta])
            ->when($areaId, fn ($q) => $q->where('area_destino_id', $areaId))
            ->get()
            ->groupBy(fn ($m) => $m->areaDestino->nombre)
            ->map(fn (Collection $g) => round($g->avg(fn ($m) => $m->expediente->fecha_presentacion->diffInHours($m->fecha_hora) / 24), 1))
            ->sortKeys();

        return compact('totales', 'porArea', 'tiempos');
    }

    /** 4. Descarga en Excel (.xlsx), con la librería OpenSpout. */
    private function excel(array $d, CarbonImmutable $desde, CarbonImmutable $hasta): BinaryFileResponse
    {
        $archivo = tempnam(sys_get_temp_dir(), 'reporte');
        $negrita = (new Style)->withFontBold(true);
        $titulo = fn (string ...$celdas) => Row::fromValuesWithStyle($celdas, $negrita);

        $excel = new Writer;
        $excel->openToFile($archivo);
        $excel->getCurrentSheet()->setName('Reporte');
        $excel->addRow($titulo('Reporte de Mesa de Partes', $desde->format('d/m/Y').' al '.$hasta->format('d/m/Y')));
        $excel->addRow(Row::fromValues([]));
        $excel->addRow($titulo('Indicador', 'Total'));
        foreach ($d['totales'] as $k => $v) {
            $excel->addRow(Row::fromValues([$k, $v]));
        }
        $excel->addRow(Row::fromValues([]));
        $excel->addRow($titulo('Área destino', 'Derivaciones'));
        foreach ($d['porArea'] as $fila) {
            $excel->addRow(Row::fromValues([$fila['area'], $fila['total']]));
        }
        $excel->addRow(Row::fromValues([]));
        $excel->addRow($titulo('Área', 'Días promedio de atención'));
        foreach ($d['tiempos'] as $k => $v) {
            $excel->addRow(Row::fromValues([$k, $v]));
        }
        $excel->close();

        $nombre = 'reporte_'.$desde->format('Ymd').'_'.$hasta->format('Ymd').'.xlsx';

        return response()->download($archivo, $nombre, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }
}
