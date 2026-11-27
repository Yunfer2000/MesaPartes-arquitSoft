<?php

use App\Services\Verificacion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Firma electrónica del sistema (RF26): huella SHA-256 de cada archivo y código de verificación
 * en cada constancia (expedientes) y en cada respuesta. Los registros que ya existen también
 * reciben su código y su huella.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documentos_adjuntos', function (Blueprint $table) {
            $table->char('hash_sha256', 64)->nullable()->after('tamano_kb');
        });
        Schema::table('expedientes', function (Blueprint $table) {
            $table->string('codigo_verificacion', 14)->nullable()->unique()->after('codigo_propio');
        });
        Schema::table('respuestas', function (Blueprint $table) {
            $table->string('codigo_verificacion', 14)->nullable()->unique()->after('contenido');
        });

        foreach (['expedientes', 'respuestas'] as $tabla) {
            foreach (DB::table($tabla)->whereNull('codigo_verificacion')->pluck('id') as $id) {
                DB::table($tabla)->where('id', $id)->update(['codigo_verificacion' => Verificacion::nuevoCodigo()]);
            }
        }
        foreach (DB::table('documentos_adjuntos')->whereNull('hash_sha256')->get(['id', 'ruta']) as $doc) {
            if (Storage::disk('local')->exists($doc->ruta)) {
                $hash = hash('sha256', Storage::disk('local')->get($doc->ruta));
                DB::table('documentos_adjuntos')->where('id', $doc->id)->update(['hash_sha256' => $hash]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('respuestas', function (Blueprint $table) {
            $table->dropUnique(['codigo_verificacion']);
            $table->dropColumn('codigo_verificacion');
        });
        Schema::table('expedientes', function (Blueprint $table) {
            $table->dropUnique(['codigo_verificacion']);
            $table->dropColumn('codigo_verificacion');
        });
        Schema::table('documentos_adjuntos', fn (Blueprint $table) => $table->dropColumn('hash_sha256'));
    }
};
