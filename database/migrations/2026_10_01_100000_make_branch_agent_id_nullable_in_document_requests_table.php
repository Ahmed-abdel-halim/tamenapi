<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // استخدام ALTER TABLE مباشرة لضمان العمل على MySQL بدون اشتراط حزمة doctrine/dbal
        try {
            DB::statement("ALTER TABLE `document_requests` MODIFY `branch_agent_id` BIGINT UNSIGNED NULL");
        } catch (\Exception $e) {
            Schema::table('document_requests', function (Blueprint $table) {
                $table->unsignedBigInteger('branch_agent_id')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            DB::statement("ALTER TABLE `document_requests` MODIFY `branch_agent_id` BIGINT UNSIGNED NOT NULL");
        } catch (\Exception $e) {
            Schema::table('document_requests', function (Blueprint $table) {
                $table->unsignedBigInteger('branch_agent_id')->nullable(false)->change();
            });
        }
    }
};