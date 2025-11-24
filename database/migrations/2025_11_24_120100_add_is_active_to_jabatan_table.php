<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsActiveToJabatanTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('jabatan') && !Schema::hasColumn('jabatan', 'is_active')) {
            Schema::table('jabatan', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('keterangan');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('jabatan') && Schema::hasColumn('jabatan', 'is_active')) {
            Schema::table('jabatan', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
    }
}
