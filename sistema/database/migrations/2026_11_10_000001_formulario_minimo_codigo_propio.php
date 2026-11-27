<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Formulario público mínimo (reunión con Henry): el ciudadano ya no elige el tipo de documento,
 * Mesa de Partes lo completa al recepcionar. Se agrega el código propio opcional del ciudadano.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expedientes', function (Blueprint $table) {
            $table->unsignedBigInteger('tipo_documento_id')->nullable()->change();
            $table->string('codigo_propio', 20)->nullable()->unique()->after('numero');
        });
    }

    public function down(): void
    {
        Schema::table('expedientes', function (Blueprint $table) {
            $table->dropUnique(['codigo_propio']);
            $table->dropColumn('codigo_propio');
        });
    }
};
