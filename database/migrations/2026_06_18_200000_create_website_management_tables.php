<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // إعدادات الموقع العامة (هاتف، إيميل، سوشيال ميديا)
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // بنرات الصفحة الرئيسية (صور/فيديوهات)
        Schema::create('homepage_sliders', function (Blueprint $table) {
            $table->id();
            $table->string('media_type')->default('image'); // image or video
            $table->string('media_url');
            $table->string('title_ar')->nullable();
            $table->string('title_en')->nullable();
            $table->string('subtitle_ar')->nullable();
            $table->string('subtitle_en')->nullable();
            $table->string('button_text_ar')->nullable();
            $table->string('button_text_en')->nullable();
            $table->string('button_link')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // خدمات الصفحة الرئيسية (الكاردات الثمانية)
        Schema::create('homepage_services', function (Blueprint $table) {
            $table->id();
            $table->string('title_ar');
            $table->string('title_en')->nullable();
            $table->text('desc_ar')->nullable();
            $table->text('desc_en')->nullable();
            $table->string('icon')->nullable();
            $table->string('image_url')->nullable();
            $table->string('link')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // أنواع التأمين في صفحة التأمينات (التفصيلية)
        Schema::create('insurance_types', function (Blueprint $table) {
            $table->id();
            $table->string('title_ar');
            $table->string('title_en')->nullable();
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            $table->text('details_ar')->nullable();
            $table->text('details_en')->nullable();
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->string('image_url')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // طلبات التأمين العامة من الزوار
        Schema::create('public_insurance_requests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone');
            $table->string('whatsapp')->nullable();
            $table->string('email')->nullable();
            $table->string('insurance_type')->nullable();
            $table->string('request_type')->default('new'); // new or renewal
            $table->string('previous_policy_number')->nullable();
            $table->string('payment_method')->nullable(); // حوالة، كاش، فيزا، أخرى
            $table->text('notes')->nullable();
            $table->string('attachment_url')->nullable();
            $table->string('status')->default('pending'); // pending, in_progress, approved, rejected
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_insurance_requests');
        Schema::dropIfExists('insurance_types');
        Schema::dropIfExists('homepage_services');
        Schema::dropIfExists('homepage_sliders');
        Schema::dropIfExists('site_settings');
    }
};
