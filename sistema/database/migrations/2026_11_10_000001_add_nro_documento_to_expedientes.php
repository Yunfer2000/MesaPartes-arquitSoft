<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bloque 2 (reunión con Henry): Mesa de Partes completa al recepcionar el N.° del documento
 * presentado, por ejemplo "Solicitud N.° 015-2026". Tipo y folios ya existen en la tabla.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expedientes', function (Blueprint $table) {
            $table->string('nro_documento', 50)->nullable()->after('folios');
        });
    }

    public function down(): void
    {
        Schema::table('expedientes', fn (Blueprint $table) => $table->dropColumn('nro_documento'));
    }
};
