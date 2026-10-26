<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add presence fields to users table if not existing
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'last_seen_at')) {
                $table->timestamp('last_seen_at')->nullable()->after('is_active');
            }
            if (!Schema::hasColumn('users', 'current_login_at')) {
                $table->timestamp('current_login_at')->nullable()->after('last_seen_at');
            }
        });

        // 2. Create employee_attendances table
        if (!Schema::hasTable('employee_attendances')) {
            Schema::create('employee_attendances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
                $table->date('date')->index();
                $table->timestamp('first_login_at')->nullable();
                $table->timestamp('last_activity_at')->nullable();
                $table->unsignedInteger('total_online_seconds')->default(0);
                $table->unsignedInteger('sessions_count')->default(1);
                $table->string('status', 30)->default('offline');
                $table->string('login_ip', 60)->nullable();
                $table->string('device_info', 255)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'date']);
                $table->index(['branch_id', 'date']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_attendances');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'current_login_at')) {
                $table->dropColumn('current_login_at');
            }
            if (Schema::hasColumn('users', 'last_seen_at')) {
                $table->dropColumn('last_seen_at');
            }
        });
    }
};
