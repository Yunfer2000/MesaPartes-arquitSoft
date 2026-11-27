<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Rol;
use App\Models\TipoDocumento;
use Illuminate\Database\Seeder;

/** Datos base que el sistema necesita para funcionar. Se ajustarán con el TUPA del instituto. */
class CatalogoSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            [Rol::MESA_DE_PARTES, 'Recepciona, observa, deriva y registra expedientes presenciales'],
            [Rol::AREA, 'Atiende los expedientes derivados a su área'],
            [Rol::ADMINISTRADOR, 'Gestiona usuarios, áreas, tipos de documento y reportes'],
        ] as [$nombre, $descripcion]) {
            Rol::updateOrCreate(['nombre' => $nombre], ['descripcion' => $descripcion]);
        }

        // Áreas del organigrama 2026 del instituto (Transparencia > Documentos de gestión).
        // El Administrador puede crear o desactivar áreas después, por ejemplo las temporales.
        foreach ([
            ['Mesa de Partes', Area::MESA_DE_PARTES],
            ['Dirección General', 'DG'],
            ['Unidad Académica', 'UAC'],
            ['Coordinación de Área Académica', 'CAA'],
            ['PE Administración de Redes y Comunicaciones', 'PE-ARC'],
            ['PE Electricidad Industrial', 'PE-EI'],
            ['PE Gestión Administrativa', 'PE-GA'],
            ['PE Mecatrónica Automotriz', 'PE-MA'],
            ['Unidad de Formación Continua', 'UFC'],
            ['Unidad de Investigación', 'UI'],
            ['Unidad de Bienestar y Empleabilidad', 'UBE'],
            ['Servicio de Bienestar (Consejería)', 'SBC'],
            ['Servicio Psicopedagógico', 'SPP'],
            ['Servicio de Empleabilidad', 'SEM'],
            ['Servicio Médico (Tópico)', 'TOP'],
            ['Área de Calidad', 'CAL'],
            ['Área Administrativa', 'UA'],
            ['Contabilidad', 'CON'],
            ['Patrimonio', 'PAT'],
            ['Almacén', 'ALM'],
            ['Caja', 'CAJ'],
            ['Personal', 'PER'],
            ['Abastecimiento', 'ABA'],
            ['Biblioteca', 'BIB'],
            ['FabLab', 'FAB'],
            ['Soporte de Infraestructura Tecnológica', 'SIT'],
            ['Secretaría Académica', 'SA'],
            // No está en el organigrama: es un comité temporal, pero el Ing. Henry pidió tratarlo como un área más
            ['Comité de Admisión', 'ADM'],
        ] as [$nombre, $sigla]) {
            Area::updateOrCreate(['sigla' => $sigla], ['nombre' => $nombre, 'activa' => true]);
        }
        // De qué unidad depende cada área según el organigrama 2026 (sirve para sugerir destinos al derivar)
        $dependeDe = [
            'DG' => ['UAC', 'UFC', 'UI', 'UBE', 'CAL', 'UA', 'SA', 'ADM'],
            'UAC' => ['CAA', 'PE-ARC', 'PE-EI', 'PE-GA', 'PE-MA'],
            'UBE' => ['SBC', 'SPP', 'SEM', 'TOP'],
            'UA' => [Area::MESA_DE_PARTES, 'CON', 'PAT', 'ALM', 'CAJ', 'PER', 'ABA', 'BIB', 'FAB', 'SIT'],
        ];
        foreach ($dependeDe as $padre => $hijas) {
            Area::whereIn('sigla', $hijas)->update(['area_padre_id' => Area::where('sigla', $padre)->value('id')]);
        }
        // "Programas de Estudio" (PE) se reemplazó por los cuatro programas: se desactiva si ya existía
        Area::where('sigla', 'PE')->update(['activa' => false]);

        foreach ([
            ['Solicitud', 'Pedido de un trámite o servicio (constancias, certificados, convalidaciones, etc.)'],
            ['Oficio', 'Comunicación formal de otra entidad'],
            ['Carta', 'Comunicación de una persona o institución'],
            ['Informe', 'Informe dirigido a un área del instituto'],
            ['Recurso', 'Reconsideración, apelación u otro recurso administrativo'],
            ['Otro', 'Documento no clasificado en los tipos anteriores'],
        ] as [$nombre, $descripcion]) {
            TipoDocumento::updateOrCreate(['nombre' => $nombre], ['descripcion' => $descripcion, 'activo' => true]);
        }
    }
}
