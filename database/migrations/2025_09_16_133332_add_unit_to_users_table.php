<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUnitToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'unit')) {
                $table->enum('unit', ['kepaniteraan', 'kesekretariatan'])
                      ->default('kesekretariatan')
                      ->after('jabatan');
            }
            if (!Schema::hasColumn('users', 'no_hp')) {
                $table->string('no_hp', 20)->nullable()->after('email');
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'unit')) {
                $table->dropColumn('unit');
            }
            if (Schema::hasColumn('users', 'no_hp')) {
                $table->dropColumn('no_hp');
            }
        });
    }
}
