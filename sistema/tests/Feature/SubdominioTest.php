<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** RNF17: el login y las pantallas del personal solo responden en su subdominio. */
class SubdominioTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;
    protected $seeder = DatabaseSeeder::class;

    private const PUBLICO = 'http://mesadepartes.test';
    private const PERSONAL = 'http://personal.mesadepartes.test';

    /** Las rutas se arman al iniciar la aplicación, por eso el subdominio se define justo antes. */
    public function createApplication()
    {
        $app = require Application::inferBasePath().'/bootstrap/app.php';
        $app->booting(fn () => $app['config']->set('app.staff_domain', 'personal.mesadepartes.test'));
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    public function test_la_pagina_publica_no_muestra_ni_abre_el_login(): void
    {
        $this->get(self::PUBLICO.'/')->assertOk()->assertSee('Presentar trámite')
            ->assertDontSee('Acceso del personal')->assertDontSee('Iniciar sesión');
        $this->get(self::PUBLICO.'/tramite/nuevo')->assertOk();

        $this->get(self::PUBLICO.'/ingresar')->assertNotFound();
        $this->post(self::PUBLICO.'/ingresar', ['correo' => 'admin@demo.test', 'contrasena' => 'password'])->assertNotFound();
        $this->get(self::PUBLICO.'/mesa-de-partes')->assertNotFound();
        $this->get(self::PUBLICO.'/admin/usuarios')->assertNotFound();
    }

    public function test_el_personal_ingresa_por_su_subdominio(): void
    {
        $this->get(self::PERSONAL.'/')->assertRedirect(self::PERSONAL.'/ingresar');
        $this->get(self::PERSONAL.'/ingresar')->assertOk()->assertSee('Acceso al sistema');

        $this->post(self::PERSONAL.'/ingresar', ['correo' => 'mesadepartes@demo.test', 'contrasena' => 'password'])
            ->assertRedirect(self::PERSONAL.'/mesa-de-partes');
        $this->get(self::PERSONAL.'/mesa-de-partes')->assertOk();
        $this->get(self::PERSONAL.'/')->assertRedirect(self::PERSONAL.'/mesa-de-partes');
    }

    public function test_sin_sesion_el_subdominio_pide_ingresar(): void
    {
        $this->get(self::PERSONAL.'/mesa-de-partes')->assertRedirect(self::PERSONAL.'/ingresar');
    }
}
