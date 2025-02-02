<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlteraTipoVinculoProfessorTurmaExcluidos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('modules.professor_turma_excluidos', function (Blueprint $table) {
            $table->integer('tipo_vinculo')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('modules.professor_turma_excluidos', function (Blueprint $table) {
            $table->integer('tipo_vinculo')->change();
        });
    }
}
