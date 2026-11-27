<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/** El contrato OpenAPI (public/docs/openapi.yaml) documenta todas las rutas de la API y Swagger UI lo muestra. */
class ContratoOpenApiTest extends TestCase
{
    public function test_cada_ruta_de_la_api_esta_en_el_contrato_openapi(): void
    {
        $contrato = file_get_contents(public_path('docs/openapi.yaml'));

        $rutas = collect(Route::getRoutes()->getRoutes())->filter(fn ($r) => str_starts_with($r->uri(), 'api/v1/'));
        $this->assertNotEmpty($rutas);

        foreach ($rutas as $ruta) {
            // api/v1/tipos-documento/{tipo} -> /tipos-documento/{id}
            $camino = preg_replace('/\{tipo\}/', '{id}', substr($ruta->uri(), strlen('api/v1')));
            $this->assertStringContainsString("\n  {$camino}:", $contrato, "Falta {$camino} en openapi.yaml");
            foreach (array_diff($ruta->methods(), ['HEAD']) as $metodo) {
                $this->assertMatchesRegularExpression(
                    '/\n  '.preg_quote($camino, '/').':(?:(?!\n  \/).)*\n    '.strtolower($metodo).':/s',
                    $contrato,
                    "Falta {$metodo} {$camino} en openapi.yaml"
                );
            }
        }
    }

    public function test_swagger_ui_esta_en_api_docs(): void
    {
        $this->get('/api-docs')->assertOk()->assertSee('swagger-ui', false)->assertSee('docs/openapi.yaml', false);
        $this->assertFileExists(public_path('docs/openapi.yaml'));
    }
}
