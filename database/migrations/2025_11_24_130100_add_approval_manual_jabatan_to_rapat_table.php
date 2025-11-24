<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddApprovalManualJabatanToRapatTable extends Migration
{
    public function up()
    {
        Schema::table('rapat', function (Blueprint $table) {
            if (!Schema::hasColumn('rapat', 'approval1_jabatan_manual')) {
                $table->string('approval1_jabatan_manual', 150)->nullable()->after('approval1_user_id');
            }
            if (!Schema::hasColumn('rapat', 'approval2_jabatan_manual')) {
                $table->string('approval2_jabatan_manual', 150)->nullable()->after('approval2_user_id');
            }
        });
    }

    public function down()
    {
        Schema::table('rapat', function (Blueprint $table) {
            if (Schema::hasColumn('rapat', 'approval1_jabatan_manual')) {
                $table->dropColumn('approval1_jabatan_manual');
            }
            if (Schema::hasColumn('rapat', 'approval2_jabatan_manual')) {
                $table->dropColumn('approval2_jabatan_manual');
            }
        });
    }
}
