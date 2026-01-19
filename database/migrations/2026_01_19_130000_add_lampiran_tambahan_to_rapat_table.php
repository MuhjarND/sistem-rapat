<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLampiranTambahanToRapatTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rapat', function (Blueprint $table) {
            if (!Schema::hasColumn('rapat', 'lampiran_tambahan_path')) {
                $table->string('lampiran_tambahan_path')->nullable()->after('meeting_passcode');
            }
            if (!Schema::hasColumn('rapat', 'lampiran_tambahan_nama')) {
                $table->string('lampiran_tambahan_nama')->nullable()->after('lampiran_tambahan_path');
            }
            if (!Schema::hasColumn('rapat', 'lampiran_tambahan_mime')) {
                $table->string('lampiran_tambahan_mime', 80)->nullable()->after('lampiran_tambahan_nama');
            }
            if (!Schema::hasColumn('rapat', 'lampiran_tambahan_size')) {
                $table->unsignedBigInteger('lampiran_tambahan_size')->nullable()->after('lampiran_tambahan_mime');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rapat', function (Blueprint $table) {
            if (Schema::hasColumn('rapat', 'lampiran_tambahan_path')) {
                $table->dropColumn('lampiran_tambahan_path');
            }
            if (Schema::hasColumn('rapat', 'lampiran_tambahan_nama')) {
                $table->dropColumn('lampiran_tambahan_nama');
            }
            if (Schema::hasColumn('rapat', 'lampiran_tambahan_mime')) {
                $table->dropColumn('lampiran_tambahan_mime');
            }
            if (Schema::hasColumn('rapat', 'lampiran_tambahan_size')) {
                $table->dropColumn('lampiran_tambahan_size');
            }
        });
    }
}
