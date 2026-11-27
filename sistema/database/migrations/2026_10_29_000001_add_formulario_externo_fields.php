<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campos del formulario de trámite sin cuenta (basado en la Mesa de Partes Virtual de la UNSCH):
 * razón social para remitentes con RUC y número de folios del expediente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('administrados', function (Blueprint $table) {
            $table->string('razon_social', 150)->nullable()->after('apellidos');
        });

        Schema::table('expedientes', function (Blueprint $table) {
            $table->unsignedSmallInteger('folios')->nullable()->after('detalle');
        });
    }

    public function down(): void
    {
        Schema::table('administrados', fn (Blueprint $table) => $table->dropColumn('razon_social'));
        Schema::table('expedientes', fn (Blueprint $table) => $table->dropColumn('folios'));
    }
};
