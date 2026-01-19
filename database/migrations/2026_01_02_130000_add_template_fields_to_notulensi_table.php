<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTemplateFieldsToNotulensiTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('notulensi', function (Blueprint $table) {
            if (!Schema::hasColumn('notulensi', 'template')) {
                $table->string('template', 10)->default('a')->after('agenda');
            }
            if (!Schema::hasColumn('notulensi', 'susunan_agenda')) {
                $table->text('susunan_agenda')->nullable()->after('template');
            }
            if (!Schema::hasColumn('notulensi', 'hasil_rapat')) {
                $table->text('hasil_rapat')->nullable()->after('susunan_agenda');
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
            if (Schema::hasColumn('notulensi', 'hasil_rapat')) {
                $table->dropColumn('hasil_rapat');
            }
            if (Schema::hasColumn('notulensi', 'susunan_agenda')) {
                $table->dropColumn('susunan_agenda');
            }
            if (Schema::hasColumn('notulensi', 'template')) {
                $table->dropColumn('template');
            }
        });
    }
}
