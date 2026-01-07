<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddJenisPakaianToRapatTable extends Migration
{
    public function up()
    {
        Schema::table('rapat', function (Blueprint $table) {
            if (!Schema::hasColumn('rapat', 'jenis_pakaian')) {
                $table->string('jenis_pakaian', 120)->nullable()->after('tempat');
            }
        });
    }

    public function down()
    {
        Schema::table('rapat', function (Blueprint $table) {
            if (Schema::hasColumn('rapat', 'jenis_pakaian')) {
                $table->dropColumn('jenis_pakaian');
            }
        });
    }
}
