<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPenerimaToAgendaPimpinanTable extends Migration
{
    public function up()
    {
        Schema::table('agenda_pimpinan', function (Blueprint $table) {
            if (!Schema::hasColumn('agenda_pimpinan', 'penerima_json')) {
                $table->text('penerima_json')->nullable()->after('pimpinan_user_id');
            }
        });
    }

    public function down()
    {
        Schema::table('agenda_pimpinan', function (Blueprint $table) {
            if (Schema::hasColumn('agenda_pimpinan', 'penerima_json')) {
                $table->dropColumn('penerima_json');
            }
        });
    }
}
