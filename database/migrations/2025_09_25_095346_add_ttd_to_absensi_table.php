<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTtdToAbsensiTable extends Migration
{
    public function up()
    {
        Schema::table('absensi', function (Blueprint $table) {
            $table->string('ttd_path')->nullable()->after('waktu_absen');
            $table->string('ttd_hash', 64)->nullable()->after('ttd_path');
            $table->string('ttd_user_agent',255)->nullable()->after('ttd_hash');
            $table->string('ttd_timezone',64)->nullable()->after('ttd_user_agent');
        });
    }

    public function down()
    {
        Schema::table('absensi', function (Blueprint $table) {
            $table->dropColumn(['ttd_path','ttd_hash','ttd_user_agent','ttd_timezone']);
        });
    }
}
