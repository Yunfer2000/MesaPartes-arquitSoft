<?php

namespace App\Services;

use App\Exceptions\ReglaNegocio;
use App\Models\RegistroAuditoria;
use App\Models\TipoDocumento;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Catálogo de tipos de documento con caché (Sprint 2: estrategia de caché).
 * La lista casi no cambia y se pide en cada formulario, así que se guarda en caché
 * y se borra cuando el Administrador crea, edita o elimina un tipo (cache-aside con invalidación).
 */
class CatalogoTiposDocumento
{
    public const CLAVE = 'catalogo.tipos_documento';

    public const SEGUNDOS = 3600;

    /** @return Collection<int, TipoDocumento> */
    public function listar(?bool $activo = null): Collection
    {
        $todos = Cache::remember(self::CLAVE, self::SEGUNDOS, fn () => TipoDocumento::orderBy('nombre')->get());

        return $activo === null ? $todos : $todos->where('activo', $activo)->values();
    }

    public function crear(array $datos): TipoDocumento
    {
        $tipo = TipoDocumento::create($datos + ['activo' => true]);
        RegistroAuditoria::registrar('crear tipo de documento', $tipo, $tipo->nombre);
        $this->olvidar();

        return $tipo;
    }

    public function actualizar(TipoDocumento $tipo, array $datos): TipoDocumento
    {
        $tipo->update($datos);
        RegistroAuditoria::registrar('editar tipo de documento', $tipo, $tipo->nombre);
        $this->olvidar();

        return $tipo;
    }

    /** Solo se elimina si ningún expediente lo usa; si no, hay que desactivarlo (integridad de datos). */
    public function eliminar(TipoDocumento $tipo): void
    {
        if ($tipo->expedientes()->exists()) {
            throw new ReglaNegocio('No se puede eliminar: hay expedientes con este tipo de documento. Desactívalo en su lugar.');
        }
        RegistroAuditoria::registrar('eliminar tipo de documento', $tipo, $tipo->nombre);
        $tipo->delete();
        $this->olvidar();
    }

    public function olvidar(): void
    {
        Cache::forget(self::CLAVE);
    }
}
