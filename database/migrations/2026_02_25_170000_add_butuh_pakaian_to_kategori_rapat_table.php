<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddButuhPakaianToKategoriRapatTable extends Migration
{
    public function up()
    {
        Schema::table('kategori_rapat', function (Blueprint $table) {
            if (!Schema::hasColumn('kategori_rapat', 'butuh_pakaian')) {
                $table->boolean('butuh_pakaian')->default(false)->after('nama');
            }
        });
    }

    public function down()
    {
        Schema::table('kategori_rapat', function (Blueprint $table) {
            if (Schema::hasColumn('kategori_rapat', 'butuh_pakaian')) {
                $table->dropColumn('butuh_pakaian');
            }
        });
    }
}

