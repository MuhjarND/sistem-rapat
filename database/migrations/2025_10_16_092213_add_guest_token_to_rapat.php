<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGuestTokenToRapat extends Migration {
    public function up(): void {
        Schema::table('rapat', function (Blueprint $table) {
            if (!Schema::hasColumn('rapat','guest_token')) {
                $table->string('guest_token', 64)->nullable()->after('token_qr');
            }
        });
    }
    public function down(): void {
        Schema::table('rapat', function (Blueprint $table) {
            if (Schema::hasColumn('rapat','guest_token')) {
                $table->dropColumn('guest_token');
            }
        });
    }
};
