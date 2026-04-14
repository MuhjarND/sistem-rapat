<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIzinKeteranganToAbsensiTable extends Migration
{
    public function up()
    {
        Schema::table('absensi', function (Blueprint $table) {
            if (!Schema::hasColumn('absensi', 'izin_keterangan')) {
                $table->string('izin_keterangan', 50)->nullable()->after('status');
            }
        });
    }

    public function down()
    {
        Schema::table('absensi', function (Blueprint $table) {
            if (Schema::hasColumn('absensi', 'izin_keterangan')) {
                $table->dropColumn('izin_keterangan');
            }
        });
    }
}
