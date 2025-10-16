<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAbsensiGuestTable extends Migration
{
    public function up(): void {
        Schema::create('absensi_guest', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('id_rapat');
            $table->string('nama', 120);
            $table->string('instansi', 150)->nullable();
            $table->string('jabatan', 120)->nullable();
            $table->string('no_hp', 32)->nullable();
            $table->enum('status', ['hadir','tidak_hadir','izin'])->default('hadir');
            $table->timestamp('waktu_absen')->nullable();
            $table->string('ttd_path')->nullable();
            $table->string('ttd_hash', 64)->nullable();
            $table->string('ip', 64)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();

            $table->foreign('id_rapat')->references('id')->on('rapat')->onDelete('cascade');
            $table->index(['id_rapat','no_hp']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('absensi_guest');
    }
}
