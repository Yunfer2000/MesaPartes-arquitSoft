<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Support\Menu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** CU02 Iniciar sesión (Figura 38). */
class SesionController extends Controller
{
    public function create(): View
    {
        return view('auth.inicio');
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'correo' => ['required', 'email'],
            'contrasena' => ['required', 'string'],
        ]);

        $clave = Str::lower($datos['correo']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($clave, 5)) {
            throw ValidationException::withMessages([
                'correo' => 'Demasiados intentos. Vuelve a intentarlo en '.RateLimiter::availableIn($clave).' segundos.',
            ]);
        }

        if (! Auth::attempt(['correo' => $datos['correo'], 'password' => $datos['contrasena']], $request->boolean('recordar'))) {
            RateLimiter::hit($clave);
            // 3a. Credenciales incorrectas
            throw ValidationException::withMessages(['correo' => 'El correo o la contraseña no son correctos.']);
        }
        RateLimiter::clear($clave);

        /** @var Usuario $usuario */
        $usuario = Auth::user();
        if (! $usuario->activo) {
            // 3b. Usuario inactivo
            Auth::logout();
            throw ValidationException::withMessages(['correo' => 'Tu cuenta está deshabilitada. Comunícate con Mesa de Partes.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(Menu::inicio($usuario));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
