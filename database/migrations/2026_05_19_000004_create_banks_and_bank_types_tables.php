<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banks', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('bank_transaction_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        // Insert default data
        $banks = ['مصرف الجمهورية', 'مصرف الوحدة', 'مصرف التجارة والتنمية', 'المصرف الإسلامي الليبي', 'مصرف صحارى', 'مصرف الأمان', 'المصرف التجاري الوطني'];
        foreach ($banks as $bank) {
            DB::table('banks')->insert(['name' => $bank, 'created_at' => now(), 'updated_at' => now()]);
        }

        $types = ['حوالة مصرفية (من مصرف لآخر)', 'إيداع نقدي مباشر في الحساب', 'دفع عبر الموبايل الإلكتروني', 'صك مصرفي / شيك مقاصة', 'حوالة عبر مكتب صرافة/حوالات', 'تسوية مبيعات بطاقات (POS)', 'أخرى'];
        foreach ($types as $type) {
            DB::table('bank_transaction_types')->insert(['name' => $type, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_transaction_types');
        Schema::dropIfExists('banks');
    }
};
