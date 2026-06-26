<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTanggalSelesaiToRapatTable extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('rapat', 'tanggal_selesai')) {
            Schema::table('rapat', function (Blueprint $table) {
                $table->date('tanggal_selesai')->nullable()->after('tanggal');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('rapat', 'tanggal_selesai')) {
            Schema::table('rapat', function (Blueprint $table) {
                $table->dropColumn('tanggal_selesai');
            });
        }
    }
}
