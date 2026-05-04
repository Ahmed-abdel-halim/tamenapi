<?php

namespace App\Http\Controllers;

use App\Models\RentalVoucher;
use App\Models\RentalRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RentalVoucherController extends Controller
{
    public function index()
    {
        $vouchers = RentalVoucher::withCount('records')
            ->withSum('records', 'total_amount')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($v) {
                $v->personal_photo_url  = $v->personal_photo    ? asset('storage/' . $v->personal_photo)    : null;
                $v->id_photo_url        = $v->id_photo          ? asset('storage/' . $v->id_photo)          : null;
                $v->national_id_photo_url = $v->national_id_photo ? asset('storage/' . $v->national_id_photo) : null;
                $v->contract_photos_urls = collect($v->contract_photos ?? [])->map(fn($p) => asset('storage/' . $p))->toArray();
                return $v;
            });

        return response()->json(['success' => true, 'data' => $vouchers]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'owner_name'          => 'required|string|max:255',
            'phone'               => 'required|string|max:50',
            'national_id'         => 'required|string|max:100',
            'personal_photo'      => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'id_photo'            => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'national_id_photo'   => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'contract_photos.*'   => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'notes'               => 'nullable|string',
            'records'             => 'nullable|string', // JSON string
        ]);

        $data = [
            'owner_name'  => $request->owner_name,
            'phone'       => $request->phone,
            'national_id' => $request->national_id,
            'notes'       => $request->notes,
        ];

        if ($request->hasFile('personal_photo')) {
            $data['personal_photo'] = $request->file('personal_photo')->store('rental_vouchers', 'public');
        }
        if ($request->hasFile('id_photo')) {
            $data['id_photo'] = $request->file('id_photo')->store('rental_vouchers', 'public');
        }
        if ($request->hasFile('national_id_photo')) {
            $data['national_id_photo'] = $request->file('national_id_photo')->store('rental_vouchers', 'public');
        }

        $contractPhotos = [];
        if ($request->hasFile('contract_photos')) {
            foreach ($request->file('contract_photos') as $file) {
                $contractPhotos[] = $file->store('rental_vouchers', 'public');
            }
        }
        $data['contract_photos'] = $contractPhotos;

        $voucher = RentalVoucher::create($data);

        // حفظ السجلات
        if ($request->has('records')) {
            $records = json_decode($request->records, true) ?? [];
            foreach ($records as $rec) {
                RentalRecord::create([
                    'rental_voucher_id' => $voucher->id,
                    'from_date'         => $rec['from_date'],
                    'to_date'           => $rec['to_date'],
                    'apartments_count'  => $rec['apartments_count'] ?? 1,
                    'total_amount'      => $rec['total_amount'] ?? 0,
                    'recipient_name'    => $rec['recipient_name'],
                ]);
            }
        }

        return response()->json(['success' => true, 'data' => $voucher->load('records')], 201);
    }

    public function show($id)
    {
        $voucher = RentalVoucher::with('records')->findOrFail($id);

        $voucher->personal_photo_url    = $voucher->personal_photo    ? asset('storage/' . $voucher->personal_photo)    : null;
        $voucher->id_photo_url          = $voucher->id_photo          ? asset('storage/' . $voucher->id_photo)          : null;
        $voucher->national_id_photo_url = $voucher->national_id_photo ? asset('storage/' . $voucher->national_id_photo) : null;
        $voucher->contract_photos_urls  = collect($voucher->contract_photos ?? [])->map(fn($p) => asset('storage/' . $p))->toArray();

        return response()->json(['success' => true, 'data' => $voucher]);
    }

    public function update(Request $request, $id)
    {
        $voucher = RentalVoucher::findOrFail($id);

        $request->validate([
            'owner_name'                    => 'required|string|max:255',
            'phone'                         => 'required|string|max:50',
            'national_id'                   => 'required|string|max:100',
            'personal_photo'                => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'id_photo'                      => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'national_id_photo'             => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'contract_photos.*'             => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'existing_contract_photos'      => 'nullable|string',
            'notes'                         => 'nullable|string',
            'records'                       => 'nullable|string',
        ]);

        $data = [
            'owner_name'  => $request->owner_name,
            'phone'       => $request->phone,
            'national_id' => $request->national_id,
            'notes'       => $request->notes,
        ];

        if ($request->hasFile('personal_photo')) {
            if ($voucher->personal_photo) Storage::disk('public')->delete($voucher->personal_photo);
            $data['personal_photo'] = $request->file('personal_photo')->store('rental_vouchers', 'public');
        }
        if ($request->hasFile('id_photo')) {
            if ($voucher->id_photo) Storage::disk('public')->delete($voucher->id_photo);
            $data['id_photo'] = $request->file('id_photo')->store('rental_vouchers', 'public');
        }
        if ($request->hasFile('national_id_photo')) {
            if ($voucher->national_id_photo) Storage::disk('public')->delete($voucher->national_id_photo);
            $data['national_id_photo'] = $request->file('national_id_photo')->store('rental_vouchers', 'public');
        }

        // الصور القديمة المحتفظ بها
        $existingPhotos = json_decode($request->existing_contract_photos ?? '[]', true) ?? [];
        $newPhotos = [];
        if ($request->hasFile('contract_photos')) {
            foreach ($request->file('contract_photos') as $file) {
                $newPhotos[] = $file->store('rental_vouchers', 'public');
            }
        }
        $data['contract_photos'] = array_merge($existingPhotos, $newPhotos);

        $voucher->update($data);

        // تحديث السجلات: حذف القديمة وإعادة الإدخال
        if ($request->has('records')) {
            $voucher->records()->delete();
            $records = json_decode($request->records, true) ?? [];
            foreach ($records as $rec) {
                RentalRecord::create([
                    'rental_voucher_id' => $voucher->id,
                    'from_date'         => $rec['from_date'],
                    'to_date'           => $rec['to_date'],
                    'apartments_count'  => $rec['apartments_count'] ?? 1,
                    'total_amount'      => $rec['total_amount'] ?? 0,
                    'recipient_name'    => $rec['recipient_name'],
                ]);
            }
        }

        return response()->json(['success' => true, 'data' => $voucher->load('records')]);
    }

    public function destroy($id)
    {
        $voucher = RentalVoucher::findOrFail($id);

        // حذف الصور من التخزين
        foreach (['personal_photo', 'id_photo', 'national_id_photo'] as $field) {
            if ($voucher->$field) Storage::disk('public')->delete($voucher->$field);
        }
        foreach ($voucher->contract_photos ?? [] as $path) {
            Storage::disk('public')->delete($path);
        }

        $voucher->delete();

        return response()->json(['success' => true, 'message' => 'تم حذف ورقة الإيجار بنجاح']);
    }
}
