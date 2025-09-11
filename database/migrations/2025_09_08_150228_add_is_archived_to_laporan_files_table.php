<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsArchivedToLaporanFilesTable extends Migration
{
    public function up()
    {
        Schema::table('laporan_files', function (Blueprint $table) {
            $table->boolean('is_archived')->default(0)->after('id'); // Default: belum diarsipkan
        });
    }

    public function down()
    {
        Schema::table('laporan_files', function (Blueprint $table) {
            $table->dropColumn('is_archived');
        });
    }
}
