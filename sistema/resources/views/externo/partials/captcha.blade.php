{{-- Issue #22: Cloudflare Turnstile si hay claves en el .env; si no, la pregunta de suma. --}}
@if (\App\Support\Captcha::usaTurnstile())
    <label>Verificación</label>
    <div class="cf-turnstile" data-sitekey="{{ \App\Support\Captcha::siteKey() }}" data-language="es"></div>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
@else
    <label for="captcha">{{ $pregunta }}</label>
    <input id="captcha" name="captcha" required inputmode="numeric" maxlength="3" autocomplete="off" placeholder="Tu respuesta">
@endif
@error('captcha')<div class="error">{{ $message }}</div>@enderror
