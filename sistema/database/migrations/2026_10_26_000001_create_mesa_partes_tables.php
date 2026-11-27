<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tablas del trámite documentario (Figura 55 y Tabla 29 del informe).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('areas', function (Blueprint $table) {
            $table->foreign('responsable_id')->references('id')->on('usuarios')->nullOnDelete();
        });

        Schema::create('administrados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->nullable()->unique()->constrained('usuarios');
            $table->string('tipo_doc_identidad', 20)->default('DNI');
            $table->string('numero_documento', 20)->unique();
            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->string('tipo_administrado', 30)->comment('Estudiante, Egresado, Docente, Personal o Ciudadano');
            $table->string('correo', 120)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('direccion', 200)->nullable();
            $table->timestamps();
        });

        Schema::create('tipos_documento', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->unique();
            $table->string('descripcion', 200)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('expedientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('administrado_id')->constrained('administrados');
            $table->foreignId('tipo_documento_id')->constrained('tipos_documento');
            $table->foreignId('area_destino_id')->constrained('areas')->comment('Área a la que va dirigido el trámite');
            $table->string('numero', 20)->unique();
            $table->string('asunto', 255);
            $table->text('detalle')->nullable();
            $table->string('modalidad', 10)->comment('Virtual o Presencial');
            $table->dateTime('fecha_presentacion');
            $table->dateTime('fecha_recepcion')->nullable();
            $table->string('estado', 20)->index();
            $table->timestamps();
        });

        Schema::create('observaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expediente_id')->constrained('expedientes')->cascadeOnDelete();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios');
            $table->string('motivo', 255);
            $table->text('detalle')->nullable();
            $table->dateTime('fecha');
            $table->timestamps();
        });

        Schema::create('subsanaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('observacion_id')->constrained('observaciones')->cascadeOnDelete();
            $table->text('descripcion');
            $table->dateTime('fecha');
            $table->timestamps();
        });

        Schema::create('respuestas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expediente_id')->constrained('expedientes')->cascadeOnDelete();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios');
            $table->text('contenido');
            $table->dateTime('fecha');
            $table->timestamps();
        });

        Schema::create('documentos_adjuntos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expediente_id')->constrained('expedientes')->cascadeOnDelete();
            $table->foreignId('subsanacion_id')->nullable()->constrained('subsanaciones');
            $table->foreignId('respuesta_id')->nullable()->constrained('respuestas');
            $table->string('nombre_archivo', 200);
            $table->string('ruta', 255);
            $table->string('tipo_mime', 80);
            $table->unsignedInteger('tamano_kb');
            $table->boolean('es_principal')->default(false);
            $table->dateTime('fecha_carga');
            $table->timestamps();
        });

        Schema::create('movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expediente_id')->constrained('expedientes')->cascadeOnDelete();
            $table->foreignId('area_origen_id')->nullable()->constrained('areas');
            $table->foreignId('area_destino_id')->constrained('areas');
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->comment('Usuario que registra el movimiento');
            $table->foreignId('usuario_destino_id')->nullable()->constrained('usuarios');
            $table->string('accion', 20);
            $table->string('proveido', 255)->nullable();
            $table->dateTime('fecha_hora');
            $table->boolean('confirmado')->default(false);
            $table->dateTime('fecha_confirmacion')->nullable();
            $table->timestamps();
        });

        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('administrado_id')->constrained('administrados');
            $table->foreignId('expediente_id')->constrained('expedientes')->cascadeOnDelete();
            $table->string('evento', 20);
            $table->text('mensaje');
            $table->dateTime('fecha');
            $table->boolean('enviada')->default(false);
            $table->boolean('leida')->default(false);
            $table->timestamps();
        });

        Schema::create('registros_auditoria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios');
            $table->string('accion', 50);
            $table->string('tabla', 50);
            $table->unsignedBigInteger('registro_id')->nullable();
            $table->text('detalle')->nullable();
            $table->string('ip', 45)->nullable();
            $table->dateTime('fecha_hora');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registros_auditoria');
        Schema::dropIfExists('notificaciones');
        Schema::dropIfExists('movimientos');
        Schema::dropIfExists('documentos_adjuntos');
        Schema::dropIfExists('respuestas');
        Schema::dropIfExists('subsanaciones');
        Schema::dropIfExists('observaciones');
        Schema::dropIfExists('expedientes');
        Schema::dropIfExists('tipos_documento');
        Schema::dropIfExists('administrados');
        Schema::table('areas', function (Blueprint $table) {
            $table->dropForeign(['responsable_id']);
        });
    }
};
