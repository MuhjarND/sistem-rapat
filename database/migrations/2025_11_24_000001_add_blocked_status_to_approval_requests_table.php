<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddBlockedStatusToApprovalRequestsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('approval_requests')) {
            return;
        }

        $columnType = DB::table('information_schema.columns')
            ->where('table_schema', DB::raw('DATABASE()'))
            ->where('table_name', 'approval_requests')
            ->where('column_name', 'status')
            ->value('COLUMN_TYPE');

        // Tambahkan enum "blocked" jika belum ada
        if ($columnType && stripos($columnType, "'blocked'") === false) {
            DB::statement("ALTER TABLE approval_requests MODIFY status ENUM('pending','approved','rejected','blocked') DEFAULT 'pending'");
        }
    }

    public function down()
    {
        if (!Schema::hasTable('approval_requests')) {
            return;
        }

        // Pastikan tidak ada data 'blocked' sebelum rollback enum
        DB::table('approval_requests')
            ->where('status', 'blocked')
            ->update(['status' => 'pending']);

        DB::statement("ALTER TABLE approval_requests MODIFY status ENUM('pending','approved','rejected') DEFAULT 'pending'");
    }
}
