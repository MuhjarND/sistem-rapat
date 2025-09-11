<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddArchivedAtToLaporanFilesTable extends Migration
{
    public function up()
    {
        Schema::table('laporan_files', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('is_archived');
        });
    }

    public function down()
    {
        Schema::table('laporan_files', function (Blueprint $table) {
            $table->dropColumn('archived_at');
        });
    }
}
