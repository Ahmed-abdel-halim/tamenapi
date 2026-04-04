<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->foreignId('worker_or_activity_id')->constrained('worker_or_activities')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->date('return_date');
            $table->text('notes')->nullable();
            $table->string('attachment')->nullable(); // ملف مرفق
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_returns');
    }
};

