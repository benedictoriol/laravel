<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('shop_services') || ! Schema::hasColumn('shop_services', 'category')) {
            return;
        }

        DB::statement("ALTER TABLE `shop_services` MODIFY COLUMN `category` VARCHAR(100) NOT NULL");
    }

    public function down(): void
    {
        // Keep as varchar to avoid truncating modern category keys.
    }
};
