<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Expediente;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** CU18 Monitorear plazos y pendientes (RF24, RF25). */
class PendientesController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->vePendientes(), 403);

        // Expedientes que alguien del instituto todavía tiene que atender.
        $pendientes = Expediente::with('administrado', 'movimientos.areaDestino')
            ->whereNotIn('estado', [Expediente::FINALIZADO, Expediente::OBSERVADO])
            ->get()
            ->sortByDesc(fn (Expediente $e) => $e->diasEnArea())
            ->values();

        $contadores = $pendientes->countBy(fn (Expediente $e) => $e->semaforo());

        // #32: áreas con más expedientes en rojo que el límite. Se calcula antes de filtrar,
        // para que el aviso salga siempre, aunque se esté mirando otra área u otro estado.
        $alertas = $pendientes
            ->filter(fn (Expediente $e) => $e->semaforo() === 'rojo')
            ->groupBy(fn (Expediente $e) => $e->movimientos->last()?->area_destino_id)
            ->filter(fn ($grupo, $areaId) => $areaId && $grupo->count() > config('plazos.alerta_area'))
            ->map(fn ($grupo) => ['area' => $grupo->first()->movimientos->last()->areaDestino, 'total' => $grupo->count()])
            ->sortByDesc('total')
            ->values();

        if ($request->filled('area_id')) {
            $pendientes = $pendientes->filter(fn (Expediente $e) => $e->movimientos->last()?->area_destino_id == $request->area_id);
        }

        // Filtro por estado del trámite (antes era por color del semáforo).
        $estados = array_values(array_diff(Expediente::ESTADOS, [Expediente::FINALIZADO, Expediente::OBSERVADO]));

        $estadoActual = in_array($request->estado, $estados, true) ? $request->estado : null;

        if ($estadoActual) {
            $pendientes = $pendientes->filter(fn (Expediente $e) => $e->estado === $estadoActual);
        }

        return view('pendientes.index', [
            'pendientes' => $pendientes,
            'contadores' => $contadores,
            'alertas' => $alertas,
            'estados' => $estados,
            'estadoActual' => $estadoActual,
            'areas' => Area::where('activa', true)->orderBy('nombre')->get(),
        ]);
    }
}
