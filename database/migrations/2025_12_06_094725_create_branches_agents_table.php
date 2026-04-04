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
        Schema::create('branches_agents', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['وكيل', 'فرع من شركة'])->default('وكيل');
            $table->string('code', 20)->unique(); // BK0001, BK0002, etc.
            $table->string('agency_name');
            $table->string('agent_name');
            $table->string('activity')->nullable();
            $table->string('agency_number')->nullable();
            $table->string('stamp_number')->nullable();
            $table->date('contract_date');
            $table->date('contract_end_date')->nullable();
            $table->string('contract_duration')->nullable();
            $table->string('city');
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('nationality')->nullable();
            $table->string('national_id', 12)->nullable(); // 12 رقم بالضبط
            $table->string('identity_number')->nullable(); // حروف وأرقام
            $table->json('consumed_custodies')->nullable(); // عهد مستهلكة
            $table->json('fixed_custodies')->nullable(); // عهد الوكيل الثابتة
            $table->string('personal_photo')->nullable();
            $table->string('identity_photo')->nullable();
            $table->string('contract_photo')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->enum('status', ['نشط', 'غير نشط'])->default('نشط');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches_agents');
    }
};
