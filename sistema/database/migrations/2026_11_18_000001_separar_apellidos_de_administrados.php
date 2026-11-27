<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Apellido paterno y materno en columnas separadas, como en el DNI.
 * Reemplaza a la columna "apellidos": el nombre completo se arma solo al mostrarlo (modelo Administrado).
 */
return new class extends Migration
{
    /** Partículas de apellidos compuestos: "De la Cruz", "Del Pino", "San Martín", "Mac Gregor". */
    private const PARTICULAS = ['de', 'del', 'la', 'las', 'los', 'san', 'santa', 'mac', 'mc', 'van', 'von', 'da', 'di', 'y'];

    public function up(): void
    {
        Schema::table('administrados', function (Blueprint $table) {
            $table->string('apellido_paterno', 60)->nullable()->after('nombres');
            $table->string('apellido_materno', 60)->nullable()->after('apellido_paterno');
        });

        // Separa los apellidos ya guardados lo mejor posible: el paterno lleva sus partículas ("De la Cruz").
        DB::table('administrados')->whereNotNull('apellidos')->orderBy('id')->each(function ($fila) {
            [$paterno, $materno] = $this->separar($fila->apellidos);
            DB::table('administrados')->where('id', $fila->id)
                ->update(['apellido_paterno' => $paterno, 'apellido_materno' => $materno]);
        });

        // Ya separados, la columna antigua se elimina para no guardar el mismo dato dos veces.
        Schema::table('administrados', function (Blueprint $table) {
            $table->dropColumn('apellidos');
        });
    }

    /** Deshace el cambio: vuelve a unir los apellidos en una sola columna. */
    public function down(): void
    {
        Schema::table('administrados', function (Blueprint $table) {
            $table->string('apellidos', 100)->nullable()->after('nombres');
        });

        DB::table('administrados')->orderBy('id')->each(function ($fila) {
            DB::table('administrados')->where('id', $fila->id)
                ->update(['apellidos' => trim($fila->apellido_paterno.' '.$fila->apellido_materno)]);
        });

        Schema::table('administrados', function (Blueprint $table) {
            $table->dropColumn(['apellido_paterno', 'apellido_materno']);
        });
    }

    /** "De la Cruz Vargas" → ["De la Cruz", "Vargas"]; "Huamán" → ["Huamán", null]. */
    private function separar(string $apellidos): array
    {
        $palabras = preg_split('/\s+/', trim($apellidos), -1, PREG_SPLIT_NO_EMPTY);
        $paterno = [];
        while ($palabras) {
            $palabra = array_shift($palabras);
            $paterno[] = $palabra;
            if (! in_array(mb_strtolower($palabra), self::PARTICULAS, true)) {
                break;
            }
        }

        return [implode(' ', $paterno) ?: null, $palabras ? implode(' ', $palabras) : null];
    }
};
