<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. إضافة أعمدة جديدة لجدول pos_machines (كود الجهاز، حالة العهدة، الوكيل الحالي)
        Schema::table('pos_machines', function (Blueprint $table) {
            if (!Schema::hasColumn('pos_machines', 'pos_code')) {
                $table->string('pos_code')->nullable()->unique()->after('id')
                      ->comment('الكود الداخلي للجهاز مثل POS-001');
            }
            if (!Schema::hasColumn('pos_machines', 'custody_status')) {
                $table->enum('custody_status', ['available', 'in_custody', 'maintenance', 'damaged'])
                      ->default('available')->after('notes')
                      ->comment('حالة العهدة: متاح، بعهدة وكيل، صيانة، تالف');
            }
            if (!Schema::hasColumn('pos_machines', 'current_agent_id')) {
                $table->unsignedBigInteger('current_agent_id')->nullable()->after('custody_status')
                      ->comment('الوكيل الحائز حالياً للجهاز');
                $table->foreign('current_agent_id')->references('id')->on('branches_agents')->onDelete('set null');
            }
            if (!Schema::hasColumn('pos_machines', 'current_custody_id')) {
                $table->unsignedBigInteger('current_custody_id')->nullable()->after('current_agent_id')
                      ->comment('محضر العهدة النشط حالياً');
            }
        });

        // 2. إنشاء جدول محاضر حركة العهدة
        if (!Schema::hasTable('pos_custody_movements')) {
            Schema::create('pos_custody_movements', function (Blueprint $table) {
                $table->id();
                $table->string('reference_no')->unique()
                      ->comment('الرقم المرجعي: POS-TR-0001 أو POS-RET-0001');
                $table->unsignedBigInteger('pos_machine_id');
                $table->foreign('pos_machine_id')->references('id')->on('pos_machines')->onDelete('cascade');
                $table->unsignedBigInteger('branch_agent_id')->nullable()
                      ->comment('الوكيل المستلم/المسلّم (null إذا كانت للشركة)');
                $table->foreign('branch_agent_id')->references('id')->on('branches_agents')->onDelete('set null');
                $table->enum('movement_type', ['handover', 'return', 'maintenance_out', 'maintenance_in'])
                      ->comment('handover=تسليم لوكيل، return=إرجاع للشركة');
                $table->date('movement_date')->comment('تاريخ التسليم أو الاسترجاع');
                $table->string('device_condition')
                      ->default('ممتاز')
                      ->comment('حالة الجهاز عند الاستلام/التسليم');
                $table->json('accessories')->nullable()
                      ->comment('قائمة الملحقات المسلمة: شاحن، كابل، بكرات...');
                $table->string('return_reason')->nullable()
                      ->comment('سبب الإرجاع عند الاسترداد');
                $table->enum('financial_clearance', ['cleared', 'pending_audit', 'has_deductions'])
                      ->default('cleared')
                      ->comment('حالة الإبراء المالي');
                $table->string('received_by_name')->nullable()
                      ->comment('اسم المستلم الفعلي (الموظف أو الوكيل)');
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('processed_by')->nullable()
                      ->comment('موظف الحسابات/المخزن الذي أنجز العملية');
                $table->foreign('processed_by')->references('id')->on('users')->onDelete('set null');
                $table->enum('status', ['active', 'closed'])
                      ->default('active')
                      ->comment('active=العهدة نشطة، closed=تمت تسويتها بالإرجاع');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_custody_movements');
        Schema::table('pos_machines', function (Blueprint $table) {
            if (Schema::hasColumn('pos_machines', 'current_agent_id')) {
                $table->dropForeign(['current_agent_id']);
                $table->dropColumn('current_agent_id');
            }
            if (Schema::hasColumn('pos_machines', 'current_custody_id')) {
                $table->dropColumn('current_custody_id');
            }
            if (Schema::hasColumn('pos_machines', 'custody_status')) {
                $table->dropColumn('custody_status');
            }
            if (Schema::hasColumn('pos_machines', 'pos_code')) {
                $table->dropUnique(['pos_code']);
                $table->dropColumn('pos_code');
            }
        });
    }
};
