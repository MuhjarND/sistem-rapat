<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTujuanSuratToRapatTable extends Migration
{
    public function up()
    {
        Schema::table('rapat', function (Blueprint $table) {
            if (!Schema::hasColumn('rapat', 'tujuan_surat')) {
                $table->text('tujuan_surat')->nullable()->after('detail_tambahan');
            }
        });
    }

    public function down()
    {
        Schema::table('rapat', function (Blueprint $table) {
            if (Schema::hasColumn('rapat', 'tujuan_surat')) {
                $table->dropColumn('tujuan_surat');
            }
        });
    }
}
