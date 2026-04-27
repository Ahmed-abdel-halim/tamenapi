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
        Schema::create('mail_documents', function (Blueprint $col) {
            $col->id();
            $col->enum('type', ['incoming', 'outgoing']); // وارد أو صادر
            $col->string('referential_number')->unique(); // الرقم الإشاري الإلكتروني
            $col->string('serial_number')->nullable(); // رقم التسلسل (يدوي إن وجد)
            
            // الربط مع الجهة الخارجية
            $col->unsignedBigInteger('entity_id')->nullable();
            $col->foreign('entity_id')->references('id')->on('external_entities')->onDelete('set null');
            
            // في حال كانت الجهة غير مسجلة في الدليل (إدخال يدوي)
            $col->string('sender_name_manual')->nullable();
            $col->string('recipient_name_manual')->nullable();
            
            $col->string('subject'); // موضوع الرسالة / نوع الرسالة
            $col->text('description')->nullable(); // ملاحظات
            $col->date('date'); // تاريخ الرسالة
            $col->date('registered_at')->nullable(); // سجلت بتاريخ
            
            $col->string('messenger_name')->nullable(); // اسم المندوب
            $col->string('messenger_phone')->nullable(); // هاتف المندوب
            
            // الموظف المستلم أو المرسل (داخلي)
            $col->unsignedBigInteger('employee_id')->nullable();
            $col->foreign('employee_id')->references('id')->on('users')->onDelete('set null');
            
            $col->string('attachment_path')->nullable(); // مسار ملف الـ PDF أو الصورة
            $col->integer('pages_count')->default(1); // عدد الورقات
            
            $col->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mail_documents');
    }
};
