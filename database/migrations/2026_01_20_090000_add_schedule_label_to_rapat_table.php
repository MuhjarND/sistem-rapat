<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddScheduleLabelToRapatTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rapat', function (Blueprint $table) {
            if (!Schema::hasColumn('rapat', 'schedule_label')) {
                $table->string('schedule_label', 120)->nullable()->after('schedule_type');
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
            if (Schema::hasColumn('rapat', 'schedule_label')) {
                $table->dropColumn('schedule_label');
            }
        });
    }
}
