<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRejectionColumnsToApprovalRequestsTable extends Migration
{
    public function up()
    {
        Schema::table('approval_requests', function (Blueprint $table) {
            // kolom untuk penolakan
            $table->text('rejection_note')->nullable()->after('signature_payload');
            $table->unsignedBigInteger('rejected_by')->nullable()->after('rejection_note');
            $table->timestamp('rejected_at')->nullable()->after('rejected_by');

            // index kecil agar query cepat
            $table->index(['status', 'doc_type']);
            $table->index(['rapat_id', 'doc_type', 'status']);
        });
    }

    public function down()
    {
        Schema::table('approval_requests', function (Blueprint $table) {
            $table->dropIndex(['approval_requests_status_doc_type_index']);
            $table->dropIndex(['approval_requests_rapat_id_doc_type_status_index']);
            $table->dropColumn(['rejection_note','rejected_by','rejected_at']);
        });
    }
}
