<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Expediente;
use App\Models\Rol;
use App\Models\TipoDocumento;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** CU14 Buscar expedientes (Figura 16). Mejora: se llama «Historial de expedientes» y muestra los 15 últimos. */
class BusquedaController extends Controller
{
    public const ULTIMOS = 15;

    public function index(Request $request): View
    {
        $f = $request->validate([
            // Issue #30: búsqueda básica, un solo campo para el N.° de expediente o el documento
            'q' => ['nullable', 'string', 'max:20'],
            'numero' => ['nullable', 'string', 'max:20'],
            'administrado' => ['nullable', 'string', 'max:100'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            'area_id' => ['nullable', 'exists:areas,id'],
            'tipo_documento_id' => ['nullable', 'exists:tipos_documento,id'],
            'estado' => ['nullable', 'in:'.implode(',', Expediente::ESTADOS)],
            'asunto' => ['nullable', 'string', 'max:100'],
        ]);
        $buscando = collect($f)->filter()->isNotEmpty();

        $resultados = $buscando ? self::visibles($request)
            ->with('administrado', 'tipoDocumento')
            ->when(isset($f['q']) ? trim($f['q']) : null, fn ($q, $v) => $q->where(fn ($q) => $q
                // Parte del número («000012» o «EXP-2026-000012»), o el documento exacto del remitente o de su representante
                ->where('numero', 'like', "%{$v}%")
                ->orWhere('representante_dni', $v)
                ->orWhereHas('administrado', fn ($q) => $q->where('numero_documento', $v))))
            ->when($f['numero'] ?? null, fn ($q, $v) => $q->where('numero', 'like', "%{$v}%"))
            ->when($f['administrado'] ?? null, fn ($q, $v) => $q->whereHas('administrado', fn ($q) => $q
                ->where('numero_documento', $v)
                // Cada palabra debe estar en los nombres o en algún apellido: "Rosa", "Quispe Huamán" o "Rosa Pariona"
                ->orWhere(function ($q) use ($v) {
                    foreach (preg_split('/\s+/', trim($v)) as $palabra) {
                        $q->where(fn ($q) => $q->where('nombres', 'like', "%{$palabra}%")
                            ->orWhere('apellido_paterno', 'like', "%{$palabra}%")
                            ->orWhere('apellido_materno', 'like', "%{$palabra}%"));
                    }
                })))
            ->when($f['desde'] ?? null, fn ($q, $v) => $q->whereDate('fecha_presentacion', '>=', $v))
            ->when($f['hasta'] ?? null, fn ($q, $v) => $q->whereDate('fecha_presentacion', '<=', $v))
            ->when($f['area_id'] ?? null, fn ($q, $v) => $q->enArea((int) $v))
            ->when($f['tipo_documento_id'] ?? null, fn ($q, $v) => $q->where('tipo_documento_id', $v))
            ->when($f['estado'] ?? null, fn ($q, $v) => $q->where('estado', $v))
            ->when($f['asunto'] ?? null, fn ($q, $v) => $q->where('asunto', 'like', "%{$v}%"))
            ->latest('fecha_presentacion')->paginate(25)->withQueryString() : null;

        return view('busqueda.index', [
            'resultados' => $resultados,
            // Sin búsqueda: los 15 últimos expedientes que el usuario puede ver, del más reciente al más antiguo
            'ultimos' => $buscando ? null : self::visibles($request)->with('administrado', 'tipoDocumento')
                ->latest('fecha_presentacion')->latest('id')->limit(self::ULTIMOS)->get(),
            // El panel de filtros se abre solo si hay alguno en uso (todo menos la búsqueda básica)
            'conFiltros' => collect($f)->except('q')->filter()->isNotEmpty(),
            'areas' => Area::orderBy('nombre')->get(),
            'tipos' => TipoDocumento::orderBy('nombre')->get(),
        ]);
    }

    /** CU14 paso 4: detalle con historial, para cualquier rol del personal que pueda verlo. */
    public function show(Expediente $expediente): View
    {
        Gate::authorize('view', $expediente);

        return view('expedientes.gestion', MesaPartesController::datosGestion($expediente));
    }

    /** Expedientes que el usuario puede ver según su rol (RNF15). */
    public static function visibles(Request $request): Builder
    {
        $usuario = $request->user();

        return Expediente::query()->when($usuario->tieneRol(Rol::AREA), fn ($q) => $q
            ->whereHas('movimientos', fn ($q) => $q->where('area_destino_id', $usuario->area_id)));
    }
}
