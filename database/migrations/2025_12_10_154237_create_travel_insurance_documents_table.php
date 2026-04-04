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
        Schema::create('travel_insurance_documents', function (Blueprint $table) {
            $table->id();
            $table->enum('insurance_type', ['تأمين المسافرين', 'تأمين زائرين ليبيا'])->default('تأمين المسافرين');
            $table->string('insurance_number')->unique();
            $table->dateTime('issue_date');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('duration')->nullable();
            $table->string('geographic_area')->nullable();
            $table->decimal('premium', 10, 3)->default(0);
            $table->decimal('family_members_premium', 10, 3)->default(0);
            $table->decimal('stamp', 10, 3)->default(0.500);
            $table->decimal('issue_fees', 10, 3)->default(0);
            $table->decimal('supervision_fees', 10, 3)->default(0.180);
            $table->decimal('total', 10, 3)->default(0);
            $table->timestamps();
        });

        Schema::create('travel_insurance_passengers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('travel_insurance_document_id')->constrained('travel_insurance_documents')->onDelete('cascade');
            $table->boolean('is_main_passenger')->default(true);
            $table->string('relationship')->nullable(); // أب، أم، أخ، إلخ
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('phone')->nullable();
            $table->string('passport_number')->nullable();
            $table->text('address')->nullable();
            $table->date('birth_date')->nullable();
            $table->integer('age')->nullable();
            $table->enum('gender', ['ذكر', 'أنثى'])->nullable();
            $table->string('nationality')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('travel_insurance_passengers');
        Schema::dropIfExists('travel_insurance_documents');
    }
};
