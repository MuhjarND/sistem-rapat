<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPublicTokenToEvotingsTable extends Migration
{
    public function up()
    {
        Schema::table('evotings', function (Blueprint $table) {
            $table->string('public_token', 64)->nullable()->unique()->after('status');
        });
    }

    public function down()
    {
        Schema::table('evotings', function (Blueprint $table) {
            $table->dropUnique(['public_token']);
            $table->dropColumn('public_token');
        });
    }
}
