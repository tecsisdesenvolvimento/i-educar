<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AlteraDataReferenciaEducacenso extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('pmieducar.instituicao')
            ->where('ativo', 1)
            ->update(['data_educacenso' => '2021-05-26']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('pmieducar.instituicao')
            ->where('ativo', 1)
            ->update(['data_educacenso' => '2020-05-27']);
    }
}
