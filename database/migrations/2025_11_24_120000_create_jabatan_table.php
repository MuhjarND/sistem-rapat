<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJabatanTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('jabatan')) {
            Schema::create('jabatan', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('nama', 150)->unique();
                $table->string('kategori', 120)->nullable();
                $table->string('keterangan', 255)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'jabatan_id')) {
                $table->unsignedBigInteger('jabatan_id')->nullable()->after('jabatan');
                $table->foreign('jabatan_id')->references('id')->on('jabatan')->onDelete('set null');
            }
            if (!Schema::hasColumn('users', 'jabatan_keterangan')) {
                $table->string('jabatan_keterangan', 255)->nullable()->after('jabatan_id');
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'jabatan_id')) {
                $table->dropForeign(['jabatan_id']);
                $table->dropColumn('jabatan_id');
            }
            if (Schema::hasColumn('users', 'jabatan_keterangan')) {
                $table->dropColumn('jabatan_keterangan');
            }
        });

        Schema::dropIfExists('jabatan');
    }
}
