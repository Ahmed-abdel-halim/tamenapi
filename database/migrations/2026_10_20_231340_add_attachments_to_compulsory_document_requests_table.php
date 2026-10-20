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
        if (Schema::hasTable('compulsory_document_requests') && !Schema::hasColumn('compulsory_document_requests', 'attachments')) {
            Schema::table('compulsory_document_requests', function (Blueprint $table) {
                $table->text('attachments')->nullable()->after('payment_receipt_image');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('compulsory_document_requests') && Schema::hasColumn('compulsory_document_requests', 'attachments')) {
            Schema::table('compulsory_document_requests', function (Blueprint $table) {
                $table->dropColumn('attachments');
            });
        }
    }
};
