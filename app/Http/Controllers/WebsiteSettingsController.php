<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use App\Models\HomepageSlider;
use App\Models\HomepageService;
use App\Models\InsuranceType;
use App\Models\MediaPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WebsiteSettingsController extends Controller
{
    // ─── إعدادات الموقع العامة ─────────────────────────────────────────────────

    /**
     * جلب جميع الإعدادات (عامة بدون تسجيل دخول)
     */
    public function getPublicSettings()
    {
        $settings = SiteSetting::all()->pluck('value', 'key');
        $sliders = HomepageSlider::where('is_active', true)->orderBy('sort_order')->get();
        $services = HomepageService::where('is_active', true)->orderBy('sort_order')->get();
        $insuranceTypes = InsuranceType::where('is_active', true)->orderBy('sort_order')->get();

        return response()->json([
            'settings' => $settings,
            'sliders' => $sliders,
            'services' => $services,
            'insurance_types' => $insuranceTypes,
        ]);
    }

    /**
     * جلب الإعدادات فقط (للأدمن)
     */
    public function getSettings()
    {
        return response()->json(SiteSetting::all()->pluck('value', 'key'));
    }

    /**
     * حفظ/تحديث إعدادات الموقع
     */
    public function saveSettings(Request $request)
    {
        $fields = [
            'phone', 'email', 'whatsapp',
            'facebook_url', 'twitter_url', 'linkedin_url', 'youtube_url', 'instagram_url',
            'address_ar', 'address_en',
            'investments_title_ar', 'investments_title_en',
            'investments_content_ar', 'investments_content_en',
        ];

        foreach ($fields as $field) {
            if ($request->has($field)) {
                SiteSetting::setValue($field, $request->input($field));
            }
        }

        if ($request->hasFile('investments_banner')) {
            // Delete old banner if it exists
            $oldBanner = SiteSetting::getValue('investments_banner');
            if ($oldBanner) {
                $oldPath = str_replace('/storage/', '', $oldBanner);
                if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            $path = $request->file('investments_banner')->store('website', 'public');
            SiteSetting::setValue('investments_banner', '/storage/' . $path);
        }

        return response()->json(['message' => 'تم حفظ الإعدادات بنجاح']);
    }

    // ─── بنرات الصفحة الرئيسية ───────────────────────────────────────────────

    public function slidersIndex()
    {
        return response()->json(HomepageSlider::orderBy('sort_order')->get());
    }

    public function slidersStore(Request $request)
    {
        $validated = $request->validate([
            'media_type' => 'required|in:image,video',
            'title_ar' => 'nullable|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'subtitle_ar' => 'nullable|string|max:500',
            'subtitle_en' => 'nullable|string|max:500',
            'button_text_ar' => 'nullable|string|max:100',
            'button_text_en' => 'nullable|string|max:100',
            'button_link' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'media' => 'required|file|max:51200', // max 50MB for videos
        ]);

        $path = $request->file('media')->store('sliders', 'public');
        $validated['media_url'] = '/storage/' . $path;
        unset($validated['media']);

        $slider = HomepageSlider::create($validated);
        return response()->json($slider, 201);
    }

    public function slidersUpdate(Request $request, $id)
    {
        $slider = HomepageSlider::findOrFail($id);

        $validated = $request->validate([
            'media_type' => 'nullable|in:image,video',
            'title_ar' => 'nullable|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'subtitle_ar' => 'nullable|string|max:500',
            'subtitle_en' => 'nullable|string|max:500',
            'button_text_ar' => 'nullable|string|max:100',
            'button_text_en' => 'nullable|string|max:100',
            'button_link' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable',
            'media' => 'nullable|file|max:51200',
        ]);

        if ($request->hasFile('media')) {
            // حذف الملف القديم
            $oldPath = str_replace('/storage/', '', $slider->media_url);
            if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('media')->store('sliders', 'public');
            $validated['media_url'] = '/storage/' . $path;
        }
        unset($validated['media']);

        // Convert is_active string to boolean
        if (isset($validated['is_active'])) {
            $validated['is_active'] = filter_var($validated['is_active'], FILTER_VALIDATE_BOOLEAN);
        }

        $slider->update($validated);
        return response()->json($slider);
    }

    public function slidersDestroy($id)
    {
        $slider = HomepageSlider::findOrFail($id);
        $oldPath = str_replace('/storage/', '', $slider->media_url);
        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }
        $slider->delete();
        return response()->json(['message' => 'تم حذف البنر بنجاح']);
    }

    // ─── خدمات الصفحة الرئيسية ──────────────────────────────────────────────

    public function servicesIndex()
    {
        return response()->json(HomepageService::orderBy('sort_order')->get());
    }

    public function servicesStore(Request $request)
    {
        $validated = $request->validate([
            'title_ar' => 'required|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'desc_ar' => 'nullable|string',
            'desc_en' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
            'link' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'image' => 'nullable|file|max:5120',
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('homepage_services', 'public');
            $validated['image_url'] = '/storage/' . $path;
        }
        unset($validated['image']);

        $service = HomepageService::create($validated);
        return response()->json($service, 201);
    }

    public function servicesUpdate(Request $request, $id)
    {
        $service = HomepageService::findOrFail($id);

        $validated = $request->validate([
            'title_ar' => 'nullable|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'desc_ar' => 'nullable|string',
            'desc_en' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
            'link' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable',
            'image' => 'nullable|file|max:5120',
        ]);

        if ($request->hasFile('image')) {
            if ($service->image_url) {
                $oldPath = str_replace('/storage/', '', $service->image_url);
                if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
            $path = $request->file('image')->store('homepage_services', 'public');
            $validated['image_url'] = '/storage/' . $path;
        }
        unset($validated['image']);

        if (isset($validated['is_active'])) {
            $validated['is_active'] = filter_var($validated['is_active'], FILTER_VALIDATE_BOOLEAN);
        }

        $service->update($validated);
        return response()->json($service);
    }

    public function servicesDestroy($id)
    {
        $service = HomepageService::findOrFail($id);
        if ($service->image_url) {
            $oldPath = str_replace('/storage/', '', $service->image_url);
            if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }
        $service->delete();
        return response()->json(['message' => 'تم حذف الخدمة بنجاح']);
    }

    // ─── أنواع التأمين (صفحة التأمينات) ──────────────────────────────────────

    public function insuranceTypesIndex()
    {
        return response()->json(InsuranceType::orderBy('sort_order')->get());
    }

    public function insuranceTypesStore(Request $request)
    {
        $validated = $request->validate([
            'title_ar' => 'required|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'description_ar' => 'nullable|string',
            'description_en' => 'nullable|string',
            'details_ar' => 'nullable|string',
            'details_en' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:20',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'image' => 'nullable|file|max:5120',
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('insurance_types', 'public');
            $validated['image_url'] = '/storage/' . $path;
        }
        unset($validated['image']);

        $type = InsuranceType::create($validated);
        return response()->json($type, 201);
    }

    public function insuranceTypesUpdate(Request $request, $id)
    {
        $type = InsuranceType::findOrFail($id);

        $validated = $request->validate([
            'title_ar' => 'nullable|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'description_ar' => 'nullable|string',
            'description_en' => 'nullable|string',
            'details_ar' => 'nullable|string',
            'details_en' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:20',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable',
            'image' => 'nullable|file|max:5120',
        ]);

        if ($request->hasFile('image')) {
            if ($type->image_url) {
                $oldPath = str_replace('/storage/', '', $type->image_url);
                if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
            $path = $request->file('image')->store('insurance_types', 'public');
            $validated['image_url'] = '/storage/' . $path;
        }
        unset($validated['image']);

        if (isset($validated['is_active'])) {
            $validated['is_active'] = filter_var($validated['is_active'], FILTER_VALIDATE_BOOLEAN);
        }

        $type->update($validated);
        return response()->json($type);
    }

    public function insuranceTypesDestroy($id)
    {
        $type = InsuranceType::findOrFail($id);
        if ($type->image_url) {
            $oldPath = str_replace('/storage/', '', $type->image_url);
            if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }
        $type->delete();
        return response()->json(['message' => 'تم حذف نوع التأمين بنجاح']);
    }

    // ─── المركز الإعلامي ────────────────────────────────────────────────────────

    public function mediaPostsIndex()
    {
        return response()->json(MediaPost::orderBy('sort_order')->orderBy('published_date', 'desc')->get());
    }

    public function mediaPostsStore(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|in:news,photo,video,audio,info',
            'title_ar' => 'required|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'content_ar' => 'nullable|string',
            'content_en' => 'nullable|string',
            'location_ar' => 'nullable|string|max:255',
            'location_en' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'published_date' => 'nullable|date',
            'media_url' => 'nullable|string',
            'file' => 'nullable|file|max:51200',
        ]);

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('media_center', 'public');
            $validated['media_url'] = '/storage/' . $path;
        }
        unset($validated['file']);

        if (empty($validated['published_date'])) {
            $validated['published_date'] = now();
        }

        $post = MediaPost::create($validated);
        return response()->json($post, 201);
    }

    public function mediaPostsUpdate(Request $request, $id)
    {
        $post = MediaPost::findOrFail($id);

        $validated = $request->validate([
            'type' => 'nullable|string|in:news,photo,video,audio,info',
            'title_ar' => 'nullable|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'content_ar' => 'nullable|string',
            'content_en' => 'nullable|string',
            'location_ar' => 'nullable|string|max:255',
            'location_en' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable',
            'published_date' => 'nullable|date',
            'media_url' => 'nullable|string',
            'file' => 'nullable|file|max:51200',
        ]);

        if ($request->hasFile('file')) {
            if ($post->media_url && str_starts_with($post->media_url, '/storage/')) {
                $oldPath = str_replace('/storage/', '', $post->media_url);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
            $path = $request->file('file')->store('media_center', 'public');
            $validated['media_url'] = '/storage/' . $path;
        }
        unset($validated['file']);

        if (isset($validated['is_active'])) {
            $validated['is_active'] = filter_var($validated['is_active'], FILTER_VALIDATE_BOOLEAN);
        }

        $post->update($validated);
        return response()->json($post);
    }

    public function mediaPostsDestroy($id)
    {
        $post = MediaPost::findOrFail($id);
        if ($post->media_url && str_starts_with($post->media_url, '/storage/')) {
            $oldPath = str_replace('/storage/', '', $post->media_url);
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }
        $post->delete();
        return response()->json(['message' => 'تم حذف المنشور بنجاح']);
    }

    public function getPublicMediaPosts(Request $request)
    {
        $type = $request->query('type');
        $id = $request->query('id');

        if ($id) {
            $post = MediaPost::where('is_active', true)->findOrFail($id);
            $post->increment('views');
            return response()->json($post);
        }

        $query = MediaPost::where('is_active', true);

        if ($type) {
            $query->where('type', $type);
        }

        $posts = $query->orderBy('sort_order')
                      ->orderBy('published_date', 'desc')
                      ->get();

        return response()->json($posts);
    }
}
