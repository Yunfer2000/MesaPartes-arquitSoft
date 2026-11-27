<?php

namespace App\Http\Controllers;

use App\Models\DocumentoAdjunto;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Descarga de adjuntos, solo para quien puede ver el expediente. */
class DocumentoController extends Controller
{
    public function show(DocumentoAdjunto $documento): StreamedResponse
    {
        Gate::authorize('view', $documento->expediente);

        return Storage::disk('local')->response($documento->ruta, $documento->nombre_archivo);
    }
}
