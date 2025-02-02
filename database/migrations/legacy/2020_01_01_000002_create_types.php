<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateTypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::unprepared(
            '
                CREATE TYPE public.typ_idlog AS (
                    idlog integer
                );

                CREATE TYPE public.typ_idpes AS (
                    idpes integer
                );
            '
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::unprepared(
            '
                DROP TYPE public.typ_idlog;

                DROP TYPE public.typ_idpes;
            '
        );
    }
}
