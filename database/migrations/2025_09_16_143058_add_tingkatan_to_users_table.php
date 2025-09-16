<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTingkatanToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // 1,2; nullable => user biasa tanpa tingkatan
            if (!Schema::hasColumn('users', 'tingkatan')) {
                $table->tinyInteger('tingkatan')->nullable()->after('unit'); // 1 atau 2
                $table->index('tingkatan');
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'tingkatan')) {
                $table->dropIndex(['tingkatan']);
                $table->dropColumn('tingkatan');
            }
        });
    }
}
