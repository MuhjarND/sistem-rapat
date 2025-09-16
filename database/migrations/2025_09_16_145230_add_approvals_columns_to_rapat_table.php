<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddApprovalsColumnsToRapatTable extends Migration
{
    public function up()
    {
        Schema::table('rapat', function (Blueprint $table) {
            if (!Schema::hasColumn('rapat', 'approval1_user_id')) {
                $table->unsignedBigInteger('approval1_user_id')->nullable()->after('id_kategori');
            }
            if (!Schema::hasColumn('rapat', 'approval2_user_id')) {
                $table->unsignedBigInteger('approval2_user_id')->nullable()->after('approval1_user_id');
            }
            // BELUM ada foreign key di tahap ini
        });
    }

    public function down()
    {
        Schema::table('rapat', function (Blueprint $table) {
            if (Schema::hasColumn('rapat', 'approval2_user_id')) $table->dropColumn('approval2_user_id');
            if (Schema::hasColumn('rapat', 'approval1_user_id')) $table->dropColumn('approval1_user_id');
        });
    }
}
