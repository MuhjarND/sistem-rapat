<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEvidenFieldsToNotulensiTugasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('notulensi_tugas', function (Blueprint $table) {
            $table->string('eviden_path', 255)->nullable()->after('status');
            $table->string('eviden_link', 255)->nullable()->after('eviden_path');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('notulensi_tugas', function (Blueprint $table) {
            $table->dropColumn(['eviden_path', 'eviden_link']);
        });
    }
}
