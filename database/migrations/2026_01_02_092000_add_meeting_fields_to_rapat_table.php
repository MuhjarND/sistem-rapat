<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMeetingFieldsToRapatTable extends Migration
{
    public function up()
    {
        Schema::table('rapat', function (Blueprint $table) {
            if (!Schema::hasColumn('rapat', 'meeting_id')) {
                $table->string('meeting_id', 120)->nullable()->after('is_virtual');
            }
            if (!Schema::hasColumn('rapat', 'meeting_passcode')) {
                $table->string('meeting_passcode', 120)->nullable()->after('meeting_id');
            }
        });
    }

    public function down()
    {
        Schema::table('rapat', function (Blueprint $table) {
            if (Schema::hasColumn('rapat', 'meeting_passcode')) {
                $table->dropColumn('meeting_passcode');
            }
            if (Schema::hasColumn('rapat', 'meeting_id')) {
                $table->dropColumn('meeting_id');
            }
        });
    }
}
