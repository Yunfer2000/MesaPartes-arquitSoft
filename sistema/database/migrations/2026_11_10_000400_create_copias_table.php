<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Derivación de uno a muchos (reunión con el Ing. Henry): al derivar, el expediente va a UN área que lo
 * atiende y, si se quiere, se envía copia "para conocimiento" a otras áreas. La copia no cambia la
 * ubicación del expediente: solo avisa a esas áreas, que lo pueden ver y marcar como leído.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('copias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expediente_id')->constrained('expedientes')->cascadeOnDelete();
            $table->foreignId('movimiento_id')->constrained('movimientos')->cascadeOnDelete()->comment('Derivación que envió la copia');
            $table->foreignId('area_id')->constrained('areas')->comment('Área que recibe la copia');
            $table->foreignId('usuario_id')->constrained('usuarios')->comment('Usuario que envió la copia');
            $table->string('proveido', 255)->nullable();
            $table->dateTime('fecha_hora');
            $table->boolean('leido')->default(false);
            $table->dateTime('fecha_lectura')->nullable();
            $table->foreignId('leido_por_id')->nullable()->constrained('usuarios');
            $table->timestamps();
            $table->unique(['movimiento_id', 'area_id']);
            $table->index(['area_id', 'leido']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('copias');
    }
};
