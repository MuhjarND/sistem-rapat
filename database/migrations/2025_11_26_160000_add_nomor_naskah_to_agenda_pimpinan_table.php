<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNomorNaskahToAgendaPimpinanTable extends Migration
{
    public function up()
    {
        Schema::table('agenda_pimpinan', function (Blueprint $table) {
            if (!Schema::hasColumn('agenda_pimpinan', 'nomor_naskah')) {
                $table->string('nomor_naskah', 200)->nullable()->after('judul');
            }
        });
    }

    public function down()
    {
        Schema::table('agenda_pimpinan', function (Blueprint $table) {
            if (Schema::hasColumn('agenda_pimpinan', 'nomor_naskah')) {
                $table->dropColumn('nomor_naskah');
            }
        });
    }
}
