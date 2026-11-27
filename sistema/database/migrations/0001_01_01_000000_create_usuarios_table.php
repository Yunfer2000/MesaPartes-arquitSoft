<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tablas de acceso: roles, áreas y usuarios (Figura 55 del informe).
 * La clave foránea areas.responsable_id se agrega en la migración siguiente,
 * porque depende de usuarios.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50)->unique()->comment('Administrado, Mesa de Partes, Área o Administrador');
            $table->string('descripcion', 150)->nullable();
            $table->timestamps();
        });

        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('responsable_id')->nullable();
            $table->string('nombre', 100)->unique();
            $table->string('sigla', 15);
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });

        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rol_id')->constrained('roles');
            $table->foreignId('area_id')->nullable()->constrained('areas');
            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->string('correo', 120)->unique();
            $table->string('contrasena');
            $table->boolean('activo')->default(true);
            $table->dateTime('fecha_registro')->useCurrent();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('usuarios');
        Schema::dropIfExists('areas');
        Schema::dropIfExists('roles');
    }
};
