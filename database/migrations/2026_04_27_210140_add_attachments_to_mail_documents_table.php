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
        Schema::table('mail_documents', function (Blueprint $table) {
            $table->text('attachments')->nullable()->after('attachment_path'); // To store multiple paths as JSON
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mail_documents', function (Blueprint $table) {
            $table->dropColumn('attachments');
        });
    }
};
