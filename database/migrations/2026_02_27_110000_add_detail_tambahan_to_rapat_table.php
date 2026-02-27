<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDetailTambahanToRapatTable extends Migration
{
    public function up()
    {
        Schema::table('rapat', function (Blueprint $table) {
            if (!Schema::hasColumn('rapat', 'detail_tambahan')) {
                $table->text('detail_tambahan')->nullable()->after('deskripsi');
            }
        });
    }

    public function down()
    {
        Schema::table('rapat', function (Blueprint $table) {
            if (Schema::hasColumn('rapat', 'detail_tambahan')) {
                $table->dropColumn('detail_tambahan');
            }
        });
    }
}

