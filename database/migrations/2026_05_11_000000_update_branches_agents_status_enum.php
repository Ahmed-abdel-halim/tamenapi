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
        // Add 'قيد الانتظار' to the enum
        DB::statement("ALTER TABLE branches_agents MODIFY COLUMN status ENUM('نشط', 'غير نشط', 'قيد الانتظار') DEFAULT 'نشط'");
        
        // Also add 'requested_documents' to store what the agent requested
        Schema::table('branches_agents', function (Blueprint $table) {
            if (!Schema::hasColumn('branches_agents', 'requested_documents')) {
                $table->json('requested_documents')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches_agents', function (Blueprint $table) {
            if (Schema::hasColumn('branches_agents', 'requested_documents')) {
                $table->dropColumn('requested_documents');
            }
        });
        DB::statement("ALTER TABLE branches_agents MODIFY COLUMN status ENUM('نشط', 'غير نشط') DEFAULT 'نشط'");
    }
};
