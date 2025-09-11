<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLaporanArchivedMeetingsTable extends Migration
{
    public function up(): void
    {
        Schema::create('laporan_archived_meetings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rapat_id')->index(); // rapat yang diarsip
            $table->unsignedBigInteger('archived_by')->nullable(); // siapa yang mengarsipkan
            $table->timestamp('archived_at')->nullable();         // kapan diarsipkan
            $table->timestamps();

            // relasi opsional
            $table->foreign('rapat_id')->references('id')->on('rapat')->onDelete('cascade');
            $table->foreign('archived_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_archived_meetings');
    }
}
