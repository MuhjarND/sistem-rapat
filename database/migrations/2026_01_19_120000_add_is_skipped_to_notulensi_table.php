<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsSkippedToNotulensiTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('notulensi', function (Blueprint $table) {
            if (!Schema::hasColumn('notulensi', 'is_skipped')) {
                $table->boolean('is_skipped')->default(0)->after('file_size');
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
            if (Schema::hasColumn('notulensi', 'is_skipped')) {
                $table->dropColumn('is_skipped');
            }
        });
    }
}
