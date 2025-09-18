<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApprovalRequestsTable extends Migration {
    public function up()
    {
        Schema::create('approval_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('rapat_id');
            $table->enum('doc_type', ['undangan','absensi','notulensi']);
            $table->unsignedBigInteger('approver_user_id');
            $table->unsignedTinyInteger('order_index')->default(1); // 1 duluan, lalu 2
            $table->enum('status', ['pending','approved','rejected'])->default('pending');

            // tanda tangan QR (disimpan di public/qr)
            $table->string('signature_qr_path')->nullable();   // contoh: "qr/qr_undangan_r12_a5_abcd.png"
            $table->text('signature_payload')->nullable();     // JSON ber-HMAC
            $table->timestamp('signed_at')->nullable();

            // token untuk link sign
            $table->string('sign_token', 64)->unique();

            $table->timestamps();

            $table->foreign('rapat_id')->references('id')->on('rapat')->onDelete('cascade');
            $table->foreign('approver_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['rapat_id','doc_type','order_index']);
        });

        // opsional: cap waktu approved per dokumen di rapat (indikator selesai)
        if (!Schema::hasColumn('rapat','undangan_approved_at')) {
            Schema::table('rapat', function (Blueprint $table) {
                $table->timestamp('undangan_approved_at')->nullable()->after('token_qr');
                $table->timestamp('absensi_approved_at')->nullable()->after('undangan_approved_at');
                $table->timestamp('notulensi_approved_at')->nullable()->after('absensi_approved_at');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('approval_requests')) {
            Schema::dropIfExists('approval_requests');
        }
        Schema::table('rapat', function (Blueprint $table) {
            if (Schema::hasColumn('rapat','notulensi_approved_at')) $table->dropColumn('notulensi_approved_at');
            if (Schema::hasColumn('rapat','absensi_approved_at'))  $table->dropColumn('absensi_approved_at');
            if (Schema::hasColumn('rapat','undangan_approved_at')) $table->dropColumn('undangan_approved_at');
        });
    }
};
