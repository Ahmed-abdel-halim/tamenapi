<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custodies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supervisor_id')->constrained('supervisors')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->foreignId('treasury_id')->constrained('treasuries')->onDelete('cascade');
            $table->text('notes')->nullable();
            $table->boolean('is_closed')->default(false);
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custodies');
    }
};

