<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBidangToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // string pendek; sesuaikan panjang jika perlu
            $table->string('bidang', 100)->nullable()->after('unit');
            // index opsional untuk query filter
            $table->index('bidang');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['bidang']);
            $table->dropColumn('bidang');
        });
    }
}
