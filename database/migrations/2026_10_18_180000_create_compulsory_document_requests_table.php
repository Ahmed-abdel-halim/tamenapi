<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('compulsory_document_requests')) {
            Schema::create('compulsory_document_requests', function (Blueprint $table) {
                $table->id();
                $table->string('request_number', 50)->unique();
                $table->string('reference_number', 100)->nullable(); // الرقم الإشاري (مثل أ / 9 - 2026)
                $table->date('request_date');
                $table->integer('documents_count');
                $table->decimal('fee_per_document', 15, 2)->default(5.00);
                $table->decimal('total_amount', 15, 2);
                $table->string('amount_in_words', 255)->nullable();
                $table->string('payment_method', 100)->default('صك مصرفي');
                $table->string('payment_reference', 150)->nullable();
                $table->string('payment_receipt_image', 255)->nullable();
                $table->enum('status', ['draft', 'pending_approval', 'pending_payment', 'sent', 'received', 'canceled'])->default('draft');
                $table->string('signatory_name', 150)->default('محمد حوشي');
                $table->string('signatory_title', 150)->default('مدير الشؤون الإدارية');
                $table->string('subject', 255)->default('طلب تزويد وثائق التأمين الإجباري');
                $table->string('addressed_to', 255)->default('السيد / مدير عام هيئة الإشراف على التأمين');
                $table->integer('received_quantity')->nullable();
                $table->date('received_date')->nullable();
                $table->string('serial_from', 100)->nullable();
                $table->string('serial_to', 100)->nullable();
                $table->foreignId('store_item_id')->nullable()->constrained('store_items')->nullOnDelete();
                $table->string('warehouse_location', 150)->nullable()->default('المخزن الرئيسي');
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('compulsory_document_requests');
    }
};
