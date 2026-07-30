<?php

namespace App\Http\Controllers;

use App\Models\TravelInsuranceDocument;
use App\Models\TravelInsurancePassenger;
use App\Models\BranchAgent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TravelInsuranceDocumentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            // الحصول على المستخدم الحالي من header أو query parameter
            $userId = $request->header('X-User-Id') ?? $request->input('user_id');
            if ($userId) {
                $userId = is_numeric($userId) ? (int) $userId : null;
                $user = $userId ? \App\Models\User::find($userId) : null;
                if (!$user) {
                    return response()->json(['message' => 'غير مصرح لك بإلغاء الوثائق'], 403);
                }
                $isAdmin = $user->is_admin ?? false;
                $authDocs = is_array($user->authorized_documents)
                    ? $user->authorized_documents
                    : (is_string($user->authorized_documents) ? json_decode($user->authorized_documents, true) : []);
                if (!is_array($authDocs)) $authDocs = [];
                $hasPermission = $isAdmin || !empty($authDocs) || $user->department_id !== null || ($user->branch_agent_id ?? null) !== null;
                if (!$hasPermission) {
                    return response()->json(['message' => 'غير مصرح لك بإلغاء الوثائق'], 403);
                }
            }
            }
            $validated = $request->validate(['cancel_reason' => 'required|string|max:1000']);
            $document = TravelInsuranceDocument::findOrFail($id);
            if ($document->is_canceled) {
                return response()->json(['message' => 'هذه الوثيقة ملغية بالفعل'], 422);
            }
            $document->update([
                'is_canceled' => true,
                'canceled_at' => now(),
                'canceled_by' => $userId,
                'cancel_reason' => $validated['cancel_reason'],
            ]);
            return response()->json(['message' => 'تم إلغاء الوثيقة بنجاح']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'الوثيقة غير موجودة'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'سبب الإلغاء مطلوب', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error in TravelInsuranceDocumentController@cancel: ' . $e->getMessage());
            return response()->json(['message' => 'حدث خطأ أثناء إلغاء الوثيقة'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $document = TravelInsuranceDocument::findOrFail($id);
            $document->delete();
            return response()->json(['message' => 'تم حذف الوثيقة بنجاح']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'الوثيقة غير موجودة'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error in TravelInsuranceDocumentController@destroy: ' . $e->getMessage());
            return response()->json([
                'message' => 'حدث خطأ أثناء حذف الوثيقة',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], 500);
        }
    }

    /**
     * Print travel insurance document
     */
    public function print(string $id)
    {
        try {
            $document = TravelInsuranceDocument::with(['passengers', 'branchAgent'])->findOrFail($id);

            // تحضير بيانات الوكالة
            $agencyData = [
                'agency_name' => 'المدار الليبي للتأمين',
                'code' => 'ML0001',
                'agent_name' => 'الإدارة',
            ];

            if ($document->branchAgent) {
                $agencyData['agency_name'] = $document->branchAgent->agency_name ?? 'المدار الليبي للتأمين';
                $agencyData['code'] = $document->branchAgent->code ?? 'ML0001';
                $agencyData['agent_name'] = $document->branchAgent->agent_name ?? 'الإدارة';
            }

            // تحضير البيانات للطباعة
            $mainPassenger = $document->passengers->where('is_main_passenger', true)->first();

            $printData = [
                'insurance_number' => $document->insurance_number,
                'issue_date' => \Carbon\Carbon::parse($document->issue_date)->format('d/m/Y h:i A'),
                'start_date' => \Carbon\Carbon::parse($document->start_date)->format('d/m/Y'),
                'end_date' => \Carbon\Carbon::parse($document->end_date)->format('d/m/Y'),
                'duration' => $document->duration,
                'total_in_words' => $this->numberToArabicWords($document->total),
                'agency_name' => $agencyData['agency_name'],
                'agency_code' => $agencyData['code'],
                'agent_name' => $agencyData['agent_name'],
                'qr_data' => [
                    'insurance_number' => $document->insurance_number,
                    'issue_date' => \Carbon\Carbon::parse($document->issue_date)->format('Y-m-d'),
                    'insured_name' => $mainPassenger ? $mainPassenger->name_ar : '',
                    'total' => $document->total
                ]
            ];

            return view('travel-insurance-documents.print', compact('document', 'printData'));
        } catch (\Exception $e) {
            Log::error('Error in TravelInsuranceDocumentController@print: ' . $e->getMessage());
            abort(404, 'الوثيقة غير موجودة');
        }
    }

    private function numberToArabicWords($number)
    {
        $ones = ['', 'واحد', 'اثنان', 'ثلاثة', 'أربعة', 'خمسة', 'ستة', 'سبعة', 'ثمانية', 'تسعة'];
        $teens = ['عشرة', 'أحد عشر', 'اثنا عشر', 'ثلاثة عشر', 'أربعة عشر', 'خمسة عشر', 'ستة عشر', 'سبعة عشر', 'ثمانية عشر', 'تسعة عشر'];
        $tens = ['', '', 'عشرون', 'ثلاثون', 'أربعون', 'خمسون', 'ستون', 'سبعون', 'ثمانون', 'تسعون'];
        $hundreds = ['', 'مائة', 'مائتان', 'ثلاثمائة', 'أربعمائة', 'خمسمائة', 'ستمائة', 'سبعمائة', 'ثمانمائة', 'تسعمائة'];

        // فصل الجزء الصحيح والجزء العشري
        $parts = explode('.', (string) $number);
        $integerPart = (int) ($parts[0] ?? 0);
        $decimalPart = isset($parts[1]) ? (int) ($parts[1]) : 0;

        // تحويل الجزء الصحيح
        $words = '';

        if ($integerPart == 0 && $decimalPart == 0) {
            return 'صفر دينار';
        }

        if ($integerPart > 0) {
            $num = $integerPart;

            // الآلاف
            if ($num >= 1000) {
                $thousands = (int) ($num / 1000);
                if ($thousands == 1) {
                    $words .= 'ألف ';
                } elseif ($thousands == 2) {
                    $words .= 'ألفان ';
                } elseif ($thousands >= 3 && $thousands <= 10) {
                    $words .= $ones[$thousands] . ' آلاف ';
                } else {
                    $words .= number_format($thousands) . ' ألف ';
                }
                $num = $num % 1000;
            }

            // المئات
            if ($num >= 100) {
                $hundred = (int) ($num / 100);
                $words .= $hundreds[$hundred] . ' ';
                $num = $num % 100;
            }

            // العشرات والآحاد
            if ($num >= 20) {
                $ten = (int) ($num / 10);
                $one = $num % 10;
                if ($one > 0) {
                    $words .= $ones[$one] . ' و' . $tens[$ten];
                } else {
                    $words .= $tens[$ten];
                }
            } elseif ($num >= 10) {
                $words .= $teens[$num - 10];
            } elseif ($num > 0) {
                $words .= $ones[$num];
            }

            $words .= ' دينار';
        }

        // تحويل الجزء العشري
        if ($decimalPart > 0) {
            if ($integerPart > 0) {
                $words .= ' و';
            }
            $words .= $decimalPart . ' درهم';
        }

        return trim($words);
    }
}
