<?php

namespace App\Http\Controllers;

use App\Models\MailDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MailDocumentController extends Controller
{
    public function index(Request $request)
    {
        $query = MailDocument::with(['entity', 'employee']);

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        return response()->json($query->orderBy('created_at', 'desc')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:incoming,outgoing',
            'serial_number' => 'nullable|string',
            'entity_id' => 'nullable|exists:external_entities,id',
            'sender_name_manual' => 'nullable|string',
            'recipient_name_manual' => 'nullable|string',
            'subject' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date' => 'required|date',
            'registered_at' => 'nullable|date',
            'messenger_name' => 'nullable|string',
            'messenger_phone' => 'nullable|string',
            'employee_id' => 'nullable|exists:users,id',
            'pages_count' => 'nullable|integer',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:51200',
            'attachments.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:51200',
            'referential_number' => 'nullable|string|unique:mail_documents,referential_number',
        ]);

        if (!$request->filled('referential_number')) {
            // توليد الرقم الإشاري الإلكتروني تلقائياً إذا لم يتم إدخاله
            $year = date('Y', strtotime($validated['date']));
            $typePrefix = $validated['type'] === 'incoming' ? 'IN' : 'OUT';

            $lastDoc = MailDocument::where('type', $validated['type'])
                ->whereYear('date', $year)
                ->orderBy('id', 'desc')
                ->first();

            $sequence = 1;
            if ($lastDoc) {
                // استخراج الرقم التسلسلي من الرقم الإشاري الأخير
                $parts = explode('-', $lastDoc->referential_number);
                $lastSeq = (int) end($parts);
                $sequence = $lastSeq + 1;
            }

            $validated['referential_number'] = sprintf("MLI-%s-%s-%04d", $typePrefix, $year, $sequence);
        }

        // التعامل مع المرفقات المتعددة
        $attachments = [];
        if ($request->hasFile('attachments')) {
            $files = $request->file('attachments');
            if (is_array($files)) {
                foreach ($files as $file) {
                    if ($file->isValid()) {
                        $path = $file->store('mail_attachments', 'public');
                        $attachments[] = $path;
                    } else {
                        return response()->json([
                            'message' => 'أحد الملفات المرفقة غير صالح أو حجمه كبير جداً',
                            'error' => $file->getErrorMessage()
                        ], 422);
                    }
                }
            }
        }
        $validated['attachments'] = $attachments;

        // دعم الحقل القديم للتوافق
        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('mail_attachments', 'public');
            $validated['attachment_path'] = $path;
        }

        $document = MailDocument::create($validated);

        // إرسال البريد الإلكتروني إذا طلب المستخدم وكان هناك بريد إلكتروني للجهة
        if ($request->boolean('send_email') && $document->entity && $document->entity->email) {
            try {
                \Illuminate\Support\Facades\Mail::to($document->entity->email)
                    ->send(new \App\Mail\MailDocumentMailable($document));
            } catch (\Exception $e) {
                // يمكن تسجيل الخطأ هنا ولكن لا نريد تعطيل عملية الحفظ الأساسية
                \Illuminate\Support\Facades\Log::error('فشل إرسال إيميل البريد الصادر: ' . $e->getMessage());
            }
        }

        return response()->json($document->load(['entity', 'employee']), 201);
    }

    public function show(MailDocument $mailDocument)
    {
        return response()->json($mailDocument->load(['entity', 'employee']));
    }

    public function update(Request $request, MailDocument $mailDocument)
    {
        $validated = $request->validate([
            'serial_number' => 'nullable|string',
            'entity_id' => 'nullable|exists:external_entities,id',
            'sender_name_manual' => 'nullable|string',
            'recipient_name_manual' => 'nullable|string',
            'subject' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date' => 'required|date',
            'registered_at' => 'nullable|date',
            'messenger_name' => 'nullable|string',
            'messenger_phone' => 'nullable|string',
            'employee_id' => 'nullable|exists:users,id',
            'pages_count' => 'nullable|integer',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:51200',
            'attachments.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:51200',
            'existing_attachments' => 'nullable|string', // JSON string of paths to keep
            'referential_number' => 'required|string|unique:mail_documents,referential_number,' . $mailDocument->id,
        ]);

        $currentAttachments = $mailDocument->attachments ?? [];
        
        // التعامل مع المرفقات الموجودة (في حال تم حذف بعضها من الفرونت إند)
        if ($request->has('existing_attachments')) {
            $keep = json_decode($request->existing_attachments, true);
            if (is_array($keep)) {
                // حذف الملفات التي لم تعد موجودة في القائمة
                $toDelete = array_diff($currentAttachments, $keep);
                foreach ($toDelete as $path) {
                    Storage::disk('public')->delete($path);
                }
                $currentAttachments = $keep;
            }
        }

        // إضافة مرفقات جديدة
        if ($request->hasFile('attachments')) {
            $files = $request->file('attachments');
            if (is_array($files)) {
                foreach ($files as $file) {
                    if ($file->isValid()) {
                        $path = $file->store('mail_attachments', 'public');
                        $currentAttachments[] = $path;
                    } else {
                        return response()->json([
                            'message' => 'أحد الملفات المرفقة غير صالح أو حجمه كبير جداً',
                            'error' => $file->getErrorMessage()
                        ], 422);
                    }
                }
            }
        }
        $validated['attachments'] = $currentAttachments;

        if ($request->hasFile('attachment')) {
            // حذف المرفق القديم إن وجد
            if ($mailDocument->attachment_path) {
                Storage::disk('public')->delete($mailDocument->attachment_path);
            }
            $path = $request->file('attachment')->store('mail_attachments', 'public');
            $validated['attachment_path'] = $path;
        }

        $mailDocument->update($validated);

        return response()->json($mailDocument->load(['entity', 'employee']));
    }

    public function destroy(MailDocument $mailDocument)
    {
        if ($mailDocument->attachment_path) {
            Storage::disk('public')->delete($mailDocument->attachment_path);
        }
        
        if ($mailDocument->attachments && is_array($mailDocument->attachments)) {
            foreach ($mailDocument->attachments as $path) {
                Storage::disk('public')->delete($path);
            }
        }
        
        $mailDocument->delete();
        return response()->json(['message' => 'Document deleted successfully']);
    }
}
