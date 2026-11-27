<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mejora de interfaz, paso 1: cada área guarda de qué unidad depende según el organigrama 2026.
 * Sirve para agrupar las áreas y sugerir destinos al derivar. No bloquea ninguna derivación.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('areas', function (Blueprint $table) {
            $table->foreignId('area_padre_id')->nullable()->after('sigla')->constrained('areas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('areas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('area_padre_id');
        });
    }
};
