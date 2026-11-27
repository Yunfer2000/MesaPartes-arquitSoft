<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TipoDocumentoRequest;
use App\Http\Resources\TipoDocumentoResource;
use App\Models\TipoDocumento;
use App\Services\CatalogoTiposDocumento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * Recurso tipos-documento: CRUD REST. Equivale al recurso "products" del caso ayaPet.
 * El controlador solo traduce HTTP ↔ servicio; las reglas y la caché están en CatalogoTiposDocumento.
 */
class TipoDocumentoController extends Controller
{
    public function __construct(private CatalogoTiposDocumento $catalogo) {}

    /** GET /api/v1/tipos-documento?activo=1 */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate(['activo' => ['sometimes', 'boolean']]);
        $activo = $request->has('activo') ? $request->boolean('activo') : null;

        return TipoDocumentoResource::collection($this->catalogo->listar($activo));
    }

    /** GET /api/v1/tipos-documento/{id} */
    public function show(TipoDocumento $tipo): TipoDocumentoResource
    {
        return new TipoDocumentoResource($tipo);
    }

    /** POST /api/v1/tipos-documento → 201 Created */
    public function store(TipoDocumentoRequest $request): JsonResponse
    {
        $tipo = $this->catalogo->crear($request->validated());

        return (new TipoDocumentoResource($tipo))->response()->setStatusCode(201)
            ->header('Location', route('api.v1.tipos.show', $tipo));
    }

    /** PUT /api/v1/tipos-documento/{id} → 200 OK */
    public function update(TipoDocumentoRequest $request, TipoDocumento $tipo): TipoDocumentoResource
    {
        return new TipoDocumentoResource($this->catalogo->actualizar($tipo, $request->validated()));
    }

    /** DELETE /api/v1/tipos-documento/{id} → 204 No Content (409 si está en uso) */
    public function destroy(TipoDocumento $tipo): Response
    {
        $this->catalogo->eliminar($tipo);

        return response()->noContent();
    }
}
