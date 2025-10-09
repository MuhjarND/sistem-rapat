<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddParticipantsNotifiedAtToRapat extends Migration
{
    public function up()
    {
        Schema::table('rapat', function (Blueprint $table) {
            if (!Schema::hasColumn('rapat', 'participants_notified_at')) {
                $table->timestamp('participants_notified_at')->nullable()->after('absensi_approved_at');
            }
        });
    }

    public function down()
    {
        Schema::table('rapat', function (Blueprint $table) {
            if (Schema::hasColumn('rapat', 'participants_notified_at')) {
                $table->dropColumn('participants_notified_at');
            }
        });
    }
}
