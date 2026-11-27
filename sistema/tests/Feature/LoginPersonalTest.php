<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Issue #33: la pantalla de login es solo para el personal (RNF17); no ofrece presentar trámites. */
class LoginPersonalTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected $seeder = DatabaseSeeder::class;

    public function test_el_login_no_muestra_presentar_tramite(): void
    {
        $this->get('/ingresar')->assertOk()
            ->assertSee('Acceso del personal')
            ->assertDontSee('Presentar trámite')
            ->assertDontSee('Presenta tus trámites')
            ->assertDontSee(route('externo.create'));
    }
}
