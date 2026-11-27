<?php

namespace Database\Seeders;

use App\Models\Administrado;
use App\Models\Area;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Database\Seeder;

/** Usuarios de prueba. Contraseña de todos: password */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $rol = fn (string $n) => Rol::where('nombre', $n)->value('id');
        $area = fn (string $s) => Area::where('sigla', $s)->value('id');

        $crear = fn (array $datos) => Usuario::updateOrCreate(['correo' => $datos['correo']], $datos + [
            'contrasena' => 'password',
            'activo' => true,
        ]);

        $crear(['rol_id' => $rol(Rol::ADMINISTRADOR), 'nombres' => 'Administrador', 'apellidos' => 'del Sistema',
            'correo' => 'admin@demo.test']);
        $mp = $crear(['rol_id' => $rol(Rol::MESA_DE_PARTES), 'area_id' => $area('MP'), 'nombres' => 'Operador',
            'apellidos' => 'Mesa de Partes', 'correo' => 'mesadepartes@demo.test']);
        $sa = $crear(['rol_id' => $rol(Rol::AREA), 'area_id' => $area('SA'), 'nombres' => 'Responsable',
            'apellidos' => 'Secretaría Académica', 'correo' => 'secretaria@demo.test']);
        $dg = $crear(['rol_id' => $rol(Rol::AREA), 'area_id' => $area('DG'), 'nombres' => 'Responsable',
            'apellidos' => 'Dirección General', 'correo' => 'direccion@demo.test']);

        Area::where('sigla', 'MP')->update(['responsable_id' => $mp->id]);
        Area::where('sigla', 'SA')->update(['responsable_id' => $sa->id]);
        Area::where('sigla', 'DG')->update(['responsable_id' => $dg->id]);

        // Remitente de ejemplo (sin cuenta) para probar el autocompletado del formulario público
        Administrado::updateOrCreate(['numero_documento' => '70451236'], [
            'tipo_doc_identidad' => 'DNI', 'nombres' => 'José Luis',
            'apellido_paterno' => 'Quispe', 'apellido_materno' => 'Huamán', 'tipo_administrado' => 'Estudiante',
            'correo' => 'estudiante@demo.test', 'telefono' => '966123456',
        ]);
    }
}
