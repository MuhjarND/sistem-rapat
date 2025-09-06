<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddConstraintToUndanganAbsensi extends Migration
{
    public function up()
    {
        Schema::table('undangan', function (Blueprint $table) {
            // UNIQUE: id_rapat + id_user
            $table->unique(['id_rapat', 'id_user'], 'undangan_unique_rapat_user');

            // FOREIGN KEY: id_rapat ke rapat(id), on delete cascade
            $table->foreign('id_rapat')
                  ->references('id')->on('rapat')
                  ->onDelete('cascade');

            // FOREIGN KEY: id_user ke users(id), on delete cascade
            $table->foreign('id_user')
                  ->references('id')->on('users')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('undangan', function (Blueprint $table) {
            $table->dropUnique('undangan_unique_rapat_user');
            $table->dropForeign(['id_rapat']);
            $table->dropForeign(['id_user']);
        });
    }
}
