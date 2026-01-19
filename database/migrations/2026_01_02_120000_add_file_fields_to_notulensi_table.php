<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFileFieldsToNotulensiTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('notulensi', function (Blueprint $table) {
            if (!Schema::hasColumn('notulensi', 'file_path')) {
                $table->string('file_path')->nullable()->after('isi');
            }
            if (!Schema::hasColumn('notulensi', 'file_name')) {
                $table->string('file_name')->nullable()->after('file_path');
            }
            if (!Schema::hasColumn('notulensi', 'file_mime')) {
                $table->string('file_mime', 120)->nullable()->after('file_name');
            }
            if (!Schema::hasColumn('notulensi', 'file_size')) {
                $table->unsignedBigInteger('file_size')->nullable()->after('file_mime');
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
        Schema::table('notulensi', function (Blueprint $table) {
            if (Schema::hasColumn('notulensi', 'file_size')) {
                $table->dropColumn('file_size');
            }
            if (Schema::hasColumn('notulensi', 'file_mime')) {
                $table->dropColumn('file_mime');
            }
            if (Schema::hasColumn('notulensi', 'file_name')) {
                $table->dropColumn('file_name');
            }
            if (Schema::hasColumn('notulensi', 'file_path')) {
                $table->dropColumn('file_path');
            }
        });
    }
}
