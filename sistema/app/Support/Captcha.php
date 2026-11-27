<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Freno contra envíos automáticos en el formulario público (RNF05).
 * Si en el .env están TURNSTILE_SITE_KEY y TURNSTILE_SECRET_KEY, usa Cloudflare Turnstile (issue #22).
 * Si están vacíos (en tu PC o en las pruebas), usa la pregunta de suma de siempre.
 */
class Captcha
{
    public const CLAVE = 'captcha';

    /** Campo que Turnstile agrega solo al formulario. */
    public const CAMPO_TURNSTILE = 'cf-turnstile-response';

    private const VERIFICAR = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public static function usaTurnstile(): bool
    {
        return filled(config('services.turnstile.site_key')) && filled(config('services.turnstile.secret_key'));
    }

    public static function siteKey(): ?string
    {
        return config('services.turnstile.site_key');
    }

    /** Genera una nueva pregunta de suma y guarda la respuesta en la sesión. */
    public static function generar(): string
    {
        $a = random_int(2, 9);
        $b = random_int(1, 9);
        session([self::CLAVE => (string) ($a + $b)]);

        return "¿Cuánto es {$a} + {$b}?";
    }

    /** Con Turnstile pregunta a Cloudflare; si no, compara la suma. */
    public static function valido(?string $respuesta, ?Request $request = null): bool
    {
        if (self::usaTurnstile()) {
            return self::validarTurnstile($request?->input(self::CAMPO_TURNSTILE), $request?->ip());
        }

        $esperada = session()->pull(self::CLAVE);

        return $esperada !== null && trim((string) $respuesta) === $esperada;
    }

    public static function error(): string
    {
        return self::usaTurnstile()
            ? 'No pudimos comprobar que eres una persona. Vuelve a marcar la casilla de verificación.'
            : 'La respuesta de la suma no es correcta.';
    }

    private static function validarTurnstile(?string $token, ?string $ip): bool
    {
        if (blank($token)) {
            return false;
        }

        try {
            $respuesta = Http::asForm()->timeout(5)->post(self::VERIFICAR, [
                'secret' => config('services.turnstile.secret_key'),
                'response' => $token,
                'remoteip' => $ip,
            ]);

            return $respuesta->successful() && $respuesta->json('success') === true;
        } catch (Throwable) {
            // Si Cloudflare no responde, no se acepta el envío: la persona puede volver a intentarlo
            return false;
        }
    }
}
