<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddScheduleFieldsToRapatTable extends Migration
{
    public function up()
    {
        Schema::table('rapat', function (Blueprint $table) {
            if (!Schema::hasColumn('rapat', 'schedule_type')) {
                $table->enum('schedule_type', ['bulanan','triwulanan','tahunan'])->nullable()->after('deskripsi');
            }
            if (!Schema::hasColumn('rapat', 'approval_enqueued_at')) {
                $table->timestamp('approval_enqueued_at')->nullable()->after('schedule_type');
            }
        });
    }

    public function down()
    {
        Schema::table('rapat', function (Blueprint $table) {
            if (Schema::hasColumn('rapat', 'approval_enqueued_at')) {
                $table->dropColumn('approval_enqueued_at');
            }
            if (Schema::hasColumn('rapat', 'schedule_type')) {
                $table->dropColumn('schedule_type');
            }
        });
    }
}
