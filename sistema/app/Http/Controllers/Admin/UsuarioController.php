<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\RegistroAuditoria;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/** CU03 Gestionar usuarios y roles (Figura 18). */
class UsuarioController extends Controller
{
    public function index(Request $request): View
    {
        $editar = $request->query('editar') ? Usuario::findOrFail($request->query('editar')) : null;

        return view('admin.usuarios', [
            'usuarios' => Usuario::with('rol', 'area')
                ->orderBy('nombres')->get(),
            'roles' => Rol::orderBy('nombre')->get(),
            'areas' => Area::where('activa', true)->orderBy('nombre')->get(),
            'editar' => $editar,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);
        $usuario = Usuario::create($datos + ['activo' => true, 'fecha_registro' => now()]);
        RegistroAuditoria::registrar('crear usuario', $usuario, "{$usuario->correo} ({$usuario->rol->nombre})");

        return redirect()->route('admin.usuarios.index')->with('exito', 'Usuario creado.');
    }

    public function update(Request $request, Usuario $usuario): RedirectResponse
    {
        $datos = $this->validar($request, $usuario);
        if (empty($datos['contrasena'])) {
            unset($datos['contrasena']);
        }
        $usuario->update($datos);
        RegistroAuditoria::registrar('editar usuario', $usuario, "{$usuario->correo} ({$usuario->rol->nombre})");

        return redirect()->route('admin.usuarios.index')->with('exito', 'Usuario actualizado.');
    }

    /** 3a. Desactivar sin borrar el historial (o volver a activar). */
    public function estado(Request $request, Usuario $usuario): RedirectResponse
    {
        if ($usuario->is($request->user())) {
            return back()->with('aviso', 'No puedes desactivar tu propia cuenta.');
        }
        $usuario->update(['activo' => ! $usuario->activo]);
        RegistroAuditoria::registrar($usuario->activo ? 'activar usuario' : 'desactivar usuario', $usuario, $usuario->correo);

        return back()->with('exito', $usuario->activo ? 'Usuario activado.' : 'Usuario desactivado.');
    }

    private function validar(Request $request, ?Usuario $usuario = null): array
    {
        $rolArea = Rol::whereIn('nombre', [Rol::MESA_DE_PARTES, Rol::AREA])->pluck('id')->all();

        return $request->validate([
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            // 4a. Correo repetido
            'correo' => ['required', 'email', 'max:120', Rule::unique('usuarios', 'correo')->ignore($usuario)],
            'rol_id' => ['required', Rule::exists('roles', 'id')],
            'area_id' => [Rule::requiredIf(in_array((int) $request->input('rol_id'), $rolArea, true)), 'nullable', 'exists:areas,id'],
            'contrasena' => [$usuario ? 'nullable' : 'required', Password::min(8)],
        ]);
    }
}
