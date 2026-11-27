<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

/**
 * SDD: control de las especificaciones de la carpeta specs/.
 * Revisa que cada criterio de aceptación tenga Dado/Cuando/Entonces y que las pruebas
 * que nombra existan de verdad. Así una especificación no queda desactualizada.
 */
class EspecificacionesTest extends TestCase
{
    private const SECCIONES = ['## Objetivo', '## Flujo básico', '## Reglas de negocio', '## Criterios de aceptación'];

    /** @return array<string, string> nombre del archivo => contenido */
    private function especificaciones(): array
    {
        // CUxx: casos de uso de ICONIX. APIxx: endpoints de la API REST v1 (Sprint 2).
        $archivos = array_merge(glob(dirname(__DIR__, 2).'/specs/CU*.md'), glob(dirname(__DIR__, 2).'/specs/API*.md'));
        $this->assertNotEmpty($archivos, 'No hay especificaciones en specs/');

        $lista = [];
        foreach ($archivos as $archivo) {
            $lista[basename($archivo)] = file_get_contents($archivo);
        }

        return $lista;
    }

    /** @return array<string, string> código del criterio => texto del criterio */
    private function criterios(string $contenido): array
    {
        preg_match_all('/^### (CA-(?:CU|API)\d{2}-\d{2})\b.*?(?=^### |\z)/ms', $contenido, $m, PREG_SET_ORDER);

        return array_column(array_map(fn ($c) => [$c[1], $c[0]], $m), 1, 0);
    }

    public function test_cada_especificacion_tiene_sus_secciones(): void
    {
        foreach ($this->especificaciones() as $nombre => $contenido) {
            $this->assertMatchesRegularExpression('/^(CU|API)\d{2}-[a-z0-9-]+\.md$/', $nombre, "$nombre: el nombre debe ser CUxx-nombre-en-minusculas.md o APIxx-...");
            $cu = strtok($nombre, '-');
            $this->assertStringStartsWith("# $cu ", $contenido, "$nombre: el título debe empezar con «# $cu »");
            $secciones = str_starts_with($cu, 'API') ? [...self::SECCIONES, '## Tareas técnicas'] : self::SECCIONES;
            foreach ($secciones as $seccion) {
                $this->assertStringContainsString($seccion, $contenido, "$nombre: falta la sección «{$seccion}»");
            }
            $this->assertNotEmpty($this->criterios($contenido), "$nombre: no tiene criterios de aceptación (### CA-{$cu}-01 ...)");
        }
    }

    public function test_cada_criterio_tiene_dado_cuando_entonces_y_prueba(): void
    {
        $vistos = [];
        foreach ($this->especificaciones() as $nombre => $contenido) {
            $cu = strtok($nombre, '-');
            foreach ($this->criterios($contenido) as $codigo => $texto) {
                $this->assertStringStartsWith("CA-$cu-", $codigo, "$nombre: el criterio $codigo no es de $cu");
                $this->assertArrayNotHasKey($codigo, $vistos, "$codigo está repetido");
                $vistos[$codigo] = true;
                foreach (['Dado', 'Cuando', 'Entonces', 'Cubre', 'Prueba'] as $parte) {
                    $this->assertTrue(str_contains($texto, "- **{$parte}"), "$codigo: falta la línea «{$parte}»");
                }
            }
        }
    }

    public function test_las_pruebas_nombradas_existen(): void
    {
        $raiz = dirname(__DIR__, 2);
        foreach ($this->especificaciones() as $contenido) {
            foreach ($this->criterios($contenido) as $codigo => $texto) {
                preg_match_all('/`(tests\/[\w\/]+\.php)::(\w+)`/', $texto, $pruebas, PREG_SET_ORDER);
                $this->assertNotEmpty($pruebas, "$codigo: la línea Prueba debe ser `tests/Feature/Archivo.php::test_nombre`");
                foreach ($pruebas as [, $archivo, $funcion]) {
                    $this->assertFileExists("$raiz/$archivo", "$codigo: no existe $archivo");
                    $existe = preg_match('/function\s+'.preg_quote($funcion, '/').'\s*\(/', file_get_contents("$raiz/$archivo"));
                    $this->assertSame(1, $existe, "$codigo: no existe la prueba $funcion en $archivo");
                }
            }
        }
    }
}
