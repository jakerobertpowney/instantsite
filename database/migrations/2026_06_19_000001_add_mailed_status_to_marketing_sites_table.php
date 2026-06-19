<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE marketing_sites MODIFY COLUMN status ENUM('pending', 'mailed', 'claimed', 'dismissed') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE marketing_sites MODIFY COLUMN status ENUM('pending', 'claimed', 'dismissed') NOT NULL DEFAULT 'pending'");
    }
};
