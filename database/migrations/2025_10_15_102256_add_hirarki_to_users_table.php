<?php

// database/migrations/2025_10_15_000000_add_hirarki_to_users_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHirarkiToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedSmallInteger('hirarki')->nullable()->after('role'); // angka kecil = prioritas tinggi
            $table->index('hirarki');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['hirarki']);
            $table->dropColumn('hirarki');
        });
    }
}

