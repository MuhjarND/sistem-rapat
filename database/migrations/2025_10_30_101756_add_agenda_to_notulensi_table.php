<?php

// database/migrations/2025_10_XX_000000_add_agenda_to_notulensi_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAgendaToNotulensiTable extends Migration
{
    public function up()
    {
        Schema::table('notulensi', function (Blueprint $table) {
            if (!Schema::hasColumn('notulensi','agenda')) {
                $table->text('agenda')->nullable()->after('id_rapat'); // sesuaikan posisi
            }
        });
    }

    public function down()
    {
        Schema::table('notulensi', function (Blueprint $table) {
            if (Schema::hasColumn('notulensi','agenda')) {
                $table->dropColumn('agenda');
            }
        });
    }
}

