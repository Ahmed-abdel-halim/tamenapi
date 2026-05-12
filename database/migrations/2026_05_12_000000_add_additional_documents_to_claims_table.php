<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->json('additional_documents')->nullable()->after('document_coverage');
            $table->json('document_manual_data')->nullable()->after('additional_documents');
        });
    }

    public function down()
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->dropColumn(['additional_documents', 'document_manual_data']);
        });
    }
};
