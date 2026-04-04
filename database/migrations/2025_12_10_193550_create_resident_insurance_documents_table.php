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
        Schema::create('resident_insurance_documents', function (Blueprint $table) {
            $table->id();
            $table->string('insurance_number')->unique();
            $table->dateTime('issue_date');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('duration')->nullable();
            $table->enum('geographic_area', ['داخل ليبيا (للأفراد)', 'داخل ليبيا (للعائلات)'])->nullable();
            $table->enum('residence_type', ['تأشيرة إقامة Residence Visa', 'تأشيرة عمل Work Visa'])->nullable();
            $table->integer('residence_duration')->nullable(); // مدة الإقامة بالأيام
            $table->decimal('premium', 10, 3)->default(0);
            $table->decimal('family_members_premium', 10, 3)->default(0);
            $table->decimal('tax', 10, 3)->default(2.500);
            $table->decimal('stamp', 10, 3)->default(0.500);
            $table->decimal('issue_fees', 10, 3)->default(10.000);
            $table->decimal('supervision_fees', 10, 3)->default(1.050);
            $table->decimal('total', 10, 3)->default(0);
            $table->timestamps();
        });

        Schema::create('resident_insurance_passengers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resident_insurance_document_id')->constrained('resident_insurance_documents')->onDelete('cascade')->name('res_ins_pass_doc_id_foreign');
            $table->boolean('is_main_passenger')->default(true);
            $table->string('relationship')->nullable(); // For family members
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
        Schema::dropIfExists('resident_insurance_passengers');
        Schema::dropIfExists('resident_insurance_documents');
    }
};
