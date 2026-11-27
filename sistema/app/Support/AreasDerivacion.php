<?php

namespace App\Support;

use App\Models\Area;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Ordena las áreas para la pantalla Derivar según el organigrama: primero las sugeridas
 * (las que se conectan con el área que deriva) y luego todas agrupadas por unidad.
 * Solo sugiere: se puede derivar a cualquier área activa, como pidió el Ing. Henry.
 */
class AreasDerivacion
{
    /**
     * @param  Collection<int, Area>  $areas  áreas activas
     * @return array{sugeridas: Collection<int, Area>, grupos: array<string, Collection<int, Area>>}
     */
    public static function para(Collection $areas, ?Area $origen): array
    {
        $destinos = $areas->reject(fn (Area $a) => $origen && $a->id === $origen->id)->values();
        $dg = $areas->firstWhere('sigla', 'DG');
        $esDeDireccion = fn (?Area $a) => $a && $dg && $a->area_padre_id === $dg->id;

        $ids = match (true) {
            // Mesa de Partes recibe todo: Dirección General y las unidades que dependen de ella
            $origen === null || $origen->sigla === Area::MESA_DE_PARTES => $destinos
                ->filter(fn (Area $a) => $a->id === $dg?->id || $esDeDireccion($a))->pluck('id'),
            // Dirección General: las unidades que dependen de ella
            $origen->id === $dg?->id => $destinos->filter($esDeDireccion)->pluck('id'),
            // Una unidad: sus subáreas, Dirección General y Mesa de Partes
            $esDeDireccion($origen) => $destinos->filter(fn (Area $a) => $a->area_padre_id === $origen->id
                || $a->id === $dg?->id || $a->sigla === Area::MESA_DE_PARTES)->pluck('id'),
            // Una subárea: su jefe, sus áreas hermanas y Mesa de Partes
            default => $destinos->filter(fn (Area $a) => ($origen->area_padre_id && ($a->id === $origen->area_padre_id
                || $a->area_padre_id === $origen->area_padre_id)) || $a->sigla === Area::MESA_DE_PARTES)->pluck('id'),
        };

        return [
            'sugeridas' => self::ordenar($destinos->whereIn('id', $ids)),
            'grupos' => self::agrupar($destinos, $areas, $dg),
        ];
    }

    /**
     * Agrupa por unidad: cada unidad que depende de Dirección General encabeza su grupo.
     *
     * @return array<string, Collection<int, Area>>
     */
    public static function agrupar(Collection $destinos, Collection $todas, ?Area $dg = null): array
    {
        $dg ??= $todas->firstWhere('sigla', 'DG');
        $porId = $todas->keyBy('id');
        $unidadDe = function (Area $a) use ($porId, $dg): string {
            if ($a->id === $dg?->id) {
                return 'Dirección General';
            }
            // Sube por el organigrama hasta la unidad que depende de Dirección General
            $actual = $a;
            for ($i = 0; $i < 5 && $actual->area_padre_id && $actual->area_padre_id !== $dg?->id; $i++) {
                $actual = $porId->get($actual->area_padre_id) ?? $actual;
            }

            return $actual->area_padre_id === $dg?->id ? $actual->nombre : 'Otras áreas';
        };

        return $destinos->groupBy($unidadDe)
            ->map(fn (Collection $g, string $unidad) => self::ordenar($g, $unidad))
            ->sortBy(fn ($g, string $unidad) => match ($unidad) { 'Dirección General' => '0', 'Otras áreas' => 'z', default => '1'.Str::ascii($unidad) })
            ->all();
    }

    /**
     * Mejora: la pantalla Derivar pide primero el área principal y luego, si se quiere, una subárea.
     * Principal = Dirección General, las unidades que dependen de ella y las áreas sin jefe.
     *
     * @param  Collection<int, Area>  $areas  áreas activas
     * @return array{principales: Collection<int, Area>, subareas: Collection<int, Area>}
     */
    public static function porNivel(Collection $areas): array
    {
        $dg = $areas->firstWhere('sigla', 'DG');
        $esPrincipal = fn (Area $a) => ! $a->area_padre_id || $a->area_padre_id === $dg?->id;

        return [
            'principales' => $areas->filter($esPrincipal)
                ->sortBy(fn (Area $a) => ($a->id === $dg?->id ? '0' : ($a->area_padre_id ? '1' : '2')).Str::ascii($a->nombre))->values(),
            'subareas' => $areas->reject($esPrincipal)->sortBy(fn (Area $a) => Str::ascii($a->nombre))->values(),
        ];
    }

    /** La jefatura de la unidad va primero y luego el resto por nombre. */
    private static function ordenar(Collection $areas, ?string $unidad = null): Collection
    {
        return $areas->sortBy(fn (Area $a) => ($a->nombre === $unidad ? '0' : '1').Str::ascii($a->nombre))->values();
    }
}
