<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDibuatOlehToNotulensiTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('notulensi', function (Blueprint $table) {
            $table->unsignedBigInteger('dibuat_oleh')->nullable()->after('id_rapat');
        });
    }

    public function down()
    {
        Schema::table('notulensi', function (Blueprint $table) {
            $table->dropColumn('dibuat_oleh');
        });
    }
}
