<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddProtokolerToRoleEnumOnUsersTable extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE users
            MODIFY role ENUM('admin','operator','notulis','peserta','approval','protokoler')
            NOT NULL DEFAULT 'peserta'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE users
            MODIFY role ENUM('admin','operator','notulis','peserta','approval')
            NOT NULL DEFAULT 'peserta'
        ");
    }
}
