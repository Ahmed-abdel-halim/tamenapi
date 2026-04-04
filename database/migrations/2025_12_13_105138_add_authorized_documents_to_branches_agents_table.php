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
        Schema::table('branches_agents', function (Blueprint $table) {
            $table->json('authorized_documents')->nullable()->after('status'); // أنواع التأمين المصرح بها
            $table->json('document_percentages')->nullable()->after('authorized_documents'); // النسب الخاصة بكل نوع تأمين
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches_agents', function (Blueprint $table) {
            $table->dropColumn(['authorized_documents', 'document_percentages']);
        });
    }
};
