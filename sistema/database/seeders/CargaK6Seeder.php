<?php

namespace Database\Seeders;

use App\Models\Administrado;
use App\Models\Area;
use App\Models\Expediente;
use App\Models\Usuario;
use App\Services\FlujoExpediente;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;

/**
 * Datos para la prueba de carga con k6 (Sprint 2): un expediente recepcionado y derivado,
 * con N.° EXP-AAAA-000001 y DNI 70451236, para que la consulta devuelva un recorrido real.
 * Uso: php artisan db:seed --class=CargaK6Seeder (después de migrate --seed).
 */
class CargaK6Seeder extends Seeder
{
    public function run(FlujoExpediente $flujo): void
    {
        Notification::fake(); // sin correos reales

        $administrado = Administrado::firstOrCreate(['numero_documento' => '70451236'], [
            'tipo_doc_identidad' => 'DNI', 'nombres' => 'José Luis', 'apellido_paterno' => 'Quispe',
            'apellido_materno' => 'Huamán', 'tipo_administrado' => 'Estudiante',
            'correo' => null,
        ]);

        if (Expediente::where('administrado_id', $administrado->id)->exists()) {
            return;
        }

        $pdf = UploadedFile::fake()->createWithContent('solicitud.pdf', '%PDF-1.4 prueba de carga');
        $expediente = $flujo->presentar($administrado, null, [
            'area_destino_id' => Area::mesaDePartes()->id, 'asunto' => 'Prueba de carga k6',
        ], $pdf);

        $operador = Usuario::where('correo', 'mesadepartes@demo.test')->first();
        if ($operador) {
            $flujo->recepcionar($expediente, $operador, ['tipo_documento_id' => \App\Models\TipoDocumento::first()?->id, 'folios' => 1]);
            $flujo->derivar($expediente->fresh(), $operador, Area::where('sigla', 'SA')->firstOrFail(), null, 'Atender', []);
        }

        $this->command?->info("Expediente para k6: {$expediente->numero} · DNI 70451236");
    }
}
