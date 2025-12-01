<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEvidenNoteToNotulensiTugasTable extends Migration
{
    public function up()
    {
        Schema::table('notulensi_tugas', function (Blueprint $table) {
            if (!Schema::hasColumn('notulensi_tugas', 'eviden_note')) {
                $table->text('eviden_note')->nullable()->after('eviden_link');
            }
        });
    }

    public function down()
    {
        Schema::table('notulensi_tugas', function (Blueprint $table) {
            if (Schema::hasColumn('notulensi_tugas', 'eviden_note')) {
                $table->dropColumn('eviden_note');
            }
        });
    }
}
