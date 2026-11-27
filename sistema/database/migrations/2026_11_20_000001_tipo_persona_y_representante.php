<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tipo de persona en el formulario público (Natural / Jurídica).
 * La entidad (RUC y razón social) vive en administrados; el representante que presenta
 * cada trámite se guarda en el expediente, porque puede cambiar de un trámite a otro.
 * Una entidad con RUC no tiene nombres propios, por eso "nombres" pasa a ser opcional.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expedientes', function (Blueprint $table) {
            $table->string('representante_dni', 8)->nullable();
            $table->string('representante_nombres', 100)->nullable();
            $table->string('representante_apellido_paterno', 60)->nullable();
            $table->string('representante_apellido_materno', 60)->nullable();
            $table->string('representante_celular', 20)->nullable();
            $table->string('representante_correo', 120)->nullable();
        });

        Schema::table('administrados', function (Blueprint $table) {
            $table->string('nombres', 100)->nullable()->change();
        });
    }

    public function down(): void
    {
        // Antes de volver a exigir "nombres", las entidades sin nombre toman su razón social
        DB::table('administrados')->whereNull('nombres')
            ->update(['nombres' => DB::raw("COALESCE(razon_social, '')")]);

        Schema::table('administrados', function (Blueprint $table) {
            $table->string('nombres', 100)->nullable(false)->change();
        });

        Schema::table('expedientes', function (Blueprint $table) {
            $table->dropColumn(['representante_dni', 'representante_nombres', 'representante_apellido_paterno',
                'representante_apellido_materno', 'representante_celular', 'representante_correo']);
        });
    }
};
