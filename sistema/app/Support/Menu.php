<?php

namespace App\Support;

use App\Models\Area;
use App\Models\Copia;
use App\Models\Expediente;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Support\Facades\Route;

/** Opciones del menú según el rol del usuario (Figura 9 a 18). */
class Menu
{
    private const OPCIONES = [
        Rol::MESA_DE_PARTES => [
            ['mesa.bandeja', 'Bandeja de entrada', 'bandeja'],
            ['mesa.presencial', 'Registro presencial', 'presencial'],
            ['busqueda', 'Historial de expedientes', 'buscar'],
            ['reportes', 'Reportes', 'reportes'],
        ],
        Rol::AREA => [
            ['area.bandeja', 'Bandeja del área', 'bandeja'],
            ['pendientes', 'Pendientes', 'reloj'],
            ['busqueda', 'Historial de expedientes', 'buscar'],
            ['reportes', 'Reportes', 'reportes'],
        ],
        Rol::ADMINISTRADOR => [
            // Issue #24: primero lo que más usa el Administrador (usuarios y áreas), luego catálogos y consultas
            ['admin.usuarios.index', 'Usuarios', 'usuarios'],
            ['admin.areas.index', 'Áreas', 'areas'],
            ['admin.tipos.index', 'Tipos de documento', 'tipos'],
            ['pendientes', 'Pendientes', 'reloj'],
            ['busqueda', 'Historial de expedientes', 'buscar'],
            ['reportes', 'Reportes', 'reportes'],
            ['admin.auditoria', 'Auditoría', 'auditoria'],
        ],
    ];

    /** @return array<int, array{url: string, texto: string, icono: string, activo: bool, contador: int}> */
    public static function para(Usuario $usuario): array
    {
        return collect(self::OPCIONES[$usuario->rol->nombre] ?? [])
            ->filter(fn ($o) => Route::has($o[0]))
            ->filter(fn ($o) => $o[0] !== 'pendientes' || $usuario->vePendientes())
            ->map(fn ($o) => ['url' => route($o[0]), 'texto' => $o[1], 'icono' => $o[2], 'activo' => request()->routeIs($o[0].'*'),
                'contador' => self::contador($o[0], $usuario)])
            ->values()->all();
    }

    /** Mejora de interfaz, paso 3: cuántos expedientes esperan en la bandeja, para verlo sin entrar. */
    private static function contador(string $ruta, Usuario $usuario): int
    {
        return match ($ruta) {
            'mesa.bandeja' => Expediente::whereIn('estado', [Expediente::PRESENTADO, Expediente::SUBSANADO])
                ->enArea(Area::mesaDePartes()->id)->count(),
            'area.bandeja' => $usuario->area_id
                ? Expediente::where('estado', Expediente::DERIVADO)->enArea($usuario->area_id)->count()
                    + Copia::where('area_id', $usuario->area_id)->where('leido', false)->count()
                : 0,
            default => 0,
        };
    }

    /** Pantalla inicial de cada rol (CU02, paso 4). */
    public static function inicio(Usuario $usuario): string
    {
        return route(self::OPCIONES[$usuario->rol->nombre][0][0]);
    }
}
