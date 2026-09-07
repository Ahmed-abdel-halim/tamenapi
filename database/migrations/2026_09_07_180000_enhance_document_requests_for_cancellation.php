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
        Schema::table('document_requests', function (Blueprint $table) {
            $table->string('request_code')->nullable()->unique()->after('id');
            $table->unsignedBigInteger('document_id')->nullable()->after('document_number');
            $table->string('insured_name')->nullable()->after('document_id');
            $table->string('cancellation_reason')->nullable()->after('subject');
            $table->text('cancellation_reason_other')->nullable()->after('cancellation_reason');
            $table->text('notes')->nullable()->after('description');
            $table->boolean('legal_acknowledged')->default(false)->after('notes');
            $table->string('applicant_name')->nullable()->after('user_id');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete()->after('admin_message');
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_requests', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropColumn([
                'request_code',
                'document_id',
                'insured_name',
                'cancellation_reason',
                'cancellation_reason_other',
                'notes',
                'legal_acknowledged',
                'applicant_name',
                'reviewed_by',
                'reviewed_at',
            ]);
        });
    }
};
