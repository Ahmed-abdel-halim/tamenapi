<?php

namespace App\Http\Controllers;

use App\Models\CashInTransitInsuranceDocument;
use App\Models\BranchAgent;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CashInTransitInsuranceDocumentController extends Controller
{
    public function index(Request $request)
    {
        try {
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
            $document = CashInTransitInsuranceDocument::findOrFail($id);
            if ($document->is_canceled) {
                return response()->json(['message' => 'هذه الوثيقة ملغية بالفعل'], 422);
            }
            $document->update(['is_canceled' => true, 'canceled_at' => now(), 'canceled_by' => $userId, 'cancel_reason' => $validated['cancel_reason']]);
            return response()->json(['message' => 'تم إلغاء الوثيقة بنجاح']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'الوثيقة غير موجودة'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'سبب الإلغاء مطلوب', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'حدث خطأ أثناء إلغاء الوثيقة'], 500);
        }
    }

    public function destroy($id)
    {
        $document = CashInTransitInsuranceDocument::findOrFail($id);
        $document->delete();
        return response()->json(['status' => 'deleted']);
    }


    public function print($id)
    {
        $document = CashInTransitInsuranceDocument::findOrFail($id);
        return view('cash-in-transit-insurance-documents.print', compact('document'));
    }
}
